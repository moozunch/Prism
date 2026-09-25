<?php

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a project with a draft status and updates its status', function () {
    $project = Project::create([
        'name' => 'PRISM Project',
        'ticket_prefix' => 'PRJ',
        'status' => 'Draft',
        'start_date' => '2026-09-23',
        'end_date' => '2026-10-23',
    ]);

    expect($project->status)->toBe('Draft')
        ->and($project->start_date->toDateString())->toBe('2026-09-23')
        ->and($project->end_date->toDateString())->toBe('2026-10-23')
        ->and($project->ticket_prefix)->toBe('PRJ');

    $project->update(['status' => 'In Progress']);

    expect($project->fresh()->status)->toBe('In Progress');
});

it('calculates project progress from completed ticket statuses', function () {
    $project = Project::create([
        'name' => 'PRISM Progress Project',
        'ticket_prefix' => 'PROG',
        'status' => 'In Progress',
    ]);
    $completedStatus = TicketStatus::create([
        'project_id' => $project->id,
        'name' => 'Done',
        'is_completed' => true,
    ]);
    $openStatus = TicketStatus::create([
        'project_id' => $project->id,
        'name' => 'In Progress',
        'is_completed' => false,
    ]);

    Ticket::create([
        'project_id' => $project->id,
        'ticket_status_id' => $completedStatus->id,
        'name' => 'Completed work',
    ]);
    Ticket::create([
        'project_id' => $project->id,
        'ticket_status_id' => $openStatus->id,
        'name' => 'Open work',
    ]);

    expect($project->fresh()->progress_percentage)->toBe(50.0);
});