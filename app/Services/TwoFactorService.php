<?php

namespace App\Services;

use App\Models\User;

class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    private const RECOVERY_CODE_LENGTH = 10;

    private const TOTP_PERIOD = 30;

    private const TOTP_DIGITS = 6;

    private const TOTP_ALGORITHM = 'sha1';

    /**
     * Generate a random 32-byte secret encoded in Base32.
     */
    public function generateSecret(): string
    {
        $random = random_bytes(20); // 20 bytes = 160 bits

        return $this->base32Encode($random);
    }

    /**
     * Generate recovery codes for when the user loses access to their authenticator app.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = $this->generateRecoveryCode();
        }

        return $codes;
    }

    /**
     * Generate the otpauth:// URL for use in a QR code.
     */
    public function generateQrCodeUrl(User $user, string $secret): string
    {
        $issuer = urlencode(config('app.name', 'homeWatt'));
        $label = urlencode($user->email);

        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verify a TOTP code against the given secret.
     */
    public function verify(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / self::TOTP_PERIOD);

        // Check current and adjacent time windows (drift tolerance of 1)
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals($this->generateTotp($secret, (int) ($timeSlice + $i)), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable 2FA for the given user.
     * Saves the secret and recovery codes, returns the plain-text recovery codes.
     *
     * @return array<int, string>
     */
    public function enable(User $user, string $secret): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode(array_map(
            fn (string $code) => hash('sha256', $code),
            $recoveryCodes,
        ));
        $user->save();

        return $recoveryCodes;
    }

    /**
     * Disable 2FA for the given user.
     * Clears all 2FA-related fields.
     */
    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    /**
     * Mark the user's 2FA setup as confirmed.
     */
    public function confirm(User $user): void
    {
        $user->two_factor_confirmed_at = now();
        $user->save();
    }

    /**
     * Verify a recovery code and invalidate it upon use.
     *
     * @return bool True if the code was valid (and consumed).
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_recovery_codes)) {
            return false;
        }

        $hashedCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
        $hashedInput = hash('sha256', $code);

        foreach ($hashedCodes as $index => $hashedCode) {
            if (hash_equals($hashedCode, $hashedInput)) {
                // Remove the used code
                unset($hashedCodes[$index]);
                $user->two_factor_recovery_codes = json_encode(array_values($hashedCodes));
                $user->save();

                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------
    // Internal TOTP implementation (RFC 6238)
    // ---------------------------------------------------------------

    /**
     * Generate a TOTP code for a given time slice.
     */
    private function generateTotp(string $secret, int $timeSlice): string
    {
        $secret = $this->base32Decode($secret);
        $time = pack('J', $timeSlice);
        $hash = hash_hmac(self::TOTP_ALGORITHM, $time, $secret, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $code = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code %= 10 ** self::TOTP_DIGITS;

        return str_pad((string) $code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Encode binary data as Base32 (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $result .= $alphabet[bindec($chunk)];
        }

        // Pad to a multiple of 8
        while (strlen($result) % 8 !== 0) {
            $result .= '=';
        }

        return $result;
    }

    /**
     * Decode a Base32 string (RFC 4648).
     */
    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = rtrim($secret, '=');
        $binary = '';

        foreach (str_split($secret) as $char) {
            $value = strpos($alphabet, strtoupper($char));
            if ($value === false) {
                continue;
            }
            $binary .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $result .= chr(bindec($byte));
        }

        return $result;
    }

    /**
     * Generate a single random recovery code.
     */
    private function generateRecoveryCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';

        for ($i = 0; $i < self::RECOVERY_CODE_LENGTH; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }
}
