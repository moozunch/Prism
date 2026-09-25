<?php

use App\Filament\Widgets\StatsOverview;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('does not grant organization-wide dashboard metrics to a member', function () {
    $member = User::factory()->create();
    Role::create(['name' => 'member', 'guard_name' => 'web']);
    $member->assignRole('member');

    $this->actingAs($member);

    expect((new StatsOverview)->canViewOrganizationMetrics())->toBeFalse();
});

it('grants organization-wide dashboard metrics only to organization admins', function () {
    $admin = User::factory()->create();
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    expect((new StatsOverview)->canViewOrganizationMetrics())->toBeTrue();
});

it('notifies project participants about comments using task terminology', function () {
    $commenter = User::factory()->create(['name' => 'Commenter']);
    $recipient = User::factory()->create();
    $project = Project::create(['name' => 'Reporting Project', 'ticket_prefix' => 'RPT']);
    $status = TicketStatus::create(['project_id' => $project->id, 'name' => 'In Progress']);
    $ticket = Ticket::create([
        'project_id' => $project->id,
        'ticket_status_id' => $status->id,
        'name' => 'Prepare report',
        'created_by' => $recipient->id,
    ]);
    $comment = TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $commenter->id,
        'comment' => 'Please review this task.',
    ]);

    expect(Notification::where('user_id', $recipient->id)->latest()->first()->message)
        ->toContain('Prepare report')
        ->toContain('task');
});