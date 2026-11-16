<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\GoogleDriveService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function view(
        Attachment $attachment,
        GoogleDriveService $googleDriveService
    ): Response {
        $this->authorize('view', $attachment);

        if ($attachment->isGoogleDrive()) {
            abort_unless(
                filled($attachment->google_drive_file_id),
                404
            );

            try {
                return $googleDriveService->previewAttachment(
                    $attachment
                );
            } catch (RuntimeException $exception) {
                Log::warning(
                    'Google Drive attachment preview failed.',
                    [
                        'attachment_id' => $attachment->id,
                        'exception_type' => get_class($exception),
                    ]
                );

                abort(
                    502,
                    'Unable to preview this Google Drive attachment.'
                );
            }
        }

        abort_unless($attachment->isLocal(), 404);

        /**
         * Tell Intelephense the exact filesystem type.
         *
         * FilesystemAdapter supports response().
         *
         * @var FilesystemAdapter $disk
         */
        $disk = Storage::disk('attachments');

        abort_unless(
            $disk->exists($attachment->file_path),
            404
        );

        return $disk->response(
            $attachment->file_path,
            $attachment->original_name,
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }
}