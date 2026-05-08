<?php

namespace BookStack\Exports\Notifications;

use BookStack\Users\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PdfExportReadyNotification extends Notification
{
    public function __construct(
        protected string $entityName,
        protected string $downloadUrl,
        protected string $fileName,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $locale = $notifiable->getLocale();

        return (new MailMessage())
            ->view([
                'html' => 'vendor.notifications.email',
                'text' => 'vendor.notifications.email-plain',
            ], ['locale' => $locale])
            ->subject(trans('entities.export_pdf_email_subject', ['name' => $this->entityName]))
            ->greeting(trans('entities.export_pdf_email_greeting'))
            ->line(trans('entities.export_pdf_email_text', ['name' => $this->entityName]))
            ->action(trans('entities.export_pdf_email_action'), $this->downloadUrl)
            ->line(trans('entities.export_pdf_email_expiry'));
    }
}
