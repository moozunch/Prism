<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function view(Attachment $attachment): Response
    {
        $this->authorize('view', $attachment);

        abort_unless(Storage::disk('attachments')->exists($attachment->file_path), 404);

        return Storage::disk('attachments')->response(
            $attachment->file_path,
            $attachment->original_name,
            [
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ],
        );
    }
}
