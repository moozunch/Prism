<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Attachment extends Model
{
    protected $fillable = [
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'user_id',
        'storage_provider',
        'google_drive_file_id',
        'google_drive_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment): void {
            if (is_array($attachment->original_name)) {
                $attachment->original_name = (string) reset(
                    $attachment->original_name
                );
            }

            $attachment->storage_provider ??= 'local';

            $attachment->file_name ??= basename(
                (string) $attachment->file_path
            );

            $attachment->user_id ??= Auth::id();

            if (
                !$attachment->mime_type
                && $attachment->file_path
                && $attachment->isLocal()
            ) {
                /** @var FilesystemAdapter $disk */
                $disk = Storage::disk('attachments');

                $attachment->mime_type = $disk->mimeType(
                    $attachment->file_path
                );
            }
        });

        static::created(function (Attachment $attachment): void {
            if (
                !$attachment->isGoogleDrive()
                || !filled($attachment->google_drive_file_id)
            ) {
                return;
            }

            self::cleanupStagedFile($attachment);
        });

        static::deleting(function (Attachment $attachment): void {
            if (
                $attachment->isLocal()
                && filled($attachment->file_path)
            ) {
                Storage::disk('attachments')->delete(
                    $attachment->file_path
                );
            }
        });

        static::deleted(function (Attachment $attachment): void {
            if ($attachment->isGoogleDrive()) {
                self::cleanupStagedFile($attachment);
            }
        });
    }

    private static function cleanupStagedFile(
        Attachment $attachment
    ): void {
        $filePath = $attachment->file_path;

        if (
            !is_string($filePath)
            || !str_starts_with($filePath, 'uploads/')
            || str_contains($filePath, '..')
            || str_contains($filePath, '\\')
        ) {
            return;
        }

        $attachmentId = $attachment->getKey();

        $cleanup = static function () use (
            $filePath,
            $attachmentId
        ): void {
            try {
                $disk = Storage::disk('attachments');

                if (!$disk->exists($filePath)) {
                    return;
                }

                if (!$disk->delete($filePath)) {
                    Log::warning(
                        'Google Drive attachment cleanup failed.',
                        [
                            'attachment_id' => $attachmentId,
                            'file_path' => $filePath,
                        ]
                    );
                }
            } catch (Throwable $exception) {
                Log::warning(
                    'Google Drive attachment cleanup exception.',
                    [
                        'attachment_id' => $attachmentId,
                        'exception_type' =>
                            get_class($exception),
                    ]
                );
            }
        };

        $connection = $attachment->getConnection();

        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($cleanup);
        } else {
            $cleanup();
        }
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGoogleDrive(): bool
    {
        return $this->storage_provider === 'google_drive';
    }

    public function isLocal(): bool
    {
        return $this->storage_provider === 'local';
    }
}