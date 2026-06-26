<?php

namespace App\Modules\AICore\Filament\Resources\AgentResource\Pages;

use App\Modules\AICore\Filament\Resources\AgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAgents extends ManageRecords
{
    protected static string $resource = AgentResource::class;

    protected static ?string $title = 'AI Employee Agents';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver(),
        ];
    }
}
