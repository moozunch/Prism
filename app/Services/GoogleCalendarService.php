<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarService
{
    public function createTaskDeadline(Ticket $task): ?array
    {
        if (!config('services.google.calendar.enabled')) {
            return null;
        }

        $accessToken = config('services.google.calendar.access_token');

        if (!$accessToken || !$task->due_date) {
            Log::warning('Google Calendar is not configured for task deadline integration.', [
                'ticket_id' => $task->id,
            ]);

            return null;
        }

        try {
            $event = Http::withToken($accessToken)
                ->acceptJson()
                ->post('https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode((string) config('services.google.calendar.calendar_id', 'primary')) . '/events', [
                    'summary' => '[PRISM Task] ' . $task->name,
                    'description' => $task->description,
                    'start' => ['date' => $task->due_date->format('Y-m-d')],
                    'end' => ['date' => $task->due_date->copy()->addDay()->format('Y-m-d')],
                ])
                ->throw()
                ->json();

            return is_array($event) ? $event : null;
        } catch (Throwable $exception) {
            Log::error('Google Calendar task deadline integration failed.', [
                'ticket_id' => $task->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}