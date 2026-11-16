<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\Attachment;
use App\Models\Project;
use App\Services\GoogleDriveDeletionService;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('file_path')
                ->label('File')
                ->disk('attachments')
                ->directory('uploads')
                ->visibility('private')
                ->storeFileNamesIn('original_name')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('storage_provider')
                    ->label('Storage')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            $state === 'google_drive'
                                ? 'Google Drive'
                                : 'Local'
                    ),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->placeholder('Unknown'),

                TextColumn::make('user.name')
                    ->label('Uploaded By')
                    ->placeholder('System'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Attachment')
                    ->mutateDataUsing(
                        function (
                            array $data,
                            RelationManager $livewire
                        ): array {
                            $filePath = $data['file_path'] ?? null;

                            if (
                                !is_string($filePath)
                                || $filePath === ''
                            ) {
                                throw new RuntimeException(
                                    'No attachment file was uploaded.'
                                );
                            }

                            $originalName = $data['original_name']
                                ?? basename($filePath);

                            if (is_array($originalName)) {
                                $originalName = (string) (
                                    reset($originalName) ?: ''
                                );
                            }

                            $originalName = (string) $originalName;

                            if ($originalName === '') {
                                $originalName = basename($filePath);
                            }

                            $data['original_name'] = $originalName;
                            $data['file_name'] = basename($filePath);
                            $data['user_id'] = Auth::id();

                            if (
                                !config(
                                    'services.google.drive.enabled',
                                    false
                                )
                            ) {
                                $data['storage_provider'] = 'local';

                                return $data;
                            }

                            $project = $livewire->getOwnerRecord();

                            if (!$project instanceof Project) {
                                throw new RuntimeException(
                                    'Unable to identify the attachment project.'
                                );
                            }

                            $uploaded = app(
                                GoogleDriveService::class
                            )->uploadAttachment(
                                $filePath,
                                $originalName,
                                $project
                            );

                            $data['storage_provider'] = 'google_drive';
                            $data['google_drive_file_id'] =
                                $uploaded['file_id'];
                            $data['google_drive_url'] =
                                $uploaded['url'];
                            $data['mime_type'] =
                                $uploaded['mime_type'];

                            // Local staging cleanup comes next.
                            return $data;
                        }
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn (Attachment $record): string =>
                            route('attachments.view', $record)
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn (Attachment $record): bool =>
                            Gate::allows('view', $record)
                    ),

                Action::make('openDrive')
                    ->label('Open in Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(
                        fn (Attachment $record): ?string =>
                            $record->google_drive_url
                    )
                    ->openUrlInNewTab()
                    ->visible(
                        fn (Attachment $record): bool =>
                            $record->isGoogleDrive()
                            && filled($record->google_drive_url)
                            && Gate::allows('view', $record)
                    ),

                // Existing local attachment deletion.
                DeleteAction::make()
                    ->visible(
                        fn (Attachment $record): bool =>
                            $record->isLocal()
                            && Gate::allows('delete', $record)
                    ),

                // Google Drive attachment deletion.
                Action::make('deleteDrive')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Google Drive Attachment')
                    ->modalDescription(
                        'This file will be moved to Google Drive Trash. '
                        . 'Its PRISM attachment record will be removed '
                        . 'only after Google confirms the operation.'
                    )
                    ->modalSubmitActionLabel('Move to Trash')
                    ->visible(
                        fn (Attachment $record): bool =>
                            $record->isGoogleDrive()
                            && Gate::allows('delete', $record)
                    )
                    ->action(
                        function (Attachment $record): void {
                            // Verify permission again before deletion.
                            Gate::authorize('delete', $record);

                            try {
                                app(
                                    GoogleDriveDeletionService::class
                                )->moveToTrash($record);

                            } catch (Throwable $exception) {
                                Log::warning(
                                    'Google Drive project attachment deletion failed.',
                                    [
                                        'attachment_id' => $record->id,
                                        'exception_type' =>
                                            get_class($exception),
                                    ]
                                );

                                Notification::make()
                                    ->danger()
                                    ->title('Unable to delete attachment')
                                    ->body(
                                        'Google Drive could not move the file '
                                        . 'to Trash. The PRISM record was kept.'
                                    )
                                    ->send();

                                return;
                            }

                            try {
                                if (!$record->delete()) {
                                    throw new RuntimeException(
                                        'PRISM attachment record was not deleted.'
                                    );
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Attachment deleted')
                                    ->body(
                                        'The file was moved to Google Drive '
                                        . 'Trash and removed from PRISM.'
                                    )
                                    ->send();

                            } catch (Throwable $exception) {
                                Log::error(
                                    'Google Drive file trashed but '
                                    . 'PRISM project attachment deletion failed.',
                                    [
                                        'attachment_id' => $record->id,
                                        'exception_type' =>
                                            get_class($exception),
                                    ]
                                );

                                Notification::make()
                                    ->danger()
                                    ->title('Attachment needs attention')
                                    ->body(
                                        'The file was moved to Google Drive '
                                        . 'Trash, but its PRISM record could '
                                        . 'not be removed. Do not retry '
                                        . 'until its status is checked.'
                                    )
                                    ->send();
                            }
                        }
                    ),
            ]);
    }
}