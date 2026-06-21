<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
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
        return $this->hasMany(\Modules\Home\Models\Home::class, 'owner_id');
    }

    public function homeMembers(): HasMany
    {
        return $this->hasMany(\Modules\Home\Models\HomeMember::class);
    }

    public function aiAnalysisRequests(): HasMany
    {
        return $this->hasMany(\Modules\AI\Models\AiAnalysisRequest::class);
    }
}
