<?php

namespace BookStack\Exports\Jobs;

use BookStack\Entities\Models\Page;
use BookStack\Exports\ExportFormatter;
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

class GeneratePagePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1200;
    public bool $failOnTimeout = false;

    public function __construct(
        protected Page $page,
        protected User $user,
        protected string $locale = 'en',
    ) {
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function handle(ExportFormatter $exportFormatter): void
    {
        $lockKey = 'pdf_export_page_' . $this->page->id . '_' . $this->user->id;
        $doneKey = $lockKey . '_done';

        if (Cache::has($doneKey)) {
            return;
        }

        if (!Cache::lock($lockKey, 1800)->get()) {
            return;
        }

        try {
            if (Cache::has($doneKey)) {
                return;
            }

            ini_set('memory_limit', '512M');

            Auth::login($this->user);
            app()->setLocale($this->locale);

            $pdfContent = $exportFormatter->pageToPdf($this->page);
            $fileName = $this->page->slug . '.pdf';

            $downloadUrl = $this->uploadAndGetUrl($pdfContent, $fileName);

            $this->user->notifyNow(new PdfExportReadyNotification(
                $this->page->name,
                $downloadUrl,
                $fileName,
            ));

            Cache::put($doneKey, true, 1800);
        } finally {
            Cache::lock($lockKey)->forceRelease();
        }
    }

    protected function uploadAndGetUrl(string $pdfContent, string $fileName): string
    {
        $path = 'exports/' . $this->user->id . '/' . time() . '_' . $fileName;
        $disk = Storage::disk('exports');
        $disk->put($path, $pdfContent);

        return $disk->temporaryUrl($path, now()->addDays(7));
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("PDF export job failed for page [{$this->page->id}]: " . $exception?->getMessage());
    }
}
