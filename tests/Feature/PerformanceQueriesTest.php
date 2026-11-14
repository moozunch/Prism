<?php

use App\Filament\Pages\ProjectBoard;
use App\Filament\Pages\Reporting;
use App\Filament\Pages\TicketTimeline;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function performanceProject(User $member, string $prefix): Project
{
    $project = Project::create(['name' => $prefix, 'ticket_prefix' => $prefix]);
    $project->members()->attach($member);
    $done = $project->ticketStatuses()->create(['name' => 'Complete', 'is_completed' => true, 'sort_order' => 1]);
    $todo = $project->ticketStatuses()->create(['name' => 'Open', 'is_completed' => false, 'sort_order' => 0]);
    foreach ([$todo, $done, $done] as $status) {
        $task = Ticket::create(['project_id' => $project->id, 'ticket_status_id' => $status->id, 'name' => 'Task', 'due_date' => '2026-12-01']);
        $task->assignees()->attach($member);
    }
    return $project;
}

it('batches progress without using a constrained tickets collection and refreshes after writes', function () {
    $user = User::factory()->create();
    $project = performanceProject($user, 'PROG');
    $loaded = Project::withProgressCounts()->with(['tickets' => fn ($q) => $q->limit(1)])->findOrFail($project->id);
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect($loaded->progress_percentage)->toBe(66.7)
        ->and($loaded->progress_percentage)->toBe(66.7)
        ->and(DB::getQueryLog())->toHaveCount(0);
    DB::disableQueryLog();
    expect($project->progress_percentage)->toBe(66.7);
    $project->tickets()->where('ticket_status_id', $project->ticketStatuses()->where('is_completed', false)->value('id'))->delete();
    expect(Project::withProgressCounts()->findOrFail($project->id)->progress_percentage)->toBe(100.0)
        ->and(Project::create(['name' => 'Empty', 'ticket_prefix' => 'EMPTY'])->progress_percentage)->toBe(0.0);
});

it('retains project and reporting visibility while preloading progress', function () {
    $member = User::factory()->create();
    $other = User::factory()->create();
    Role::findOrCreate('member', 'web');
    $member->assignRole('member');
    $visible = performanceProject($member, 'VISIBLE');
    performanceProject($other, 'PRIVATE');
    $this->actingAs($member);
    expect(ProjectResource::getEloquentQuery()->pluck('id')->all())->toBe([$visible->id]);
    $page = new class extends Reporting {
        public function records() { return $this->getReportQuery()->get(); }
    };
    $records = $page->records();
    expect($records)->toHaveCount(3);
    DB::enableQueryLog();
    DB::flushQueryLog();
    foreach ($records as $record) {
        expect($record->project_id)->toBe($visible->id)
            ->and($record->project->progress_percentage)->toBe(66.7);
    }
    expect(DB::getQueryLog())->toHaveCount(0);
    DB::disableQueryLog();
});

it('loads board assignees in one query without users assigned only elsewhere', function () {
    $member = User::factory()->create();
    $other = User::factory()->create();
    $project = performanceProject($member, 'BOARD');
    performanceProject($other, 'OTHER');
    $board = new ProjectBoard;
    $board->selectedProject = $project;
    DB::enableQueryLog();
    DB::flushQueryLog();
    $board->loadProjectUsers();
    expect(DB::getQueryLog())->toHaveCount(1)
        ->and($board->projectUsers->modelKeys())->toBe([$member->id]);
    DB::disableQueryLog();
});

it('calculates ordered task progress with one status lookup per timeline', function () {
    $member = User::factory()->create();
    $project = performanceProject($member, 'TIME');
    $this->actingAs($member);
    $page = new TicketTimeline;
    $page->selectedProject = $project;
    $page->projectId = $project->id;
    DB::enableQueryLog();
    DB::flushQueryLog();
    $data = $page->getGanttDataProperty();
    expect($data['data'])->toHaveCount(3)
        ->and(collect($data['data'])->pluck('progress')->sort()->values()->all())->toBe([0.5, 1, 1])
        ->and(DB::getQueryLog())->toHaveCount(3);
    DB::disableQueryLog();
});

it('keeps member dashboard totals fresh and restricted to their projects', function () {
    $member = User::factory()->create();
    Role::findOrCreate('member', 'web');
    $member->assignRole('member');
    $visible = performanceProject($member, 'STATS');
    performanceProject(User::factory()->create(), 'HIDDEN');
    $this->actingAs($member);
    $widget = new class extends App\Filament\Widgets\StatsOverview {
        public function stats() { return $this->getStats(); }
    };
    $values = collect($widget->stats())->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);
    expect((int) $values['Project Tasks'])->toBe(3)
        ->and((int) $values['New Tasks This Week'])->toBe(3);
    $visible->tickets()->delete();
    $values = collect($widget->stats())->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);
    expect((int) $values['Project Tasks'])->toBe(0);
});

it('groups monthly chart values without exposing another members tasks', function () {
    $member = User::factory()->create();
    Role::findOrCreate('member', 'web');
    $member->assignRole('member');
    performanceProject($member, 'MONTH');
    performanceProject(User::factory()->create(), 'OTHER');
    $this->actingAs($member);
    $widget = new class extends App\Filament\Widgets\MonthlyTicketTrendChart {
        public function data() { return $this->getData(); }
    };
    expect(array_sum($widget->data()['datasets'][0]['data']))->toBe(3);
});

it('renders optimized pages and handles timeline Livewire requests for an authorized user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');
    foreach (['view_any_project', 'view_any_ticket'] as $permission) {
        $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate($permission, 'web'));
    }
    $project = performanceProject($user, 'PAGES');
    $this->actingAs($user);
    $this->get('/admin')->assertOk();
    $this->get('/admin/projects')->assertOk();
    $this->get('/admin/tasks')->assertOk();
    Livewire\Livewire::test(TicketTimeline::class, ['project_id' => $project->id])
        ->assertSuccessful()
        ->call('selectProject', $project->id)
        ->assertSuccessful();
});
