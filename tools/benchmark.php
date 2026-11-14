<?php

// Default: disposable in-memory SQLite. --mysql-test requires an empty dedicated prism_perf_* schema.
putenv('APP_ENV=testing');
$mysql = in_array('--mysql-test', $argv, true);
if (! $mysql) {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=:memory:');
}
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$safeSqlite = config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';
$safeMysql = $mysql && config('database.default') === 'mysql'
    && str_starts_with(config('database.connections.mysql.database'), 'prism_perf_')
    && count(Illuminate\Support\Facades\DB::select('SHOW TABLES')) === 0;
if (! $safeSqlite && ! $safeMysql) {
    throw new RuntimeException('Benchmark requires in-memory SQLite or an empty prism_perf_* MySQL schema. Clear configuration cache first.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$user = App\Models\User::factory()->create();
Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
$user->assignRole('super_admin');
auth()->login($user);
for ($i = 1; $i <= 20; $i++) {
    $project = App\Models\Project::create(['name' => "Project $i", 'ticket_prefix' => "P$i", 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'pinned_date' => now()]);
    $project->members()->attach($user);
    $status = $project->ticketStatuses()->create(['name' => 'Done', 'is_completed' => true, 'sort_order' => 1]);
    for ($j = 1; $j <= 10; $j++) {
        $ticket = App\Models\Ticket::create(['project_id' => $project->id, 'ticket_status_id' => $status->id, 'name' => "Task $j", 'due_date' => '2026-10-01', 'created_by' => $user->id]);
        $ticket->assignees()->attach($user);
    }
}
$report = new class extends App\Filament\Pages\Reporting {
    public function records() { return $this->getReportQuery()->limit(25)->get(); }
};
$timeline = new class extends App\Filament\Widgets\ProjectTimeline {
    public function data() { return $this->getViewData(); }
};
$board = new App\Filament\Pages\ProjectBoard;
$board->selectedProject = App\Models\Project::first();
$tasks = new App\Filament\Pages\TicketTimeline;
$tasks->selectedProject = $board->selectedProject;
$tasks->projectId = $board->selectedProject->id;
$scenarios = [
    'project_progress_20_rows' => function () {
        $projects = App\Filament\Resources\Projects\ProjectResource::getEloquentQuery()->limit(20)->get();
        foreach ($projects as $p) { for ($i = 0; $i < 5; $i++) { $v = $p->progress_percentage; } }
    },
    'reporting_25_rows' => function () use ($report) { foreach ($report->records() as $t) { $v = $t->project->progress_percentage; } },
    'dashboard_project_timeline' => fn () => $timeline->data(),
    'board_assignee_options' => fn () => $board->loadProjectUsers(),
    'task_timeline_data' => function () use ($board) {
        $page = new App\Filament\Pages\TicketTimeline;
        $page->selectedProject = $board->selectedProject;
        $page->projectId = $board->selectedProject->id;
        return $page->getGanttDataProperty();
    },
];
$result = [];
foreach ($scenarios as $name => $run) {
    $samples = [];
    for ($i = 0; $i < 5; $i++) {
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        $start = hrtime(true);
        $run();
        $elapsed = (hrtime(true) - $start) / 1e6;
        $queries = Illuminate\Support\Facades\DB::getQueryLog();
        Illuminate\Support\Facades\DB::disableQueryLog();
        $samples[] = ['ms' => round($elapsed, 3), 'queries' => count($queries), 'db_ms' => round(array_sum(array_column($queries, 'time')), 3)];
    }
    usort($samples, fn ($a, $b) => $a['ms'] <=> $b['ms']);
    $result[$name] = $samples[2];
}
$result['peak_memory_bytes'] = memory_get_peak_usage(true);
echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
