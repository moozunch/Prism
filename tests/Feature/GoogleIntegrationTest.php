<?php

use App\Models\Ticket;
use App\Services\GoogleCalendarService;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('isolates Google Calendar failures from core task handling', function () {
    Config::set('services.google.calendar.enabled', true);
    Config::set('services.google.calendar.access_token', 'test-token');
    Http::fake(fn () => Http::response(['error' => ['message' => 'Google unavailable']], 503));

    $task = new Ticket([
        'name' => 'Prepare schedule',
        'due_date' => '2026-09-30',
    ]);

    expect(app(GoogleCalendarService::class)->createTaskDeadline($task))->toBeNull();
});

it('isolates Google Drive failures and returns authorized file references on success', function () {
    Config::set('services.google.drive.enabled', true);
    Config::set('services.google.drive.access_token', 'test-token');
    Http::fake([
        'https://www.googleapis.com/drive/v3/files/failure' => Http::response([], 500),
        'https://www.googleapis.com/drive/v3/files/success*' => Http::response([
            'id' => 'success',
            'name' => 'Project brief.pdf',
            'mimeType' => 'application/pdf',
            'webViewLink' => 'https://drive.google.com/file/d/success/view',
        ]),
    ]);

    expect(app(GoogleDriveService::class)->getFileReference('failure'))->toBeNull()
        ->and(app(GoogleDriveService::class)->getFileReference('success')['name'])->toBe('Project brief.pdf');
});