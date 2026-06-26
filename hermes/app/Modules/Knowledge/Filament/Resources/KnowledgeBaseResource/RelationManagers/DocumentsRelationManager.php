<?php

namespace App\Modules\Knowledge\Filament\Resources\KnowledgeBaseResource\RelationManagers;

use App\Modules\Knowledge\Jobs\ProcessDocumentJob;
use App\Modules\Knowledge\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Knowledge Documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Document Name')
                    ->placeholder('e.g. Employee Handbook (optional, defaults to filename)')
                    ->maxLength(191),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Upload File')
                    ->disk('s3')
                    ->directory('documents')
                    ->visibility('private')
                    ->required()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
                        'text/plain',
                        'text/markdown',
                        'text/html'
                    ])
                    ->maxSize(51200) // 50MB max file size
                    ->helperText('Supported formats: PDF, Word (DOCX), TXT, MD, HTML (Max 50MB)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->upperCase(),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(function (int $state) {
                        if ($state < 1024) return $state . ' B';
                        if ($state < 1048576) return round($state / 1024, 1) . ' KB';
                        return round($state / 1048576, 1) . ' MB';
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'indexed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('chunks_count')
                    ->label('Vector Chunks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $filePath = $data['file_path'];
                        $disk = Storage::disk('s3');

                        // Fill in file metadata from MinIO
                        $data['file_size'] = $disk->size($filePath);
                        $data['file_type'] = pathinfo($filePath, PATHINFO_EXTENSION);
                        $data['status'] = 'pending';

                        if (empty($data['name'])) {
                            $data['name'] = basename($filePath);
                        }

                        return $data;
                    })
                    ->after(function (Document $record) {
                        // Dispatch the background parsing and indexing job
                        ProcessDocumentJob::dispatch($record);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('reindex')
                    ->label('Re-index')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Document $record) {
                        $record->update(['status' => 'pending']);
                        ProcessDocumentJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Re-indexing Queued')
                            ->body("Document '{$record->name}' is now being re-indexed in the background.")
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
