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

#[Fillable(['name', 'email', 'password', 'telegram_chat_id', 'telegram_verification_code'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
}
