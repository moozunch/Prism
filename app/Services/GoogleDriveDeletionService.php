<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\GoogleConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveDeletionService
{
    private const DRIVE_SCOPE =
        'https://www.googleapis.com/auth/drive.file';

    private const API_URL =
        'https://www.googleapis.com/drive/v3/files';

    public function moveToTrash(Attachment $attachment): void
    {
        if (!$attachment->isGoogleDrive()) {
            throw new RuntimeException(
                'This attachment is not stored in Google Drive.'
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

        $organizerUserId = (int) config(
            'services.google.drive.organizer_user_id',
            0
        );

        if ($organizerUserId <= 0) {
            throw new RuntimeException(
                'Google Drive organizer is not configured.'
            );
        }

        $connection = GoogleConnection::query()
            ->where('user_id', $organizerUserId)
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

        $accessToken = $this->getAccessToken($connection);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->patch(
                self::API_URL
                    . '/'
                    . rawurlencode($fileId)
                    . '?fields=id,trashed',
                [
                    'trashed' => true,
                ]
            );

        if (
            !$response->successful()
            || $response->json('trashed') !== true
        ) {
            throw new RuntimeException(
                'Google Drive deletion failed. HTTP '
                . $response->status()
            );
        }

    }

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