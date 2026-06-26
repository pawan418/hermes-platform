<?php

namespace App\Modules\CRM\Filament\Resources\DealResource\Pages;

use App\Modules\CRM\Filament\Resources\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeals extends ManageRecords
{
    protected static string $resource = DealResource::class;

    protected static ?string $title = 'Sales Deals & Pipelines';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
