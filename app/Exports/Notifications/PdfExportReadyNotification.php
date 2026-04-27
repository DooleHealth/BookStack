<?php

namespace BookStack\Exports\Notifications;

use BookStack\App\MailNotification;
use BookStack\Users\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class PdfExportReadyNotification extends MailNotification
{
    public function __construct(
        protected string $bookName,
        protected string $filePath,
        protected string $fileName,
    ) {
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->newMailMessage($notifiable->getLocale())
            ->subject('PDF Export Ready: ' . $this->bookName)
            ->greeting('Your PDF export is ready!')
            ->line("The PDF export for \"{$this->bookName}\" has been generated successfully.")
            ->line('Please find the PDF attached to this email.')
            ->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
