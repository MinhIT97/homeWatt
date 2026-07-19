<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function homes(): HasMany
    {
        return $this->hasMany(Home::class, 'owner_id');
    }

    public function homeMembers(): HasMany
    {
        return $this->hasMany(HomeMember::class);
    }

    public function aiAnalysisRequests(): HasMany
    {
        return $this->hasMany(AiAnalysisRequest::class);
    }

    /**
     * Check if the user has two-factor authentication fully set up and confirmed.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get the decrypted two-factor recovery codes.
     *
     * @return array<int, string>
     */
    public function twoFactorRecoveryCodes(): array
    {
        if (empty($this->two_factor_recovery_codes)) {
            return [];
        }

        return json_decode($this->two_factor_recovery_codes, true) ?? [];
    }
}
