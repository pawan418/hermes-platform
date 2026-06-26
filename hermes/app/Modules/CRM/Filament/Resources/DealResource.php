<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Filament\Resources\DealResource\Pages\ManageDeals;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'CRM Systems';

    protected static ?string $navigationLabel = 'Sales Deals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('e.g., Enterprise Software SLA'),
                Forms\Components\Select::make('pipeline_id')
                    ->relationship('pipeline', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('lead_id')
                    ->relationship('lead', 'title')
                    ->placeholder('Select Source Lead')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('stage')
                    ->options([
                        'new' => 'New / Lead Ingestion',
                        'qualified' => 'Qualified Opportunity',
                        'proposal' => 'Proposal Pitch Sent',
                        'negotiation' => 'Contract Negotiation',
                        'won' => 'Closed Won',
                        'lost' => 'Closed Lost',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->default(0.00)
                    ->prefix('$'),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active Pipeline',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->required()
                    ->default('active'),
                Forms\Components\DateTimePicker::make('closed_at')
                    ->placeholder('Closed Date'),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignedAgent', 'name')
                    ->placeholder('Assign to Owner Agent')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('pipeline.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'new' => 'gray',
                        'qualified' => 'info',
                        'proposal' => 'warning',
                        'negotiation' => 'primary',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'warning',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedAgent.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->dateTime()
                    ->placeholder('Open')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'new' => 'New',
                        'qualified' => 'Qualified',
                        'proposal' => 'Proposal Sent',
                        'negotiation' => 'Negotiation',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ]),
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
            'index' => ManageDeals::route('/'),
        ];
    }
}
