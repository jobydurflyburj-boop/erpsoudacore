<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Full OTP generation/storage/verification lifecycle is implemented and
 * production-ready. The ONLY deployment-time gap is the SMS transport
 * (config('security.otp.driver')) — 'log' writes the code to the app log
 * for local/dev; a real Saudi SMS gateway (Unifonic/Msegat) driver is a
 * few lines against this same interface, not a rewrite (see send()).
 */
class OtpService
{
    /**
     * Issues an OTP AND an opaque, unguessable "login ticket" bound to
     * it, so the caller (AuthService) never has to hand a raw user_id
     * back to an unauthenticated client between the password step and
     * the OTP step. The ticket is the ONLY thing the client holds and
     * presents at /auth/otp/verify — see verifyByTicket().
     *
     * Fixes a real gap found in review: the previous design returned
     * user_id directly, and the verify endpoint accepted it with
     * withoutGlobalScope('tenant') — technically still gated by the OTP
     * code + rate limits, but it made the endpoint an oracle for probing
     * arbitrary user IDs. An opaque, single-use, short-lived ticket
     * removes that surface entirely: there's nothing meaningful to probe.
     */
    public function generateWithTicket(User $user, string $purpose, string $destination): string
    {
        OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $length = (int) config('security.otp.length');
        $code = (string) random_int(
            (int) str_pad('1', $length, '0'),
            (int) str_pad('', $length, '9')
        );

        $ticket = Str::random(48);

        OtpCode::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'ticket_hash' => hash('sha256', $ticket),
            'destination' => $this->mask($destination),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('security.otp.ttl_minutes')),
        ]);

        $this->send($destination, $code);

        return $ticket;
    }

    /**
     * @return User the verified user, once the code matches
     * @throws RuntimeException on an invalid/expired ticket, expired
     *         code, too many attempts, or a wrong code
     */
    public function verifyByTicket(string $ticket, string $code): User
    {
        // Deliberately bypasses the tenant scope: at this point in the
        // flow we do NOT yet know which tenant this request belongs to
        // from anything the client can be trusted to state — the ticket
        // hash itself (cryptographically random, single-use, 5-minute
        // TTL) is the entire trust boundary here, not tenant_id. This is
        // the same reasoning as TokenService::refresh()'s token_hash
        // lookup. RLS still applies underneath — see note in
        // docs/FOUNDATION.md "Tenant isolation review — OTP ticket".
        $otp = OtpCode::withoutGlobalScope('tenant')
            ->where('ticket_hash', hash('sha256', $ticket))
            ->whereNull('consumed_at')
            ->first();

        if (! $otp) {
            throw new RuntimeException('This verification session is invalid or has expired.');
        }

        if ($otp->expires_at->isPast()) {
            throw new RuntimeException('This code has expired. Please log in again.');
        }

        if ($otp->attempts >= (int) config('security.otp.max_attempts')) {
            throw new RuntimeException('Too many incorrect attempts. Please log in again.');
        }

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp->increment('attempts');

            throw new RuntimeException('Incorrect code.');
        }

        $otp->update(['consumed_at' => now()]);

        return User::withoutGlobalScope('tenant')->findOrFail($otp->user_id);
    }

    public function generate(User $user, string $purpose, string $destination): OtpCode
    {
        // Invalidate any still-live OTP of the same purpose first — only
        // one active code per (user, purpose) at a time.
        OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $length = (int) config('security.otp.length');
        $code = (string) random_int(
            (int) str_pad('1', $length, '0'),
            (int) str_pad('', $length, '9')
        );

        $otp = OtpCode::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'destination' => $this->mask($destination),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('security.otp.ttl_minutes')),
        ]);

        $this->send($destination, $code);

        return $otp;
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if (! $otp) {
            throw new RuntimeException('No active verification code found. Request a new one.');
        }

        if ($otp->expires_at->isPast()) {
            throw new RuntimeException('This code has expired. Request a new one.');
        }

        if ($otp->attempts >= (int) config('security.otp.max_attempts')) {
            throw new RuntimeException('Too many incorrect attempts. Request a new code.');
        }

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function send(string $destination, string $code): void
    {
        match (config('security.otp.driver')) {
            'log' => Log::channel(config('logging.default'))
                ->info("[OTP] Verification code for {$this->mask($destination)}: {$code}"),

            // TODO(ops): implement the 'unifonic' / 'msegat' cases here
            // against their respective SMS APIs once gateway credentials
            // exist — same method signature, no caller changes needed.
            default => throw new RuntimeException('Unsupported OTP driver: '.config('security.otp.driver')),
        };
    }

    private function mask(string $destination): string
    {
        if (str_contains($destination, '@')) {
            [$name, $domain] = explode('@', $destination, 2);

            return Str::substr($name, 0, 2).str_repeat('*', max(strlen($name) - 2, 1)).'@'.$domain;
        }

        return str_repeat('*', max(strlen($destination) - 4, 0)).substr($destination, -4);
    }
}
