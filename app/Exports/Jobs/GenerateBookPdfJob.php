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
use Illuminate\Support\Facades\Log;

class GenerateBookPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        protected Book $book,
        protected User $user,
    ) {
    }

    public function handle(ExportFormatter $exportFormatter): void
    {
        // Authenticate as the user who requested the export
        // so permission checks in BookContents::getTree work correctly.
        Auth::login($this->user);

        $pdfContent = $exportFormatter->bookToPdf($this->book);

        $fileName = $this->book->slug . '.pdf';
        $storagePath = 'exports/' . $this->user->id . '/' . $fileName;

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $disk->put($storagePath, $pdfContent);

        $this->user->notify(new PdfExportReadyNotification(
            $this->book->name,
            $disk->path($storagePath),
            $fileName,
        ));
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("PDF export job failed for book [{$this->book->id}]: " . $exception?->getMessage());
    }
}
