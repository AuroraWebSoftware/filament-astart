<?php

namespace AuroraWebSoftware\FilamentAstart\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $password,
        protected string $loginUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject(__('filament-astart::filament-astart.resources.user.emails.credentials_subject', ['app' => $appName]))
            ->greeting(__('filament-astart::filament-astart.resources.user.emails.greeting', ['name' => $notifiable->name]))
            ->line(__('filament-astart::filament-astart.resources.user.emails.account_created'))
            ->line(__('filament-astart::filament-astart.resources.user.emails.email_label').' **'.$notifiable->email.'**')
            ->line(__('filament-astart::filament-astart.resources.user.emails.password_label').' **'.$this->password.'**')
            ->action(__('filament-astart::filament-astart.resources.user.emails.login_button'), $this->loginUrl)
            ->line(__('filament-astart::filament-astart.resources.user.emails.security_warning'));
    }
}
