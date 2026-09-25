<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment): void {
            if (is_array($attachment->original_name)) {
                $attachment->original_name = (string) reset($attachment->original_name);
            }

            $attachment->file_name ??= basename($attachment->file_path);
            $attachment->user_id ??= auth()->id();

            if (!$attachment->mime_type && $attachment->file_path) {
                $attachment->mime_type = Storage::disk('attachments')->mimeType($attachment->file_path);
            }
        });

        static::deleting(function (Attachment $attachment): void {
            Storage::disk('attachments')->delete($attachment->file_path);
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
