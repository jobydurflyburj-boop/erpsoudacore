<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $tenantSubdomain,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = "https://{$this->tenantSubdomain}.".config('tenancy.central_domain')
            ."/reset-password?token={$this->token}&email=".urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset your SoudaCore ERP password')
            ->line('You requested a password reset.')
            ->action('Reset Password', $resetUrl)
            ->line('If you did not request this, no action is required — this link expires in 60 minutes.');
    }
}
