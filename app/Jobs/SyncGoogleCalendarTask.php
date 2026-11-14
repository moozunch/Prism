<?php

namespace App\Jobs;

use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleCalendarTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public int $ticketId
    ) {
        $this->afterCommit();
    }

    public function handle(
        GoogleCalendarService $calendarService
    ): void {
        if (!config('services.google.calendar.enabled')) {
            return;
        }

        if (!config('services.google.calendar.auto_sync_enabled')) {
            return;
        }

        $calendarService->syncTaskAutomatically(
            $this->ticketId
        );
    }
}