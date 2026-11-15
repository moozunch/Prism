<?php

namespace Tests\Feature;

use App\Jobs\SyncGoogleCalendarTask;
use App\Models\GoogleConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarAutoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_environment_is_isolated(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->assertSame(
            'sqlite',
            config('database.default')
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );

        $this->assertSame(
            'sync',
            config('queue.default')
        );
    }

    public function test_auto_sync_does_not_run_when_disabled(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        config()->set(
            'services.google.calendar.enabled',
            true
        );

        config()->set(
            'services.google.calendar.auto_sync_enabled',
            false
        );

        $job = new SyncGoogleCalendarTask(999999);

        $job->handle(
            app(GoogleCalendarService::class)
        );

        Http::assertNothingSent();
    }

    public function test_auto_sync_skips_missing_task(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        config()->set(
            'services.google.calendar.enabled',
            true
        );

        config()->set(
            'services.google.calendar.auto_sync_enabled',
            true
        );

        $job = new SyncGoogleCalendarTask(999999);

        $job->handle(
            app(GoogleCalendarService::class)
        );

        Http::assertNothingSent();

        $this->assertDatabaseCount(
            'google_calendar_events',
            0
        );
    }

    public function test_auto_sync_creates_event_for_assignee(): void
    {
        // Enable Calendar only inside this test.
        config()->set(
            'services.google.calendar.enabled',
            true
        );

        config()->set(
            'services.google.calendar.auto_sync_enabled',
            true
        );

        config()->set(
            'services.google.calendar.calendar_id',
            'primary'
        );

        // Block real external HTTP requests.
        Http::preventStrayRequests();

        // Simulate a successful Google Calendar response.
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events*'
                => Http::response([
                    'id' => 'prismtestevent001',
                    'status' => 'confirmed',
                ], 200),
        ]);

        // 1. Create a dummy task organizer.
        $organizer = User::factory()->create([
            'name' => 'PRISM Test Organizer',
        ]);

        // 2. Create a dummy assignee.
        $assignee = User::factory()->create([
            'name' => 'PRISM Test Assignee',
            'calendar_email' => 'assignee@example.com',
        ]);

        // 3. Create a dummy Google connection.
        GoogleConnection::create([
            'user_id' => $organizer->id,
            'google_account_id' => 'test-google-account-001',
            'google_email' => 'organizer@example.com',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_at' => now()->addHour(),
            'scopes' => [
                'https://www.googleapis.com/auth/calendar.events',
            ],
        ]);

        // 4. Create a dummy project.
        $project = Project::factory()->create();
        $status = TicketStatus::create([
            'project_id' => $project->id,
            'name' => 'To Do',
            'sort_order' => 1,
            'is_completed' => false,
        ]);

        // 5. Create a task with a deadline.
        $ticket = Ticket::factory()->create([
            'project_id' => $project->id,
            'ticket_status_id' => $status->id,
            'created_by' => $organizer->id,
            'name' => 'PRISM Auto Sync Test',
            'due_date' => '2026-12-15',
        ]);

        // 6. Assign the task to the dummy assignee.
        $ticket->assignees()->attach($assignee->id);

        // 7. Execute the synchronization Job.
        $job = new SyncGoogleCalendarTask($ticket->id);

        $job->handle(
            app(GoogleCalendarService::class)
        );

        // 8. Check the outgoing Google Calendar request.
        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->method() === 'POST'
                && str_contains(
                    $request->url(),
                    'calendar/v3/calendars/primary/events'
                )
                && ($data['summary'] ?? null)
                    === '[PRISM Task] PRISM Auto Sync Test'
                && ($data['start']['date'] ?? null)
                    === '2026-12-15'
                && ($data['end']['date'] ?? null)
                    === '2026-12-16'
                && ($data['attendees'][0]['email'] ?? null)
                    === 'assignee@example.com';
        });

        // Only one Calendar request should be sent.
        Http::assertSentCount(1);

        // 9. Verify the event was saved to the test database.
        $this->assertDatabaseHas(
            'google_calendar_events',
            [
                'ticket_id' => $ticket->id,
                'google_event_id' => 'prismtestevent001',
                'sync_status' => 'synced',
            ]
        );
    }

    public function test_auto_sync_updates_existing_event_without_duplicates(): void
    {
        config()->set('services.google.calendar.enabled', true);
        config()->set('services.google.calendar.auto_sync_enabled', true);
        config()->set('services.google.calendar.calendar_id', 'primary');

        // Never contact the real Google API.
        Http::preventStrayRequests();

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events*'
                => Http::response([
                    'id' => 'prismupdatetest001',
                    'status' => 'confirmed',
                ], 200),
        ]);

        // Create test organizer.
        $organizer = User::factory()->create([
            'name' => 'PRISM Update Organizer',
        ]);

        // Create two different assignees.
        $assigneeA = User::factory()->create([
            'name' => 'Assignee A',
            'calendar_email' => 'assignee-a@example.com',
        ]);

        $assigneeB = User::factory()->create([
            'name' => 'Assignee B',
            'calendar_email' => 'assignee-b@example.com',
        ]);

        // Create organizer's Google connection.
        GoogleConnection::create([
            'user_id' => $organizer->id,
            'google_account_id' => 'test-update-account',
            'google_email' => 'organizer@example.com',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_at' => now()->addHour(),
            'scopes' => [
                'https://www.googleapis.com/auth/calendar.events',
            ],
        ]);

        // Create project and task status.
        $project = Project::factory()->create();

        $status = TicketStatus::create([
            'project_id' => $project->id,
            'name' => 'To Do',
            'sort_order' => 1,
            'is_completed' => false,
        ]);

        // Create task with original deadline.
        $ticket = Ticket::factory()->create([
            'project_id' => $project->id,
            'ticket_status_id' => $status->id,
            'created_by' => $organizer->id,
            'name' => 'PRISM Update Calendar Test',
            'due_date' => '2026-12-15',
        ]);

        $ticket->assignees()->attach($assigneeA->id);

        // FIRST SYNC: create event.
        $job = new SyncGoogleCalendarTask($ticket->id);

        $job->handle(app(GoogleCalendarService::class));

        $this->assertDatabaseHas('google_calendar_events', [
            'ticket_id' => $ticket->id,
            'google_event_id' => 'prismupdatetest001',
            'sync_status' => 'synced',
        ]);

        // EDIT TASK: change deadline and assignee.
        $ticket->update([
            'due_date' => '2026-12-20',
        ]);

        $ticket->assignees()->sync([$assigneeB->id]);

        // SECOND SYNC: update the existing event.
        $job->handle(app(GoogleCalendarService::class));

        // Confirm the update uses PATCH, not a new POST.
        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->method() === 'PATCH'
                && str_contains(
                    $request->url(),
                    '/events/prismupdatetest001'
                )
                && str_contains(
                    $request->url(),
                    'sendUpdates=all'
                )
                && ($data['start']['date'] ?? null)
                    === '2026-12-20'
                && ($data['end']['date'] ?? null)
                    === '2026-12-21'
                && count($data['attendees'] ?? []) === 1
                && ($data['attendees'][0]['email'] ?? null)
                    === 'assignee-b@example.com';
        });

        // Exactly two HTTP calls: POST, then PATCH.
        Http::assertSentCount(2);

        // The database must contain only one event for this task.
        $this->assertDatabaseCount('google_calendar_events', 1);

        $this->assertDatabaseHas('google_calendar_events', [
            'ticket_id' => $ticket->id,
            'google_event_id' => 'prismupdatetest001',
            'sync_status' => 'synced',
        ]);
    }


    public function test_auto_sync_cancels_event_when_deadline_removed(): void
    {
        config()->set('services.google.calendar.enabled', true);
        config()->set('services.google.calendar.auto_sync_enabled', true);
        config()->set('services.google.calendar.calendar_id', 'primary');

        // Prevent any real Google API request.
        Http::preventStrayRequests();

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events/*'
                => Http::response('', 204),
        ]);

        // Create a dummy organizer.
        $organizer = User::factory()->create([
            'name' => 'PRISM Cancellation Organizer',
        ]);

        $connection = GoogleConnection::create([
            'user_id' => $organizer->id,
            'google_account_id' => 'test-cancel-account',
            'google_email' => 'organizer@example.com',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_at' => now()->addHour(),
            'scopes' => [
                'https://www.googleapis.com/auth/calendar.events',
            ],
        ]);

        // Create a project and task status.
        $project = Project::factory()->create();

        $status = TicketStatus::create([
            'project_id' => $project->id,
            'name' => 'To Do',
            'sort_order' => 1,
            'is_completed' => false,
        ]);

        // Create a task that originally had a deadline.
        $ticket = Ticket::factory()->create([
            'project_id' => $project->id,
            'ticket_status_id' => $status->id,
            'created_by' => $organizer->id,
            'name' => 'PRISM Cancel Calendar Test',
            'due_date' => '2026-12-15',
        ]);

        // Simulate an event already stored in Google Calendar.
        GoogleCalendarEvent::create([
            'ticket_id' => $ticket->id,
            'google_connection_id' => $connection->id,
            'calendar_id' => 'primary',
            'google_event_id' => 'prismtestcancel001',
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        // User removes the task deadline.
        $ticket->update([
            'due_date' => null,
        ]);

        // Run the automatic synchronization Job.
        $job = new SyncGoogleCalendarTask($ticket->id);

        $job->handle(app(GoogleCalendarService::class));

        // Expect a DELETE request, not POST or PATCH.
        Http::assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains(
                    $request->url(),
                    '/events/prismtestcancel001'
                )
                && str_contains(
                    $request->url(),
                    'sendUpdates=all'
                );
        });

        // Only one request should be made.
        Http::assertSentCount(1);

        // The local record should remember the cancellation.
        $this->assertDatabaseHas('google_calendar_events', [
            'ticket_id' => $ticket->id,
            'google_event_id' => null,
            'sync_status' => 'cancelled',
        ]);
    }


}
