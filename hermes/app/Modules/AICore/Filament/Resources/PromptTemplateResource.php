<?php

namespace App\Modules\AICore\Filament\Resources;

use App\Modules\AICore\Models\PromptTemplate;
use App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages\CreatePromptTemplate;
use App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages\EditPromptTemplate;
use App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages\ListPromptTemplates;
use App\Modules\AICore\Filament\Resources\PromptTemplateResource\RelationManagers\VersionsRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PromptTemplateResource extends Resource
{
    protected static ?string $model = PromptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'AI Orchestration';

    protected static ?string $navigationLabel = 'Prompt Manager';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\TagsInput::make('variables')
                    ->label('Declared Variables')
                    ->placeholder('Add variable (press Enter), e.g. company_name')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('variables')
                    ->badge()
                    ->color('info')
                    ->placeholder('None'),
                Tables\Columns\TextColumn::make('versions_count')
                    ->label('Total Versions')
                    ->counts('versions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromptTemplates::route('/'),
            'create' => CreatePromptTemplate::route('/create'),
            'edit' => EditPromptTemplate::route('/{record}/edit'),
        ];
    }
}
