<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\GoogleConnection;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarService
{
    private const CALENDAR_SCOPE =
        'https://www.googleapis.com/auth/calendar.events';

    /**
     * Manual synchronization.
     * Uses the connected Google account of the current user.
     */
    public function createTaskDeadline(Ticket $task): ?array
    {
        if (!config('services.google.calendar.enabled')) {
            return null;
        }

        $user = Auth::user();

        if (!$user instanceof User) {
            return null;
        }

        $connection = $user->googleConnection;

        if (!$connection) {
            return null;
        }

        return $this->syncWithConnection($task, $connection);
    }

    /**
     * Automatic synchronization.
     * Works without an authenticated browser session.
     */
    public function syncTaskAutomatically(int $ticketId): ?array
    {
        if (
            !config('services.google.calendar.enabled') ||
            !config('services.google.calendar.auto_sync_enabled')
        ) {
            return null;
        }

        $task = Ticket::with([
            'creator.googleConnection',
            'googleCalendarEvent.googleConnection',
        ])->find($ticketId);

        if (!$task) {
            return null;
        }

        $savedEvent = $task->googleCalendarEvent;

        if ($savedEvent) {
            // Existing event must keep its original organizer.
            $connection = $savedEvent->googleConnection;
        } else {
            $organizerUserId = (int) config(
                'services.google.calendar.organizer_user_id',
                0
            );

            if ($organizerUserId > 0) {
                // Use the configured Google organizer.
                $connection = GoogleConnection::query()
                    ->where('user_id', $organizerUserId)
                    ->first();
            } else {
                // Backward compatibility for existing integrations
                // and tests without a configured organizer.
                $connection = $task->creator?->googleConnection;
            }
        }

        if (!$connection) {
            Log::warning(
                'Calendar auto sync skipped: organizer unavailable.',
                ['ticket_id' => $ticketId]
            );

            return null;
        }

        return $this->syncWithConnection(
            $task,
            $connection
        );
    }

    /**
     * Create, update, or cancel a task Calendar event.
     */
    private function syncWithConnection(
        Ticket $task,
        GoogleConnection $connection
    ): ?array {
        if (!$task->due_date) {
            return $this->cancelTaskDeadline(
                $task,
                $connection
            );
        }

        if (!in_array(
            self::CALENDAR_SCOPE,
            $connection->scopes ?? [],
            true
        )) {
            Log::warning(
                'Calendar sync skipped: missing Google scope.',
                ['ticket_id' => $task->id]
            );

            return null;
        }

        $calendarEvent = null;

        try {
            $attendeesByEmail = [];
            $hasValidRecipient = false;

            $organizerEmail = strtolower(
                trim((string) $connection->google_email)
            );

            foreach ($task->assignees()->get() as $assignee) {
                $email = trim(
                    (string) $assignee->calendar_email
                );

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

                $hasValidRecipient = true;

                if ($normalized === $organizerEmail) {
                    continue;
                }

                $attendeesByEmail[$normalized] = [
                    'email' => $email,
                    'displayName' => $assignee->name,
                ];
            }

            $savedEvent = $task
                ->googleCalendarEvent()
                ->first();

            if (!$hasValidRecipient && !$savedEvent) {
                Log::info(
                    'Calendar sync skipped: no valid recipient.',
                    ['ticket_id' => $task->id]
                );

                return null;
            }

            $accessToken = $this->getAccessToken($connection);

            if (!$accessToken) {
                Log::warning(
                    'Calendar sync skipped: access token unavailable.',
                    ['ticket_id' => $task->id]
                );

                return null;
            }

            $calendarId = (string) config(
                'services.google.calendar.calendar_id',
                'primary'
            );

            if ($calendarId === '') {
                $calendarId = 'primary';
            }

            $calendarEvent = GoogleCalendarEvent::firstOrCreate(
                [
                    'ticket_id' => $task->id,
                ],
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
                    'Calendar sync skipped: organizer mismatch.',
                    ['ticket_id' => $task->id]
                );

                return null;
            }

            $calendarId = $calendarEvent->calendar_id;

            $task->loadMissing('project');

            $eventData = [
                'summary' => '[PRISM Task] ' . $task->name,

                'description' => $this->buildEventDescription(
                    $task
                ),

                'start' => [
                    'date' => $task->due_date->format('Y-m-d'),
                ],

                'end' => [
                    'date' => $task->due_date
                        ->copy()
                        ->addDay()
                        ->format('Y-m-d'),
                ],

                'attendees' => array_values(
                    $attendeesByEmail
                ),

                'guestsCanModify' => false,
            ];

            $baseUrl =
                'https://www.googleapis.com/calendar/v3/calendars/'
                . rawurlencode((string) $calendarId)
                . '/events';

            $request = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(20);

            if ($calendarEvent->google_event_id) {
                // UPDATE existing event.
                $eventId = $calendarEvent->google_event_id;

                $response = $request->patch(
                    $baseUrl
                    . '/'
                    . rawurlencode($eventId)
                    . '?sendUpdates=all',
                    $eventData
                );
            } else {
                // CREATE new event using a stable event ID.
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
                    // Google already has this event.
                    // Update instead of inserting a duplicate.
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

                Log::warning(
                    'Google Calendar sync failed.',
                    [
                        'ticket_id' => $task->id,
                        'status' => $response->status(),
                    ]
                );

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
            if ($exception instanceof ConnectionException) {
                $calendarEvent?->update(['sync_status' => 'failed']);
            }

            Log::error(
                'Google Calendar integration failed.',
                [
                    'ticket_id' => $task->id,
                    'exception_type' => get_class($exception),
                ]
            );

            return null;
        }
    }

    /**
     * Cancel the event when the task deadline is removed.
     */
    private function cancelTaskDeadline(
        Ticket $task,
        GoogleConnection $connection
    ): ?array {
        $savedEvent = $task
            ->googleCalendarEvent()
            ->first();

        if (!$savedEvent || !$savedEvent->google_event_id) {
            return null;
        }

        if (
            (int) $savedEvent->google_connection_id
            !== (int) $connection->id
        ) {
            Log::warning(
                'Calendar cancellation skipped: organizer mismatch.',
                ['ticket_id' => $task->id]
            );

            return null;
        }

        if (!in_array(
            self::CALENDAR_SCOPE,
            $connection->scopes ?? [],
            true
        )) {
            return null;
        }

        try {
            $accessToken = $this->getAccessToken($connection);

            if (!$accessToken) {
                $savedEvent->update([
                    'sync_status' => 'failed',
                ]);

                return null;
            }

            $url =
                'https://www.googleapis.com/calendar/v3/calendars/'
                . rawurlencode((string) $savedEvent->calendar_id)
                . '/events/'
                . rawurlencode((string) $savedEvent->google_event_id)
                . '?sendUpdates=all';

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(20)
                ->delete($url);

            if (
                !$response->successful()
                && !in_array(
                    $response->status(),
                    [404, 410],
                    true
                )
            ) {
                $savedEvent->update([
                    'sync_status' => 'failed',
                ]);

                Log::warning(
                    'Google Calendar cancellation failed.',
                    [
                        'ticket_id' => $task->id,
                        'status' => $response->status(),
                    ]
                );

                return null;
            }

            $savedEvent->update([
                'google_event_id' => null,
                'sync_status' => 'cancelled',
                'last_synced_at' => now(),
            ]);

            return [
                'status' => 'cancelled',
            ];

        } catch (Throwable $exception) {
            Log::error(
                'Google Calendar cancellation exception.',
                [
                    'ticket_id' => $task->id,
                    'exception_type' => get_class($exception),
                ]
            );

            return null;
        }
    }

    /**
     * Build the Google Calendar event description.
     */
    private function buildEventDescription(Ticket $task): string
    {
        $description = (string) ($task->description ?? '');

        $description = preg_replace(
            '/<br\s*\/?>|<\/(?:p|div|li)>/i',
            "\n",
            $description
        ) ?? $description;

        $description = html_entity_decode(
            strip_tags($description),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $description = trim($description);

        $description = preg_replace(
            '/[ \t]+/u',
            ' ',
            $description
        ) ?? $description;

        $description = preg_replace(
            '/\n{3,}/',
            "\n\n",
            $description
        ) ?? $description;

        $taskId = trim((string) $task->uuid);
        $taskName = trim((string) $task->name);

        $projectName = trim(
            (string) ($task->project?->name ?? '')
        );

        return implode("\n", [
            'Task ID: ' . ($taskId !== '' ? $taskId : '-'),
            'Task Name: ' . ($taskName !== '' ? $taskName : '-'),
            'Project: ' . ($projectName !== '' ? $projectName : '-'),
            'Description: ' . (
                $description !== '' ? $description : '-'
            ),
        ]);
    }

    /**
     * Get a valid Google access token.
     */
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
            Log::warning(
                'Google token refresh failed.',
                [
                    'user_id' => $connection->user_id,
                    'status' => $response->status(),
                ]
            );

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