<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Jobs\SyncGoogleCalendarTask;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    private array $assigneeIdsBeforeSave = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->assigneeIdsBeforeSave = $this->record
            ->assignees()
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $assignees = $this->data['assignees'] ?? null;

        if ($assignees === null) {
            return;
        }

        if (!is_array($assignees)) {
            throw ValidationException::withMessages([
                'data.assignees' => 'Invalid assignee selection.',
            ]);
        }

        $projectId = $this->data['project_id']
            ?? $this->record->project_id;

        $project = Project::find($projectId);

        if (!$project) {
            throw ValidationException::withMessages([
                'data.project_id' => 'The selected project is invalid.',
            ]);
        }

        $assigneeIds = array_values(
            array_unique(array_map('intval', $assignees))
        );

        if (empty($assigneeIds)) {
            return;
        }

        $validIds = $project
            ->members()
            ->whereIn('users.id', $assigneeIds)
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $invalidIds = array_diff($assigneeIds, $validIds);

        if (!empty($invalidIds)) {
            throw ValidationException::withMessages([
                'data.assignees' =>
                    'All assignees must be members of the selected project.',
            ]);
        }
    }

    protected function afterSave(): void
    {
        if (
            !config('services.google.calendar.enabled')
            || !config('services.google.calendar.auto_sync_enabled')
        ) {
            return;
        }

        $ticket = $this->record;

        $assigneeIdsAfterSave = $ticket
            ->assignees()
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $assigneesChanged =
            $this->assigneeIdsBeforeSave !== $assigneeIdsAfterSave;

        $taskDetailsChanged = $ticket->wasChanged([
            'due_date',
            'name',
            'description',
            'project_id',
        ]);

        // Avoid unnecessary Google Calendar requests.
        if (!$assigneesChanged && !$taskDetailsChanged) {
            return;
        }

        if (!$ticket->due_date) {
            $hasExistingEvent = $ticket
                ->googleCalendarEvent()
                ->whereNotNull('google_event_id')
                ->exists();

            if (!$hasExistingEvent) {
                return;
            }
        }

        SyncGoogleCalendarTask::dispatch(
            (int) $ticket->id
        )->afterCommit();
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Task updated')
            ->body('The task has been updated successfully.');
    }
}