<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'disk',
        'path',
        'mime_type',
        'size',
        'checksum',
        'status',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function url(): string
    {
        return route('media.serve', $this);
    }
}
