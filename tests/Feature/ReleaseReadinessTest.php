<?php

use App\Filament\Pages\ProjectBoard;
use App\Filament\Pages\Reporting;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Division;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('scopes project mutation grants to membership for non-administrative roles', function (string $role, bool $organizationWide) {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate($role, 'web'));
    foreach (['view_project', 'update_project', 'delete_project'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $inside = Project::factory()->create();
    $outside = Project::factory()->create();
    $inside->members()->attach($user);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($user->can($ability, $inside))->toBeTrue()
            ->and($user->can($ability, $outside))->toBe($organizationWide);
    }
})->with([
    'technical admin' => ['super_admin', true],
    'organization admin' => ['admin', true],
    'division head' => ['division_head', false],
    'project manager' => ['project_manager', false],
    'member' => ['member', false],
]);

it('rejects direct project edit outside the project manager membership', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Role::findOrCreate('project_manager', 'web'));
    foreach (['view_project', 'view_any_project', 'update_project'] as $permission) {
        $manager->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $inside = Project::factory()->create();
    $outside = Project::factory()->create();
    $inside->members()->attach($manager);

    $this->actingAs($manager)
        ->get(ProjectResource::getUrl('edit', ['record' => $inside]))->assertOk();
    $this->get(ProjectResource::getUrl('edit', ['record' => $outside]))->assertNotFound();
});

it('moves an assigned task only to statuses of its own project', function () {
    $this->seed(RoleSeeder::class);
    $member = User::factory()->create();
    $member->assignRole('member');
    $project = Project::factory()->create();
    $project->members()->attach($member);
    $other = Project::factory()->create();
    $todo = $project->ticketStatuses()->create(['name' => 'To Do']);
    $done = $project->ticketStatuses()->create(['name' => 'Done', 'is_completed' => true]);
    $foreign = $other->ticketStatuses()->create(['name' => 'Other']);
    $task = $project->tickets()->create(['name' => 'QA transition', 'ticket_status_id' => $todo->id]);
    $task->assignees()->attach($member);

    $board = Livewire::actingAs($member)->test(ProjectBoard::class, ['project_id' => $project->id]);
    $board->call('moveTicket', $task->id, $foreign->id)->assertSuccessful();
    expect($task->fresh()->ticket_status_id)->toBe($todo->id);
    $this->assertDatabaseMissing('ticket_histories', ['ticket_id' => $task->id, 'ticket_status_id' => $foreign->id]);

    $board->call('moveTicket', $task->id, $done->id)->assertSuccessful();
    expect($task->fresh()->ticket_status_id)->toBe($done->id)
        ->and($project->fresh()->progress_percentage)->toEqual(100);
});

it('filters report deadlines without including tasks from a hidden project', function () {
    $this->seed(RoleSeeder::class);
    $member = User::factory()->create();
    $member->assignRole('member');
    $visible = Project::factory()->create();
    $hidden = Project::factory()->create();
    $visible->members()->attach($member);
    $visibleStatus = $visible->ticketStatuses()->create(['name' => 'To Do']);
    $hiddenStatus = $hidden->ticketStatuses()->create(['name' => 'Hidden Status']);
    $businessTask = $visible->tickets()->create([
        'name' => 'QA in period', 'ticket_status_id' => $visibleStatus->id, 'due_date' => '2027-01-15',
    ]);
    $outOfPeriod = $visible->tickets()->create([
        'name' => 'QA after period', 'ticket_status_id' => $visibleStatus->id, 'due_date' => '2027-02-15',
    ]);
    $outside = $hidden->tickets()->create([
        'name' => 'QA hidden', 'ticket_status_id' => $hiddenStatus->id, 'due_date' => '2027-01-15',
    ]);

    Livewire::actingAs($member)->test(Reporting::class)
        ->filterTable('period', ['from' => '2027-01-10', 'until' => '2027-01-20'])
        ->assertCanSeeTableRecords([$businessTask])
        ->assertCanNotSeeTableRecords([$outOfPeriod, $outside])
        ->filterTable('period', ['from' => '2027-03-01', 'until' => '2027-03-02'])
        ->assertSee('No Task Data');
});

it('rejects a duplicate member email and lets an admin reassign a division', function () {
    Notification::fake();
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $originalDivision = Division::factory()->create();
    $destination = Division::factory()->create();
    $member = User::factory()->for($originalDivision)->create();
    $member->assignRole('member');
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Duplicate email fixture',
            'email' => $member->email,
            'password' => 'disposable-qa-password',
            'division_id' => (string) $destination->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
    expect(User::where('email', $member->email)->count())->toBe(1);

    Livewire::test(EditUser::class, ['record' => $member->id])
        ->fillForm(['division_id' => (string) $destination->id])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($member->fresh()->division_id)->toBe($destination->id);
});
