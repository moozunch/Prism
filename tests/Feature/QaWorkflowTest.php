<?php

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Jobs\SyncGoogleCalendarTask;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use App\Services\GoogleCalendarService;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::preventStrayRequests();
});

function qaMember(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('member', 'web'));

    return $user;
}

it('requests a reset link for a verified member without sending real mail', function () {
    Notification::fake();
    $member = qaMember();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => $member->email])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertSentTo($member, ResetPasswordNotification::class);
});

it('uses a valid reset token once and rejects an invalid token', function () {
    $member = qaMember();
    $oldHash = $member->password;
    $token = Password::broker()->createToken($member);
    $password = 'QA-new-password-123';

    Livewire::test(ResetPassword::class, ['email' => $member->email, 'token' => 'invalid-ci-fixture'])
        ->fillForm(['password' => $password, 'passwordConfirmation' => $password])
        ->call('resetPassword');
    expect($member->fresh()->password)->toBe($oldHash);

    Livewire::test(ResetPassword::class, ['email' => $member->email, 'token' => $token])
        ->fillForm(['password' => $password, 'passwordConfirmation' => $password])
        ->call('resetPassword')
        ->assertRedirect();
    expect(Hash::check($password, $member->fresh()->password))->toBeTrue()
        ->and(Password::broker()->tokenExists($member, $token))->toBeFalse();
});

it('protects local attachment content and distinguishes missing files', function () {
    Storage::fake('attachments');
    $member = qaMember();
    $outsider = qaMember();
    $permission = Permission::findOrCreate('view_project', 'web');
    $member->givePermissionTo($permission);
    $outsider->givePermissionTo($permission);
    $project = Project::factory()->create();
    $project->members()->attach($member);
    $attachment = $project->attachments()->create([
        'file_path' => 'qa/reference.txt', 'file_name' => 'reference.txt',
        'original_name' => 'reference.txt', 'mime_type' => 'text/plain',
        'user_id' => $member->id, 'storage_provider' => 'local',
    ]);
    $url = route('attachments.view', $attachment);

    Storage::disk('attachments')->put($attachment->file_path, 'QA disposable contents');
    $this->actingAs($outsider)->get($url)->assertForbidden();
    $response = $this->actingAs($member)->get($url);
    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe('QA disposable contents');
    Storage::disk('attachments')->delete($attachment->file_path);
    $this->get($url)->assertNotFound();
});

it('rejects an unmatched OAuth session before calling the provider', function () {
    $member = qaMember();
    $other = qaMember();
    Socialite::shouldReceive('driver')->never();

    $this->actingAs($member)
        ->withSession(['google_integration_user_id' => $other->id])
        ->get(route('integrations.google.callback'))
        ->assertForbidden();

    $this->assertDatabaseEmpty('google_connections');
});

it('persists an approved mocked OAuth connection with hidden encrypted tokens', function () {
    $member = qaMember();
    $google = (new GoogleUser)->map(['id' => 'qa-account', 'email' => 'qa-google@example.test']);
    $google->setToken('fake-qa-access-token')
        ->setRefreshToken('fake-qa-refresh-token')
        ->setExpiresIn(3600)
        ->setApprovedScopes([
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/drive.file',
        ]);
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('redirectUrl')->with(route('integrations.google.callback'))->once()->andReturnSelf();
    $provider->shouldReceive('user')->once()->andReturn($google);
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

    $this->actingAs($member)
        ->withSession(['google_integration_user_id' => $member->id])
        ->get(route('integrations.google.callback'))
        ->assertRedirect('/admin')
        ->assertSessionHas('success');

    $connection = $member->googleConnection()->firstOrFail();
    expect($connection->google_account_id)->toBe('qa-account')
        ->and($connection->refresh_token)->toBe('fake-qa-refresh-token')
        ->and($connection->toArray())->not->toHaveKeys(['access_token', 'refresh_token'])
        ->and(DB::table('google_connections')->value('access_token'))->not->toBe('fake-qa-access-token');
});

it('rejects invalid Google OAuth state without persisting or exchanging a code', function () {
    $member = qaMember();
    config([
        'services.google.client_id' => 'qa-client',
        'services.google.client_secret' => 'qa-client-secret',
    ]);

    $this->actingAs($member)
        ->withSession(['google_integration_user_id' => $member->id, 'state' => 'expected-qa-state'])
        ->get(route('integrations.google.callback', ['state' => 'unmatched-qa-state', 'code' => 'fake-code']))
        ->assertRedirect('/admin')
        ->assertSessionHas('error');

    $this->assertDatabaseEmpty('google_connections');
    Http::assertNothingSent();
});

