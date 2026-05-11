<?php

namespace BookStack\Exports\Jobs;

use BookStack\Entities\Models\Book;
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

class GenerateBookPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1200;
    public bool $failOnTimeout = false;

    public function __construct(
        protected Book $book,
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
        $lockKey = 'pdf_export_book_' . $this->book->id . '_' . $this->user->id;
        $doneKey = $lockKey . '_done';

        // If already completed by a previous attempt, skip
        if (Cache::has($doneKey)) {
            return;
        }

        if (!Cache::lock($lockKey, 1800)->get()) {
            return;
        }

        try {
            // Double-check after acquiring lock
            if (Cache::has($doneKey)) {
                return;
            }

            Auth::login($this->user);
            app()->setLocale($this->locale);

            $pdfContent = $exportFormatter->bookToPdf($this->book);
            $fileName = $this->book->slug . '.pdf';

            $downloadUrl = $this->uploadAndGetUrl($pdfContent, $fileName);

            $this->user->notifyNow(new PdfExportReadyNotification(
                $this->book->name,
                $downloadUrl,
                $fileName,
            ));

            // Mark as completed so retries are skipped
            Cache::put($doneKey, true, 1800);
        } finally {
            Cache::lock($lockKey)->forceRelease();
        }
    }

    protected function uploadAndGetUrl(string $pdfContent, string $fileName): string
    {
        $path = 'exports/docs/' . now()->format('Y-m-d') . '/' . time() . '_' . $fileName;
        $disk = Storage::disk('exports');
        $disk->put($path, $pdfContent);

        return $disk->temporaryUrl($path, now()->addDays(7));
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("PDF export job failed for book [{$this->book->id}]: " . $exception?->getMessage());
    }
}
