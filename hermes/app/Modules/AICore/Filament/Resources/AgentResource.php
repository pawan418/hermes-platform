<?php

namespace App\Modules\AICore\Filament\Resources;

use App\Modules\AICore\Models\Agent;
use App\Modules\AICore\Filament\Resources\AgentResource\Pages\ManageAgents;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'AI Orchestration';

    protected static ?string $navigationLabel = 'AI Employee Agents';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Profile')
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
                    ])->columns(2),

                Forms\Components\Section::make('LLM Model Configuration')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->options([
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic Claude',
                                'gemini' => 'Google Gemini',
                                'ollama' => 'Ollama (Local CPU/GPU)',
                            ])
                            ->required()
                            ->default('openai'),
                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->placeholder('e.g., gpt-4o, claude-3-5-sonnet, gemini-1.5-flash')
                            ->default('gpt-4o'),
                        Forms\Components\Slider::make('temperature')
                            ->min(0.0)
                            ->max(1.2)
                            ->step(0.1)
                            ->default(0.7),
                        Forms\Components\TextInput::make('max_tokens')
                            ->numeric()
                            ->required()
                            ->default(2048),
                    ])->columns(2),

                Forms\Components\Section::make('Instructions & System Prompts')
                    ->schema([
                        Forms\Components\Select::make('prompt_template_id')
                            ->label('Versioned Prompt Template')
                            ->relationship('promptTemplate', 'name')
                            ->placeholder('Select a dynamic prompt template (optional)'),
                        Forms\Components\Textarea::make('system_prompt')
                            ->label('Static System Prompt Fallback')
                            ->rows(6)
                            ->placeholder('Enter custom system instructions if not using a dynamic versioned prompt template...')
                            ->columnSpanFull(),
                    ])->columns(1),
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
                    ->color('info'),
                Tables\Columns\TextColumn::make('provider')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'openai' => 'success',
                        'anthropic' => 'warning',
                        'gemini' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('temperature')
                    ->numeric(1),
                Tables\Columns\TextColumn::make('promptTemplate.name')
                    ->label('Linked Template')
                    ->placeholder('Static Prompt'),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageAgents::route('/'),
        ];
    }
}
