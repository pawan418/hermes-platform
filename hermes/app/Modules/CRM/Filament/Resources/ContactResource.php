<?php

namespace App\Modules\CRM\Filament\Resources;

use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Filament\Resources\ContactResource\Pages\ManageContacts;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'CRM Systems';

    protected static ?string $navigationLabel = 'Client Contacts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(191)
                    ->placeholder('First Name'),
                Forms\Components\TextInput::make('last_name')
                    ->maxLength(191)
                    ->placeholder('Last Name'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(191)
                    ->placeholder('personal/work email'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(191)
                    ->placeholder('+1 (555) 000-0000'),
                Forms\Components\TextInput::make('job_title')
                    ->maxLength(191)
                    ->placeholder('e.g., Chief Technology Officer'),
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->placeholder('Select Employer Company')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('Freelance / No Company')
                    ->sortable(),
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
            'index' => ManageContacts::route('/'),
        ];
    }
}
