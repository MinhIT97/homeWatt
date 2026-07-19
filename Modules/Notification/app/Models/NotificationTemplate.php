<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'channels',
        'mail_subject',
        'mail_body',
        'telegram_body',
        'push_title',
        'push_body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
