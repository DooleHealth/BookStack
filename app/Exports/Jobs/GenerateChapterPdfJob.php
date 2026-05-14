<?php

namespace BookStack\Exports\Jobs;

use BookStack\Entities\Models\Chapter;
use BookStack\Exports\ExportFormatter;
use BookStack\Exports\Models\PdfExport;
use BookStack\Exports\Notifications\PdfExportReadyNotification;
use BookStack\Users\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateChapterPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1200;
    public bool $failOnTimeout = true;
    public int $tries = 1;

    public function __construct(
        protected Chapter $chapter,
        protected User $user,
        protected string $locale = 'en',
        protected ?int $pdfExportId = null,
    ) {
        $this->onQueue(env('SQS_PDF_QUEUE', env('SQS_QUEUE', 'default')));
    }

    public function handle(ExportFormatter $exportFormatter): void
    {
        $lockKey = 'pdf_export_chapter_' . $this->chapter->id . '_' . $this->user->id;
        $doneKey = $lockKey . '_done';

        if (Cache::has($doneKey)) {
            $this->markExport('completed');
            return;
        }

        if (!Cache::lock($lockKey, 1800)->get()) {
            return;
        }

        $this->markExport('processing');

        try {
            if (Cache::has($doneKey)) {
                $this->markExport('completed');
                return;
            }

            Auth::login($this->user);
            app()->setLocale($this->locale);

            $pdfContent = $exportFormatter->chapterToPdf($this->chapter);
            $fileName = $this->chapter->slug . '.pdf';

            $path = $this->uploadToStorage($pdfContent, $fileName);
            $downloadUrl = Storage::disk('exports')->temporaryUrl($path, now()->addDays(7));

            $this->markExport('completed', $path, now()->addDays(7));

            $this->user->notifyNow(new PdfExportReadyNotification(
                $this->chapter->name,
                $downloadUrl,
                $fileName,
            ));

            Cache::put($doneKey, true, 1800);
        } catch (\Throwable $e) {
            $this->markExport('failed', null, null, $e->getMessage());
            throw $e;
        } finally {
            Cache::lock($lockKey)->forceRelease();
        }
    }

    protected function uploadToStorage(string $pdfContent, string $fileName): string
    {
        $path = 'exports/docs/' . now()->format('Y-m-d') . '/' . time() . '_' . $fileName;
        Storage::disk('exports')->put($path, $pdfContent);
        return $path;
    }

    protected function markExport(string $status, ?string $path = null, ?\DateTime $expiresAt = null, ?string $error = null): void
    {
        if (!$this->pdfExportId) {
            return;
        }

        $data = ['status' => $status];
        if ($path) {
            $data['storage_path'] = $path;
        }
        if ($expiresAt) {
            $data['expires_at'] = $expiresAt;
        }
        if ($error) {
            $data['error_message'] = mb_substr($error, 0, 1000);
        }

        PdfExport::where('id', $this->pdfExportId)->update($data);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->markExport('failed', null, null, $exception?->getMessage());
        Log::error("PDF export job failed for chapter [{$this->chapter->id}]: " . $exception?->getMessage());
    }
}
