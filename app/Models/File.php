<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FileStatus;
use App\Enums\FileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'original_name',
        'file_name',
        'disk',
        'mime_type',
        'extension',
        'file_type',
        'size',
        'status',
        'attachable_type',
        'attachable_id',
    ];

    protected function casts(): array
    {
        return [
            'file_type' => FileType::class,
            'status' => FileStatus::class,
            'size' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->file_name);
    }

    public function isAttached(): bool
    {
        return $this->status === FileStatus::Attached
            && $this->attachable_type !== null
            && $this->attachable_id !== null;
    }
}
