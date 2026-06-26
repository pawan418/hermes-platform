<?php

namespace App\Modules\CRM\Filament\Resources\CompanyResource\Pages;

use App\Modules\CRM\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'Client Companies';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
