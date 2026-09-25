<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Ticket;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class Reporting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';
    protected static ?string $navigationLabel = 'Reporting';
    protected static ?string $title = 'Task Reporting';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.reporting';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view_any_ticket');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getReportQuery())
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.status')
                    ->label('Project Status')
                    ->badge(),
                TextColumn::make('project_progress')
                    ->label('Progress')
                    ->state(fn (Ticket $record): string => $record->project
                        ? $record->project->progress_percentage . '%'
                        : '0%'),
                TextColumn::make('name')
                    ->label('Task')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status.name')
                    ->label('Task Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Deadline')
                    ->date()
                    ->sortable(),
                TextColumn::make('assignees.name')
                    ->label('Assignee')
                    ->badge()
                    ->separator(','),
                TextColumn::make('priority.name')
                    ->label('Priority')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn (): array => $this->getVisibleProjectQuery()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('ticket_status_id')
                    ->label('Task Status')
                    ->relationship('status', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('period')
                    ->label('Date Period')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '<=', $date));
                    }),
            ])
            ->emptyStateHeading('No Task Data')
            ->emptyStateDescription('No tasks match the selected reporting filters.')
            ->defaultSort('due_date', 'asc')
            ->paginated([10, 25, 50]);
    }

    protected function getReportQuery(): Builder
    {
        $query = Ticket::query()->with(['project', 'status', 'assignees', 'priority']);

        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            $query->whereHas('project.members', function (Builder $query): void {
                $query->where('users.id', auth()->id());
            });
        }

        return $query;
    }

    protected function getVisibleProjectQuery(): Builder
    {
        $query = Project::query();

        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            $query->whereHas('members', function (Builder $query): void {
                $query->where('users.id', auth()->id());
            });
        }

        return $query;
    }
}