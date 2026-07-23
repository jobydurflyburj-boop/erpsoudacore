<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;

class EmailVerificationService
{
    public function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->notify(new VerifyEmailNotification($user->tenant->subdomain));
    }

    public function markVerified(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        return $user->markEmailAsVerified();
    }
}
