<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleDriveService
{
    public function getFileReference(string $fileId): ?array
    {
        if (!config('services.google.drive.enabled')) {
            return null;
        }

        $accessToken = config('services.google.drive.access_token');

        if (!$accessToken || trim($fileId) === '') {
            Log::warning('Google Drive is not configured for file reference integration.');

            return null;
        }

        try {
            $file = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId), [
                    'fields' => 'id,name,mimeType,webViewLink',
                ])
                ->throw()
                ->json();

            return is_array($file) ? $file : null;
        } catch (Throwable $exception) {
            Log::error('Google Drive file reference integration failed.', [
                'file_id' => $fileId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}