<?php

use App\Models\GoogleConnection;
use App\Models\Ticket;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('isolates Google Calendar failures from core task handling', function () {
    Config::set('services.google.calendar.enabled', true);
    Config::set('services.google.calendar.access_token', 'test-token');

    Http::fake(
        fn () => Http::response([
            'error' => [
                'message' => 'Google unavailable',
            ],
        ], 503)
    );

    $task = new Ticket([
        'name' => 'Prepare schedule',
        'due_date' => '2026-09-30',
    ]);

    expect(
        app(GoogleCalendarService::class)
            ->createTaskDeadline($task)
    )->toBeNull();
});

it('isolates Google Drive failures and returns authorized file references on success', function () {
    /*
     * Create a PRISM user acting as the centralized
     * Google Drive organizer.
     */
    $organizer = User::factory()->create();

    /*
     * Configure Google Drive to use that organizer.
     */
    Config::set('services.google.drive.enabled', true);

    Config::set(
        'services.google.drive.organizer_user_id',
        $organizer->id
    );

    /*
     * Create a test Google connection.
     *
     * Laravel handles token encryption automatically
     * through the GoogleConnection model casts.
     */
    GoogleConnection::create([
        'user_id' => $organizer->id,
        'google_account_id' => 'test-google-account',
        'google_email' => 'organizer@example.com',
        'access_token' => 'test-token',
        'refresh_token' => 'test-refresh-token',
        'expires_at' => now()->addHour(),
        'scopes' => [
            'https://www.googleapis.com/auth/drive.file',
        ],
    ]);

    /*
     * Simulate Google Drive API responses.
     * No real Google API request is required.
     */
    Http::fake([
        'https://www.googleapis.com/drive/v3/files/failure*' =>
            Http::response([
                'error' => [
                    'message' => 'Google unavailable',
                ],
            ], 500),

        'https://www.googleapis.com/drive/v3/files/success*' =>
            Http::response([
                'id' => 'success',
                'name' => 'Project brief.pdf',
                'mimeType' => 'application/pdf',
                'webViewLink' =>
                    'https://drive.google.com/file/d/success/view',
                'trashed' => false,
            ], 200),
    ]);

    /*
     * The service should handle Google API failure
     * without breaking the application.
     */
    expect(
        app(GoogleDriveService::class)
            ->getFileReference('failure')
    )->toBeNull();

    /*
     * A successful response should return file metadata.
     */
    $file = app(GoogleDriveService::class)
        ->getFileReference('success');

    expect($file)
        ->toBeArray()
        ->and($file['name'])
        ->toBe('Project brief.pdf');

    /*
     * Verify that requests use the organizer token.
     */
    Http::assertSent(
        fn ($request): bool =>
            str_contains(
                $request->url(),
                '/drive/v3/files/success'
            )
            && $request->hasHeader(
                'Authorization',
                'Bearer test-token'
            )
    );
});