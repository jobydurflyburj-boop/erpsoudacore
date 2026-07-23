<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $tenantSubdomain) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
            absolute: false
        );

        // Signed route generates a path; we resolve it against the
        // tenant's own subdomain, not the central domain, since
        // verification happens in the context of that company's account.
        $verifyUrl = "https://{$this->tenantSubdomain}.".config('tenancy.central_domain').$url;

        return (new MailMessage)
            ->subject('Verify your SoudaCore ERP account')
            ->line('Please verify your email address to activate your account.')
            ->action('Verify Email', $verifyUrl)
            ->line('This link expires in 60 minutes.');
    }
}