it('queues Calendar jobs after commit and delegates only when autosync is enabled', function () {
    Queue::fake();
    SyncGoogleCalendarTask::dispatch(42);
    Queue::assertPushed(SyncGoogleCalendarTask::class, fn (SyncGoogleCalendarTask $job) =>
        $job->ticketId === 42 && $job->afterCommit && $job->tries === 1);

    $service = Mockery::mock(GoogleCalendarService::class);
    $service->shouldReceive('syncTaskAutomatically')->with(42)->once()->andReturn(null);
    $job = new SyncGoogleCalendarTask(42);
    config(['services.google.calendar.enabled' => false, 'services.google.calendar.auto_sync_enabled' => true]);
    $job->handle($service);
    config(['services.google.calendar.enabled' => true, 'services.google.calendar.auto_sync_enabled' => false]);
    $job->handle($service);
    config(['services.google.calendar.auto_sync_enabled' => true]);
    $job->handle($service);
});

it('persists a failed Calendar sync without changing the task', function (int|string $failure) {
    config(['services.google.calendar.enabled' => true]);
    $requestAttempts = 0;
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*/events*' => function ($request) use ($failure, &$requestAttempts) {
            $requestAttempts++;

            return $failure === 'timeout'
                ? Http::failedConnection('QA simulated timeout')($request)
                : Http::response([], $failure);
        },
        'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);
    $organizer = qaMember();
    $organizer->googleConnection()->create([
        'google_account_id' => 'qa-calendar-organizer',
        'google_email' => 'qa-calendar@example.test',
        'access_token' => 'fake-calendar-access',
        'refresh_token' => 'fake-calendar-refresh',
        'expires_at' => $failure === 'refresh' ? now()->subHour() : now()->addHour(),
        'scopes' => ['https://www.googleapis.com/auth/calendar.events'],
    ]);
    $project = Project::factory()->create();
    $status = $project->ticketStatuses()->create(['name' => 'To Do', 'sort_order' => 0]);
    $task = $project->tickets()->create([
        'name' => 'QA Calendar task', 'ticket_status_id' => $status->id,
        'due_date' => '2027-01-03', 'created_by' => $organizer->id,
    ]);
    $assignee = User::factory()->create(['calendar_email' => 'qa-attendee@example.com']);
    $task->assignees()->attach($assignee);
    $original = $task->fresh()->getAttributes();
    $this->actingAs($organizer);

    expect(app(GoogleCalendarService::class)->createTaskDeadline($task))->toBeNull()
        ->and($task->fresh()->getAttributes())->toEqual($original);
    if ($failure === 'refresh') {
        expect($requestAttempts)->toBe(0);
        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token');
        $this->assertDatabaseEmpty('google_calendar_events');
    } else {
        expect($requestAttempts)->toBe(1);
        $this->assertDatabaseHas('google_calendar_events', [
            'ticket_id' => $task->id, 'sync_status' => 'failed', 'google_event_id' => null,
        ]);
    }
})->with([403, 429, 503, 'timeout', 'refresh']);

it('accepts multiple project assignees and refuses a non-member', function () {
    $project = Project::factory()->create();
    $status = $project->ticketStatuses()->create(['name' => 'To Do', 'sort_order' => 0]);
    $creator = qaMember();
    $second = qaMember();
    $outsider = qaMember();
    $this->seed(RoleSeeder::class);
    $creator->syncRoles(['admin']);
    $project->members()->attach([$creator->id, $second->id]);
    $this->actingAs($creator);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    expect($creator->can('create', \App\Models\Ticket::class))->toBeTrue();
    $form = [
        'name' => 'QA multi-assignee task', 'project_id' => $project->id,
        'ticket_status_id' => $status->id,
    ];

    $rejected = Livewire::test(CreateTicket::class)
        ->assertStatus(200)
        ->fillForm(['project_id' => $project->id])
        ->fillForm($form + ['assignees' => [(string) $creator->id, (string) $outsider->id]])
        ->call('create');
    expect($rejected->errors()->keys())->toContain('data.assignees.1');
    $this->assertDatabaseEmpty('tickets');

    Livewire::test(CreateTicket::class)
        ->assertStatus(200)
        ->fillForm(['project_id' => $project->id])
        ->fillForm($form + ['assignees' => [(string) $creator->id, (string) $second->id]])
        ->call('create')
        ->assertHasNoFormErrors();
    $task = $project->tickets()->where('name', $form['name'])->firstOrFail();
    expect($task->assignees()->orderBy('users.id')->pluck('users.id')->all())
        ->toBe([$creator->id, $second->id]);
});
