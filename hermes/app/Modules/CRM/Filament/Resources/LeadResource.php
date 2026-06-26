<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Filament\Resources\LeadResource\Pages\ManageLeads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'CRM Systems';

    protected static ?string $navigationLabel = 'Incoming Leads';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('e.g. LSPL Academy admission inquiry'),
                Forms\Components\Select::make('source')
                    ->options([
                        'web' => 'Web Landing Page',
                        'whatsapp' => 'WhatsApp Cloud API',
                        'voice' => 'Voice call agent',
                        'manual' => 'Manual Lead',
                    ])
                    ->required()
                    ->default('web'),
                Forms\Components\Select::make('status')
                    ->options([
                        'new' => 'New Inbound',
                        'contacted' => 'Contact Established',
                        'qualified' => 'Qualified Opportunity',
                        'won' => 'Lead Won (Converted)',
                        'lost' => 'Lead Lost',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('0.00'),
                Forms\Components\Select::make('contact_id')
                    ->relationship('contact', 'first_name')
                    ->placeholder('Select Contact Person')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->placeholder('Select Target Company')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignedAgent', 'name')
                    ->placeholder('Assign to Sales Agent')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'qualified' => 'primary',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.first_name')
                    ->label('Contact')
                    ->placeholder('None'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('None'),
                Tables\Columns\TextColumn::make('assignedAgent.name')
                    ->label('Assigned Agent')
                    ->placeholder('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New Inbound',
                        'contacted' => 'Contact Established',
                        'qualified' => 'Qualified Opportunity',
                        'won' => 'Lead Won',
                        'lost' => 'Lead Lost',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'web' => 'Web Page',
                        'whatsapp' => 'WhatsApp',
                        'voice' => 'Voice',
                        'manual' => 'Manual',
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
            'index' => ManageLeads::route('/'),
        ];
    }
}
