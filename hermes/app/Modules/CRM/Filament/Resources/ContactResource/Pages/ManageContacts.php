<?php

namespace App\Modules\CRM\Filament\Resources\ContactResource\Pages;

use App\Modules\CRM\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageContacts extends ManageRecords
{
    protected static string $resource = ContactResource::class;

    protected static ?string $title = 'Client Contacts';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
