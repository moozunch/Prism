<?php

declare(strict_types=1);

use App\Models\Division;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\PrismRoleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || ! str_starts_with(basename(dirname($database)), 'prism-qa.')
    || ! is_file($database) || User::exists()) {
    throw new RuntimeException('Browser fixtures require the empty, disposable SQLite database from browser.sh.');
}

(new RoleSeeder)->run();
(new PrismRoleSeeder)->run();
Organization::profile()->update(['name' => 'QA Northstar']);
$division = Division::create(['name' => 'QA Engineering']);
$manager = User::factory()->create([
    'name' => 'QA Administrator',
    'email' => 'qa-admin@example.test',
    'password' => getenv('PRISM_QA_PASSWORD'),
    'division_id' => $division->id,
]);
$manager->assignRole('admin');
$member = User::factory()->create([
    'name' => 'QA Member',
    'email' => 'qa-member@example.test',
    'password' => getenv('PRISM_QA_PASSWORD'),
    'division_id' => $division->id,
]);
$member->assignRole('member');

foreach (['Visible' => 'VQA', 'Restricted' => 'RQA'] as $name => $prefix) {
    $project = Project::create(['name' => "QA $name Project", 'ticket_prefix' => $prefix]);
    $project->members()->attach($name === 'Visible' ? $member : $manager);
    $status = $project->ticketStatuses()->create(['name' => 'To Do', 'color' => '#475569', 'sort_order' => 1]);
    $project->ticketStatuses()->create(['name' => 'Done', 'sort_order' => 2, 'is_completed' => true]);
    $ticket = $project->tickets()->create(['name' => "QA $name Task", 'ticket_status_id' => $status->id]);
    $ticket->assignees()->attach($name === 'Visible' ? $member : $manager);
}
Notification::create([
    'user_id' => $member->id, 'type' => 'comment_added', 'title' => 'QA Notification',
    'message' => 'QA notification for member',
]);
$visibleTask = Ticket::where('name', 'QA Visible Task')->firstOrFail();
$visibleTask->comments()->create(['user_id' => $member->id, 'comment' => 'QA comment on the visible task']);
if (! Storage::disk('attachments')->put('qa-proof.txt', 'QA attachment content')) {
    throw new RuntimeException('Could not write the isolated attachment fixture.');
}
$attachment = $visibleTask->attachments()->create([
    'user_id' => $member->id, 'storage_provider' => 'local', 'file_path' => 'qa-proof.txt',
    'file_name' => 'qa-proof.txt', 'original_name' => 'qa-proof.txt', 'mime_type' => 'text/plain',
]);
file_put_contents(getenv('PRISM_QA_FIXTURES'), json_encode([
    'visibleProject' => $visibleTask->project_id,
    'visibleTask' => $visibleTask->id,
    'visibleDone' => $visibleTask->project->ticketStatuses()->where('is_completed', true)->value('id'),
    'attachment' => $attachment->id,
    'restrictedProject' => Project::where('ticket_prefix', 'RQA')->value('id'),
], JSON_THROW_ON_ERROR));
