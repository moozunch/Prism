<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\Attachment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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
                    ->mutateDataUsing(function (array $data): array {
                        $data['file_name'] = basename($data['file_path']);
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Attachment $record): string => route('attachments.view', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ]);
    }
}
