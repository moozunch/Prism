<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\GoogleConnection;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarService
{
    private const CALENDAR_SCOPE =
        'https://www.googleapis.com/auth/calendar.events';

    public function createTaskDeadline(Ticket $task): ?array
    {
        if (!config('services.google.calendar.enabled')) {
            return null;
        }

        $user = Auth::user();

        if (!$user instanceof User || !$task->due_date) {
            return null;
        }

        $connection = $user->googleConnection;

        if (
            !$connection ||
            !in_array(
                self::CALENDAR_SCOPE,
                $connection->scopes ?? [],
                true
            )
        ) {
            return null;
        }

        $emails = [];

        foreach ($task->assignees()->get() as $assignee) {
            $email = trim((string) $assignee->calendar_email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalized = strtolower($email);

            if (
                str_ends_with($normalized, '.test') ||
                str_ends_with($normalized, '.invalid') ||
                str_ends_with($normalized, '.example')
            ) {
                continue;
            }

            $emails[$normalized] = [
                'email' => $email,
                'displayName' => $assignee->name,
            ];
        }

        if (empty($emails)) {
            Log::info('Calendar sync skipped: no recipient email.', [
                'ticket_id' => $task->id,
            ]);

            return null;
        }

        try {
            $accessToken = $this->getAccessToken($connection);

            if (!$accessToken) {
                return null;
            }

            $calendarId = (string) config(
                'services.google.calendar.calendar_id',
                'primary'
            );

            $calendarEvent = GoogleCalendarEvent::firstOrCreate(
                ['ticket_id' => $task->id],
                [
                    'google_connection_id' => $connection->id,
                    'calendar_id' => $calendarId,
                    'sync_status' => 'pending',
                ]
            );

            if (
                (int) $calendarEvent->google_connection_id
                    !== (int) $connection->id
            ) {
                Log::warning(
                    'Calendar event belongs to another connection.',
                    ['ticket_id' => $task->id]
                );

                return null;
            }

            $calendarId = $calendarEvent->calendar_id;

            $attendees = [];

            $organizerEmail = strtolower(
                (string) $connection->google_email
            );

            foreach ($emails as $normalized => $attendee) {
                if ($normalized === $organizerEmail) {
                    continue;
                }

                $attendees[] = $attendee;
            }

            $task->loadMissing('project');

            $taskId = trim((string) $task->uuid);
            $taskName = trim((string) $task->name);

            $eventData = [
                'summary' => '[PRISM Task] '
                    . ($taskName !== '' ? $taskName : '-'),

                'description' => $this->buildEventDescription($task),

                'start' => [
                    'date' => $task->due_date->format('Y-m-d'),
                ],

                'end' => [
                    'date' => $task->due_date
                        ->copy()
                        ->addDay()
                        ->format('Y-m-d'),
                ],

                'attendees' => $attendees,

                'guestsCanModify' => false,
            ];

            $baseUrl =
                'https://www.googleapis.com/calendar/v3/calendars/'
                . rawurlencode($calendarId)
                . '/events';

            $request = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(20);

            if ($calendarEvent->google_event_id) {
                $eventId = $calendarEvent->google_event_id;

                $response = $request->patch(
                    $baseUrl
                    . '/'
                    . rawurlencode($eventId)
                    . '?sendUpdates=all',
                    $eventData
                );
            } else {
                $eventId = 'prism' . substr(
                    hash('sha256', (string) $task->uuid),
                    0,
                    32
                );

                $eventData['id'] = $eventId;

                $response = $request->post(
                    $baseUrl . '?sendUpdates=all',
                    $eventData
                );

                if ($response->status() === 409) {
                    unset($eventData['id']);

                    $response = Http::withToken($accessToken)
                        ->acceptJson()
                        ->timeout(20)
                        ->patch(
                            $baseUrl
                            . '/'
                            . rawurlencode($eventId)
                            . '?sendUpdates=all',
                            $eventData
                        );
                }
            }

            if (!$response->successful()) {
                $calendarEvent->update([
                    'sync_status' => 'failed',
                ]);

                Log::warning('Google Calendar sync failed.', [
                    'ticket_id' => $task->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $event = $response->json();

            if (
                !is_array($event) ||
                !is_string($event['id'] ?? null) ||
                $event['id'] === ''
            ) {
                $calendarEvent->update([
                    'sync_status' => 'failed',
                ]);

                return null;
            }

            $calendarEvent->update([
                'google_event_id' => $event['id'],
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);

            return $event;

        } catch (Throwable $exception) {
            Log::error('Google Calendar integration failed.', [
                'ticket_id' => $task->id,
                'exception_type' => get_class($exception),
            ]);

            return null;
        }
    }

    private function buildEventDescription(Ticket $task): string
    {
        $taskId = trim((string) $task->uuid);
        $taskName = trim((string) $task->name);
        $projectName = trim(
            (string) ($task->project?->name ?? '')
        );


        $rawDescription = (string) ($task->description ?? '');

        $rawDescription = preg_replace(
            '/<br\s*\/?>|<\/(?:p|div|li)>/i',
            "\n",
            $rawDescription
        ) ?? $rawDescription;

        $plainDescription = html_entity_decode(
            strip_tags($rawDescription),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $plainDescription = trim($plainDescription);

        // Rapikan spasi dan baris kosong.
        $plainDescription = preg_replace(
            '/[ \t]+/u',
            ' ',
            $plainDescription
        ) ?? $plainDescription;

        $plainDescription = preg_replace(
            '/\n{3,}/',
            "\n\n",
            $plainDescription
        ) ?? $plainDescription;

        return implode("\n", [
            'Task ID: ' . ($taskId !== '' ? $taskId : '-'),
            'Task Name: ' . ($taskName !== '' ? $taskName : '-'),
            'Project: ' . ($projectName !== '' ? $projectName : '-'),
            'Description: ' . (
                $plainDescription !== ''
                    ? $plainDescription
                    : '-'
            ),
        ]);
    }

    private function getAccessToken(
        GoogleConnection $connection
    ): ?string {
        if (
            $connection->access_token &&
            $connection->expires_at &&
            $connection->expires_at->greaterThan(
                now()->addMinute()
            )
        ) {
            return $connection->access_token;
        }

        if (!$connection->refresh_token) {
            return null;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' =>
                    config('services.google.client_id'),

                'client_secret' =>
                    config('services.google.client_secret'),

                'refresh_token' =>
                    $connection->refresh_token,

                'grant_type' => 'refresh_token',
            ]);

        if (!$response->successful()) {
            Log::warning('Google token refresh failed.', [
                'user_id' => $connection->user_id,
                'status' => $response->status(),
            ]);

            return null;
        }

        $newToken = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        if (
            !is_string($newToken) ||
            $newToken === '' ||
            !is_numeric($expiresIn) ||
            (int) $expiresIn <= 0
        ) {
            return null;
        }

        $connection->access_token = $newToken;

        $connection->expires_at = now()->addSeconds(
            (int) $expiresIn
        );

        $newRefreshToken = $response->json('refresh_token');

        if (
            is_string($newRefreshToken) &&
            $newRefreshToken !== ''
        ) {
            $connection->refresh_token = $newRefreshToken;
        }

        $connection->save();

        return $newToken;
    }
}