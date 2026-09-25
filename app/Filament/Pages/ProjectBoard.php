<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class ProjectBoard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.pages.project-board';

    protected static ?string $title = 'Project Board';

    protected static ?string $navigationLabel = 'Project Board';

    protected static string|\UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 4;

    public function getSubheading(): ?string
    {
        return 'Kanban board for task management';
    }

    protected static ?string $slug = 'project-board/{project_id?}';

    public ?Project $selectedProject = null;

    public Collection $projects;

    // ticketStatuses is now a computed property - see getTicketStatusesProperty()

    public ?Ticket $selectedTicket = null;

    public ?int $selectedProjectId = null;

    public array $sortOrders = [];

    public array $selectedUserIds = [];

    public Collection $projectUsers;

    public string $searchProject = '';

    public function mount($project_id = null): void
    {
        if (auth()->user()->hasRole(['super_admin'])) {
            $this->projects = Project::orderByRaw('pinned_date IS NULL')
                ->orderBy('pinned_date', 'desc')
                ->orderBy('name')
                ->get();
        } else {
            $this->projects = auth()->user()->projects()
                ->orderByRaw('pinned_date IS NULL')
                ->orderBy('pinned_date', 'desc')
                ->orderBy('name')
                ->get();
        }

        if ($project_id) {
            $projectId = (int) $project_id;

            if (! $this->projects->contains('id', $projectId)) {
                Notification::make()
                    ->title('Project Not Found')
                    ->body('The selected project was not found or you do not have access to it.')
                    ->danger()
                    ->send();

                $this->projectUsers = collect();

                return;
            }

            $this->selectedProjectId = $projectId;
            $this->selectedProject = Project::find($projectId);
            $this->loadProjectUsers();
        } else {
            $this->projectUsers = collect();
        }
    }

    public function getFilteredProjectsProperty(): Collection
    {
        if (empty($this->searchProject)) {
            return $this->projects;
        }

        return $this->projects->filter(function ($project) {
            return str_contains(strtolower($project->name), strtolower($this->searchProject)) ||
                str_contains(strtolower($project->ticket_prefix ?? ''), strtolower($this->searchProject));
        });
    }

    public function updatedSelectedProjectId($value): void
    {
        if ($value) {
            $this->selectProject($value);
        } else {
            $this->selectedProject = null;
            $this->projectUsers = collect();
            $this->selectedUserIds = [];

            // Use wire:navigate for SPA-like navigation
            $url = static::getUrl();
            $this->js("Livewire.navigate('{$url}')");
        }
    }

    public function selectProject(int $projectId): void
    {
        if (! $this->projects->contains('id', $projectId)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have access to this project.')
                ->danger()
                ->send();

            return;
        }

        $this->selectedTicket = null;
        $this->ticketStatuses = collect();
        $this->selectedProjectId = $projectId;
        $this->selectedProject = Project::with('tickets')->find($projectId);
        $this->selectedUserIds = [];

        $this->loadProjectUsers();

        // Use wire:navigate for SPA-like navigation
        $url = static::getUrl(['project_id' => $projectId]);
        $this->js("Livewire.navigate('{$url}')");
    }

    #[Computed]
    public function ticketStatuses(): Collection
    {
        if (! $this->selectedProject) {
            return collect();
        }

        $statuses = $this->selectedProject->ticketStatuses()
            ->with([
                'tickets' => function ($query) {
                    $query->with([
                        'assignees:id,name',
                        'status:id,name,color,is_completed',
                        'priority:id,name,color',
                        'creator:id,name',
                    ])
                        ->select('id', 'project_id', 'ticket_status_id', 'priority_id', 'name', 'description', 'uuid', 'due_date', 'created_at', 'updated_at', 'created_by')
                        ->when(! empty($this->selectedUserIds), function ($query) {
                            $query->whereHas('assignees', function ($assigneeQuery) {
                                $assigneeQuery->whereIn('users.id', $this->selectedUserIds);
                            });
                        })
                        ->orderByDesc('created_at')
                        ->orderByDesc('id');
                },
            ])
            ->select('id', 'project_id', 'name', 'color', 'sort_order', 'is_completed')
            ->orderBy('sort_order')
            ->get();

        $statuses->each(function ($status) {
            $sortOrder = $this->sortOrders[$status->id] ?? 'date_created_newest';
            $status->tickets = $this->applySorting($status->tickets, $sortOrder);
        });

        return $statuses;
    }

    public function loadTicketStatuses(): void
    {
        // Force recompute of ticketStatuses by clearing cache
        unset($this->ticketStatuses);
    }

    public function loadProjectUsers(): void
    {
        if (! $this->selectedProject) {
            $this->projectUsers = collect();

            return;
        }

        // Get only users who are assigned to tickets in this project
        $ticketAssigneeIds = $this->selectedProject->tickets()
            ->with('assignees')
            ->get()
            ->flatMap(function ($ticket) {
                return $ticket->assignees->pluck('id');
            })
            ->unique()
            ->filter();

        $this->projectUsers = User::whereIn('id', $ticketAssigneeIds)
            ->orderBy('name')
            ->get();
    }

    public function updatedSelectedUserIds(): void
    {
        $this->loadTicketStatuses();
    }

    public function clearUserFilter(): void
    {
        $this->selectedUserIds = [];
        $this->loadTicketStatuses();
    }

    public function setSortOrder($statusId, $sortOrder)
    {
        $this->sortOrders[$statusId] = $sortOrder;
        $this->loadTicketStatuses();
    }

    private function applySorting($tickets, $sortOrder)
    {
        switch ($sortOrder) {
            case 'date_created_newest':
                // Query already ordered by created_at DESC, id DESC - just reset keys
                return $tickets->values();
            case 'date_created_oldest':
                return $tickets->sortBy(function ($ticket) {
                    return $ticket->created_at->timestamp . '_' . str_pad($ticket->id, 10, '0', STR_PAD_LEFT);
                })->values();
            case 'card_name_alphabetical':
                return $tickets->sortBy('name')->values();
            case 'due_date':
                return $tickets->sortBy(function ($ticket) {
                    return $ticket->due_date ?? '9999-12-31';
                })->values();
            case 'priority':
                return $tickets->sortBy(function ($ticket) {
                    return $ticket->priority ? $ticket->priority->id : 999;
                })->values();
            default:
                // Default is same as date_created_newest - query already sorted
                return $tickets->values();
        }
    }

    #[On('ticket-moved')]
    public function moveTicket($ticketId, $newStatusId): void
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket && $ticket->project_id === $this->selectedProject?->id) {
            if (! $this->canManageTicket($ticket)) {
                Notification::make()
                    ->title('Permission Denied')
                    ->body('You do not have permission to move this task.')
                    ->danger()
                    ->send();

                return;
            }

            $ticket->update([
                'ticket_status_id' => $newStatusId,
            ]);

            $this->loadTicketStatuses();

            $this->dispatch('ticket-updated');

            Notification::make()
                    ->title('Task Updated')
                ->success()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    public function showTicketDetails(int $ticketId): void
    {
        $ticket = Ticket::with(['assignees', 'status', 'project', 'priority'])->find($ticketId);

        if (! $ticket) {
            Notification::make()
                ->title('Task Not Found')
                ->danger()
                ->send();

            return;
        }

        $url = TicketResource::getUrl('view', ['record' => $ticketId]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function closeTicketDetails(): void
    {
        $this->selectedTicket = null;
    }

    public function editTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $this->canEditTicket($ticket)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to edit this task.')
                ->danger()
                ->send();

            return;
        }

        $this->redirect(TicketResource::getUrl('edit', ['record' => $ticketId]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->name('ticket_on_board')
                ->label('New Task')
                ->icon('heroicon-m-plus')
                ->visible(fn () => $this->selectedProject !== null && auth()->user()->can('create_ticket'))
                ->schema(fn ($schema) => TicketResource::form($schema)
                    ->columns(3)
                )
                ->model(Ticket::class)
                ->fillForm(function () {
                    $assignees = [];

                    // Auto-assign current user if they're a project member
                    if ($project = $this->selectedProject) {
                        $isCurrentUserMember = $project->members()->where('users.id', auth()->id())->exists();
                        $assignees = $isCurrentUserMember ? [auth()->id()] : [];
                    }

                    return [
                        'project_id' => $this->selectedProject?->id,
                        'ticket_status_id' => $this->ticketStatuses?->first()?->id,
                        'assignees' => $assignees,
                    ];

                })
                ->action(function (array $data, $schema) {
                    $data['created_by'] = auth()->id();

                    $model = $schema->getModel();

                    $record = $model::create($data);

                    $schema->model($record)->saveRelationships();

                    Notification::make()
                        ->title('Task Created')
                        ->body('The task has been created successfully.')
                        ->success()
                        ->send();
                }),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard')
                ->color('warning'),
            Action::make('filter_users')
                ->label('Filter by User')
                ->icon('heroicon-m-user-group')
                ->visible(fn () => $this->selectedProject !== null && $this->projectUsers->isNotEmpty())
                ->schema([
                    CheckboxList::make('selectedUserIds')
                        ->label('Select Users to Filter')
                        ->options(fn () => $this->projectUsers->pluck('name', 'id')->toArray())
                        ->columns(2)
                        ->searchable()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data) {
                    $this->selectedUserIds = $data['selectedUserIds'] ?? [];
                    $this->loadTicketStatuses();

                    $userCount = count($this->selectedUserIds);
                    if ($userCount > 0) {
                        Notification::make()
                            ->title('Filter Applied')
                            ->body("Showing tasks for {$userCount} selected user(s)")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Filter Cleared')
                            ->body('Showing all tasks')
                            ->info()
                            ->send();
                    }
                })
                ->fillForm([
                    'selectedUserIds' => $this->selectedUserIds,
                ])
                ->modalWidth('md')
                ->color('info'),
        ];
    }

    private function canViewTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        if (! auth()->user()->can('view_ticket')) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id()
            || $ticket->assignees()->where('users.id', auth()->id())->exists();
    }

    private function canEditTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        // Check Filament Shield permission for updating tickets
        if (! auth()->user()->can('update_ticket')) {
            return false;
        }

        // Additional business logic: user can edit if they are:
        // 1. Super admin (already covered by permission above)
        // 2. The ticket creator
        // 3. Assigned to the ticket
        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id()
            || $ticket->assignees()->where('users.id', auth()->id())->exists();
    }

    private function canManageTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }
        if (! auth()->user()->can('update_ticket')) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id()
            || $ticket->assignees()->where('users.id', auth()->id())->exists();
    }

    public function canMoveTickets(): bool
    {
        return auth()->user()->can('update_ticket');
    }
}
