<?php

namespace App\Modules\CRM\Filament\Resources\LeadResource\Pages;

use App\Modules\CRM\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected static ?string $title = 'Sales Leads';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
