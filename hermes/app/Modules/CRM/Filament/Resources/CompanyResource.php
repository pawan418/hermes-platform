<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Models\Company;
use App\Modules\CRM\Filament\Resources\CompanyResource\Pages\ManageCompanies;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'CRM Systems';

    protected static ?string $navigationLabel = 'Client Companies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('e.g. Longway Softronix'),
                Forms\Components\TextInput::make('industry')
                    ->maxLength(191)
                    ->placeholder('e.g. Artificial Intelligence'),
                Forms\Components\TextInput::make('website')
                    ->maxLength(191)
                    ->url()
                    ->placeholder('e.g. https://longwaysoftronix.com'),
                Forms\Components\TextInput::make('phone')
                    ->maxLength(191)
                    ->tel()
                    ->placeholder('Company Phone'),
                Forms\Components\Textarea::make('address')
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->placeholder('HQ Address...'),
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
                Tables\Columns\TextColumn::make('industry')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageCompanies::route('/'),
        ];
    }
}
