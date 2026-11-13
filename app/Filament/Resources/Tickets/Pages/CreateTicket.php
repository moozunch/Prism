<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function fillForm(): void
    {
        $copyFromId = request()->query('copy_from');

        if ($copyFromId) {
            $user = Auth::user();

            if (!$user instanceof User) {
                abort(403);
            }

            $userId = $user->id;

            $ticket = $user->hasRole('super_admin')
                ? Ticket::find($copyFromId)
                : Ticket::query()
                    ->where('id', $copyFromId)
                    ->where(function ($query) use ($userId) {
                        $query->whereHas(
                            'assignees',
                            function ($query) use ($userId) {
                                $query->where('users.id', $userId);
                            }
                        )
                        ->orWhere('created_by', $userId)
                        ->orWhereHas(
                            'project.members',
                            function ($query) use ($userId) {
                                $query->where('users.id', $userId);
                            }
                        );
                    })
                    ->first();

            if ($ticket) {
                $data = $ticket->toArray();

                unset(
                    $data['id'],
                    $data['uuid'],
                    $data['created_at'],
                    $data['updated_at'],
                    $data['created_by']
                );

                $data['assignees'] = $ticket->assignees()
                    ->pluck('users.id')
                    ->toArray();

                $this->form->fill($data);

                return;
            }
        }

        parent::fillForm();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function beforeCreate(): void
    {
        $assignees = $this->data['assignees'] ?? [];
        $projectId = $this->data['project_id'] ?? null;

        if (!is_array($assignees)) {
            throw ValidationException::withMessages([
                'data.assignees' => 'Invalid assignee selection.',
            ]);
        }

        $project = Project::find($projectId);

        if (!$project) {
            throw ValidationException::withMessages([
                'data.project_id' => 'The selected project is invalid.',
            ]);
        }

        $assigneeIds = array_values(array_unique(
            array_map('intval', $assignees)
        ));

        if (empty($assigneeIds)) {
            return;
        }

        $validIds = $project->members()
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

    protected function afterCreate(): void
    {
        $ticket = $this->record;


        if ($ticket->assignees()->exists()) {
            return;
        }

        $project = Project::find($ticket->project_id);

        $userId = Auth::id();

        if (!$userId) {
            return;
        }

        $currentUserIsMember = $project?->members()
            ->where('users.id', $userId)
            ->exists();

        if ($currentUserIsMember) {
            $ticket->assignees()
                ->syncWithoutDetaching([$userId]);

            Notification::make()
                ->info()
                ->title('Auto-assigned')
                ->body(
                    'No assignees selected. You have been automatically assigned to this task.'
                )
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        $referer = request()->header('referer');

        if ($referer && str_contains($referer, 'project-board-page')) {
            return '/admin/project-board-page';
        }

        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Task created')
            ->body('The task has been created successfully.');
    }
}