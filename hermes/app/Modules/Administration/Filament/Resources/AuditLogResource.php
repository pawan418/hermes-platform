<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Models\AuditLog;
use App\Modules\Administration\Filament\Resources\AuditLogResource\Pages\ManageAuditLogs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Audit Logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('created_at')
                    ->label('Timestamp')
                    ->disabled(),
                Forms\Components\TextInput::make('user.name')
                    ->label('User')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('old_values')
                    ->label('Old Values')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('new_values')
                    ->label('New Values')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match(true) {
                        str_starts_with($state, 'DELETE') => 'danger',
                        str_starts_with($state, 'POST') => 'success',
                        str_starts_with($state, 'PUT') || str_starts_with($state, 'PATCH') => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'POST' => 'Create Operations (POST)',
                        'PUT' => 'Update Operations (PUT)',
                        'PATCH' => 'Update Operations (PATCH)',
                        'DELETE' => 'Delete Operations (DELETE)',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->where('action', 'like', $data['value'] . ' %');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),
            ])
            ->bulkActions([
                // Read-only log history has no bulk modifications
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }
}
