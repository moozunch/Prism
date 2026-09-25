<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches a user to a project with a project responsibility', function () {
    $project = Project::create([
        'name' => 'PRISM Project',
        'ticket_prefix' => 'PRJ',
    ]);
    $user = User::factory()->create();

    $project->members()->attach($user->id, [
        'role' => 'Project Manager',
    ]);

    expect($project->members()->whereKey($user->id)->first()->pivot->role)
        ->toBe('Project Manager');
    expect($user->projects()->whereKey($project->id)->first()->pivot->role)
        ->toBe('Project Manager');
});