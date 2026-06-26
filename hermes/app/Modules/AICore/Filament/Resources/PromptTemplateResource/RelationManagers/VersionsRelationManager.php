<?php

namespace App\Modules\AICore\Filament\Resources\PromptTemplateResource\RelationManagers;

use App\Modules\AICore\Models\PromptVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Prompt History & Version Control';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('content')
                    ->label('Prompt System Instructions')
                    ->required()
                    ->rows(8)
                    ->placeholder('Write your system prompt using {variable_name} placeholders...')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activate this version immediately')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version ID')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active Production')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Snippet')
                    ->limit(60),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('version', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        // Auto-increment version number
                        $data['version'] = $owner->versions()->max('version') + 1;
                        $data['created_by'] = auth()->id();
                        return $data;
                    })
                    ->after(function (PromptVersion $record, array $data) {
                        // If marked active, turn off other versions
                        if ($record->is_active) {
                            $record->promptTemplate->versions()
                                ->where('id', '!=', $record->id)
                                ->update(['is_active' => false]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->after(function (PromptVersion $record) {
                        if ($record->is_active) {
                            $record->promptTemplate->versions()
                                ->where('id', '!=', $record->id)
                                ->update(['is_active' => false]);
                        }
                    }),
                Tables\Actions\Action::make('activate')
                    ->label('Set Active')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (PromptVersion $record): bool => $record->is_active)
                    ->action(function (PromptVersion $record) {
                        $record->promptTemplate->versions()->update(['is_active' => false]);
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->success()
                            ->title('Prompt Switched')
                            ->body("Version v{$record->version} is now the active prompt.")
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Disallow bulk actions on version history
            ]);
    }
}
