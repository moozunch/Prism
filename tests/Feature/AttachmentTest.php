<?php

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates and polymorphically links an attachment to a project', function () {
    Storage::fake('attachments');
    $user = User::factory()->create();
    $project = Project::create([
        'name' => 'Attachment Project',
        'ticket_prefix' => 'ATT',
    ]);

    $attachment = $project->attachments()->create([
        'file_path' => 'uploads/project-file.pdf',
        'file_name' => 'project-file.pdf',
        'original_name' => 'project-file.pdf',
        'mime_type' => 'application/pdf',
        'user_id' => $user->id,
    ]);

    expect($attachment->attachable->is($project))->toBeTrue()
        ->and($project->attachments()->first()->id)->toBe($attachment->id)
        ->and($attachment->user->is($user))->toBeTrue();
});

it('links an attachment to a ticket and authorizes its uploader', function () {
    Storage::fake('attachments');
    $user = User::factory()->create();
    $project = Project::create([
        'name' => 'Attachment Project',
        'ticket_prefix' => 'ATT',
    ]);
    $ticket = Ticket::create([
        'project_id' => $project->id,
        'name' => 'Attachment Task',
    ]);
    $attachment = $ticket->attachments()->create([
        'file_path' => 'uploads/task-file.txt',
        'file_name' => 'task-file.txt',
        'original_name' => 'task-file.txt',
        'mime_type' => 'text/plain',
        'user_id' => $user->id,
    ]);

    expect($attachment->attachable->is($ticket))->toBeTrue()
        ->and($user->can('delete', $attachment))->toBeTrue();
});
