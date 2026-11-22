<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\GoogleConnection;
use App\Models\Project;
use App\Models\Ticket;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveService
{
    private const DRIVE_SCOPE =
        'https://www.googleapis.com/auth/drive.file';

    private const ROOT_FOLDER_NAME = 'PRISM Attachments';

    private const API_URL =
        'https://www.googleapis.com/drive/v3/files';

    private const UPLOAD_API_URL =
        'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * Find or create the centralized Drive folder.
     */
    public function initializeRootFolder(): array
    {
        $connection = $this->getOrganizerConnection();
        $token = $this->getAccessToken($connection);

        $request = Http::withToken($token)
            ->acceptJson()
            ->timeout(30);

        $search = $request->get(self::API_URL, [
            'q' => "name = 'PRISM Attachments' "
                . "and mimeType = 'application/vnd.google-apps.folder' "
                . "and 'root' in parents "
                . "and trashed = false",
            'fields' => 'nextPageToken,files(id,name)',
            'pageSize' => 100,
        ]);

        if (!$search->successful()) {
            throw new RuntimeException(
                'Google Drive folder search failed. HTTP '
                . $search->status()
            );
        }

        $files = $search->json('files');

        if (!is_array($files)) {
            throw new RuntimeException(
                'Invalid Google Drive folder search response.'
            );
        }

        foreach ($files as $file) {
            if (
                is_array($file)
                && is_string($file['id'] ?? null)
                && $file['id'] !== ''
            ) {
                return [
                    'folder_id' => $file['id'],
                    'created' => false,
                ];
            }
        }

        $response = $request->post(
            self::API_URL . '?fields=id,name',
            [
                'name' => self::ROOT_FOLDER_NAME,
                'mimeType' =>
                    'application/vnd.google-apps.folder',
            ]
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google Drive folder creation failed. HTTP '
                . $response->status()
            );
        }

        $folderId = $response->json('id');

        if (!is_string($folderId) || $folderId === '') {
            throw new RuntimeException(
                'Google Drive did not return a folder ID.'
            );
        }

        return [
            'folder_id' => $folderId,
            'created' => true,
        ];
    }

    /**
     * Upload a private attachment to Google Drive.
     *
     * If a Task is supplied, store the file inside:
     * PRISM Attachments / Project / Task
     *
     * If a Project is supplied, store it inside:
     * PRISM Attachments / Project
     *
     * Without an owner, preserve the previous root
     * folder behavior for backward compatibility.
     */
    public function uploadAttachment(
        string $filePath,
        string $originalName,
        Project|Ticket|null $owner = null
    ): array {
        if (!config('services.google.drive.enabled')) {
            throw new RuntimeException(
                'Google Drive integration is disabled.'
            );
        }

        $rootFolderId = trim(
            (string) config(
                'services.google.drive.root_folder_id',
                ''
            )
        );

        if ($rootFolderId === '') {
            throw new RuntimeException(
                'Google Drive root folder is not configured.'
            );
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('attachments');

        if (!$disk->exists($filePath)) {
            throw new RuntimeException(
                'Attachment file was not found in local storage.'
            );
        }

        $connection = $this->getOrganizerConnection();
        $token = $this->getAccessToken($connection);

        /*
         * NEW: Determine the destination folder.
         */
        $folderService = app(
            GoogleDriveFolderService::class
        );

        if ($owner instanceof Ticket) {
            $folderId = $folderService->folderForTask(
                $owner,
                $token
            );
        } elseif ($owner instanceof Project) {
            $folderId = $folderService->folderForProject(
                $owner,
                $token
            );
        } else {
            $folderId = $rootFolderId;
        }

        $fileSize = $disk->size($filePath);

        if (!is_int($fileSize) || $fileSize < 0) {
            throw new RuntimeException(
                'Unable to determine attachment file size.'
            );
        }

        $mimeType = $disk->mimeType($filePath)
            ?: 'application/octet-stream';

        $name = basename(
            str_replace('\\', '/', $originalName)
        );

        if (
            $name === ''
            || $name === '.'
            || $name === '..'
        ) {
            $name = basename($filePath);
        }

        /*
         * STEP 1:
         * Initialize a resumable upload with the
         * selected Project or Task folder as parent.
         */
        $initResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->withHeaders([
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' =>
                    (string) $fileSize,
            ])
            ->post(
                self::UPLOAD_API_URL
                    . '?uploadType=resumable'
                    . '&fields=id,name,mimeType,webViewLink',
                [
                    'name' => $name,
                    'mimeType' => $mimeType,
                    'parents' => [$folderId],
                ]
            );

        if (!$initResponse->successful()) {
            throw new RuntimeException(
                'Google Drive upload initialization failed. HTTP '
                . $initResponse->status()
            );
        }

        $sessionUrl = $initResponse->header('Location');

        if (
            !is_string($sessionUrl)
            || $sessionUrl === ''
        ) {
            throw new RuntimeException(
                'Google Drive did not provide an upload session.'
            );
        }

        /*
         * Validate the upload session URL.
         */
        $scheme = parse_url(
            $sessionUrl,
            PHP_URL_SCHEME
        );

        $host = parse_url(
            $sessionUrl,
            PHP_URL_HOST
        );

        if (
            $scheme !== 'https'
            || !is_string($host)
            || (
                $host !== 'googleapis.com'
                && !str_ends_with(
                    $host,
                    '.googleapis.com'
                )
            )
        ) {
            throw new RuntimeException(
                'Invalid Google Drive upload session URL.'
            );
        }

        /*
         * STEP 2:
         * Stream the attachment to Google Drive.
         */
        $handle = $disk->readStream($filePath);

        if (!is_resource($handle)) {
            throw new RuntimeException(
                'Unable to read the attachment file.'
            );
        }

        try {
            $stream = Utils::streamFor($handle);

            $uploadResponse = Http::withToken($token)
                ->acceptJson()
                ->withoutRedirecting()
                ->timeout(120)
                ->withHeaders([
                    'Content-Length' =>
                        (string) $fileSize,
                ])
                ->withBody(
                    $stream,
                    $mimeType
                )
                ->put($sessionUrl);

        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if (!$uploadResponse->successful()) {
            throw new RuntimeException(
                'Google Drive file upload failed. HTTP '
                . $uploadResponse->status()
            );
        }

        $fileId = $uploadResponse->json('id');

        if (
            !is_string($fileId)
            || $fileId === ''
        ) {
            throw new RuntimeException(
                'Google Drive did not return a file ID.'
            );
        }

        $webViewLink = $uploadResponse->json(
            'webViewLink'
        );

        if (
            !is_string($webViewLink)
            || $webViewLink === ''
        ) {
            $webViewLink =
                'https://drive.google.com/file/d/'
                . rawurlencode($fileId)
                . '/view';
        }

        return [
            'file_id' => $fileId,
            'url' => $webViewLink,
            'mime_type' =>
                $uploadResponse->json('mimeType')
                    ?: $mimeType,
            'name' => $name,
            'folder_id' => $folderId,
        ];
    }

    /**
     * Preview a Google Drive attachment through PRISM.
     *
     * The controller must authorize the user first.
     */
    public function previewAttachment(
        Attachment $attachment
    ): StreamedResponse {
        if (!$attachment->isGoogleDrive()) {
            throw new RuntimeException(
                'Attachment is not stored in Google Drive.'
            );
        }

        if (!config('services.google.drive.enabled')) {
            throw new RuntimeException(
                'Google Drive integration is disabled.'
            );
        }

        $fileId = trim(
            (string) $attachment->google_drive_file_id
        );

        if ($fileId === '') {
            throw new RuntimeException(
                'Google Drive file ID is missing.'
            );
        }

        $connection = $this->getOrganizerConnection();
        $token = $this->getAccessToken($connection);

        $response = Http::withToken($token)
            ->withOptions([
                'stream' => true,
            ])
            ->timeout(120)
            ->get(
                self::API_URL
                    . '/'
                    . rawurlencode($fileId),
                [
                    'alt' => 'media',
                ]
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google Drive preview failed. HTTP '
                . $response->status()
            );
        }

        $allowedInlineMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        $mimeType = strtolower(
            trim(
                (string) (
                    $attachment->mime_type
                    ?: 'application/octet-stream'
                )
            )
        );

        $canPreviewInline = in_array(
            $mimeType,
            $allowedInlineMimeTypes,
            true
        );

        if (!$canPreviewInline) {
            $mimeType = 'application/octet-stream';
        }

        $disposition = $canPreviewInline
            ? 'inline'
            : 'attachment';

        $fileName = basename(
            str_replace(
                '\\',
                '/',
                (string) $attachment->original_name
            )
        );

        if ($fileName === '' || $fileName === '.') {
            $fileName = 'attachment';
        }

        $fallbackName = preg_replace(
            '/[^\x20-\x7E]/',
            '_',
            $fileName
        ) ?: 'attachment';

        $fallbackName = str_replace(
            ['%', '/', '\\'],
            '_',
            $fallbackName
        );

        $headers = [
            'Content-Type' => $mimeType,

            'Content-Disposition' =>
                HeaderUtils::makeDisposition(
                    $disposition,
                    $fileName,
                    $fallbackName
                ),

            'Cache-Control' => 'private, no-store',

            'X-Content-Type-Options' => 'nosniff',
        ];

        $body = $response
            ->toPsrResponse()
            ->getBody();

        return response()->stream(
            function () use ($body): void {
                try {
                    while (!$body->eof()) {
                        $chunk = $body->read(65536);

                        if ($chunk === '') {
                            break;
                        }

                        echo $chunk;
                        flush();
                    }
                } finally {
                    $body->close();
                }
            },
            200,
            $headers
        );
    }

    public function getFileReference(string $fileId): ?array
    {
        $fileId = trim($fileId);

        if (
            !config('services.google.drive.enabled')
            || $fileId === ''
        ) {
            return null;
        }

        try {
            $connection = $this->getOrganizerConnection();
            $token = $this->getAccessToken($connection);

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get(
                    self::API_URL . '/' . rawurlencode($fileId),
                    [
                        'fields' => 'id,name,mimeType,webViewLink,trashed',
                    ]
                );

            if (!$response->successful()) {
                return null;
            }

            $file = $response->json();

            if (
                !is_array($file)
                || !is_string($file['id'] ?? null)
                || $file['id'] === ''
                || !is_string($file['name'] ?? null)
                || ($file['trashed'] ?? false) === true
            ) {
                return null;
            }

            return $file;

        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning(
                'Google Drive file reference lookup failed.',
                [
                    'exception_type' => get_class($exception),
                ]
            );

            return null;
        }
    }

    private function getOrganizerConnection(): GoogleConnection
    {
        $userId = (int) config(
            'services.google.drive.organizer_user_id',
            0
        );

        if ($userId <= 0) {
            throw new RuntimeException(
                'Google Drive organizer is not configured.'
            );
        }

        $connection = GoogleConnection::query()
            ->where('user_id', $userId)
            ->first();

        if (!$connection) {
            throw new RuntimeException(
                'Google Drive organizer connection was not found.'
            );
        }

        if (!in_array(
            self::DRIVE_SCOPE,
            $connection->scopes ?? [],
            true
        )) {
            throw new RuntimeException(
                'Google Drive permission has not been granted.'
            );
        }

        return $connection;
    }

    /**
     * Obtain or refresh the Google access token.
     */
    private function getAccessToken(
        GoogleConnection $connection
    ): string {
        if (
            $connection->access_token
            && $connection->expires_at
            && $connection->expires_at->greaterThan(
                now()->addMinute()
            )
        ) {
            return $connection->access_token;
        }

        if (!$connection->refresh_token) {
            throw new RuntimeException(
                'Google refresh token is unavailable.'
            );
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post(
                'https://oauth2.googleapis.com/token',
                [
                    'client_id' =>
                        config('services.google.client_id'),

                    'client_secret' =>
                        config('services.google.client_secret'),

                    'refresh_token' =>
                        $connection->refresh_token,

                    'grant_type' => 'refresh_token',
                ]
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google token refresh failed. HTTP '
                . $response->status()
            );
        }

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        if (
            !is_string($token)
            || $token === ''
            || !is_numeric($expiresIn)
            || (int) $expiresIn <= 0
        ) {
            throw new RuntimeException(
                'Invalid Google token refresh response.'
            );
        }

        $connection->access_token = $token;

        $connection->expires_at = now()->addSeconds(
            (int) $expiresIn
        );

        $newRefreshToken = $response->json(
            'refresh_token'
        );

        if (
            is_string($newRefreshToken)
            && $newRefreshToken !== ''
        ) {
            $connection->refresh_token = $newRefreshToken;
        }

        $connection->save();

        return $token;
    }
}