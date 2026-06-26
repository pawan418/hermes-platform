<?php

namespace App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages;

use App\Modules\AICore\Filament\Resources\PromptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromptTemplates extends ListRecords
{
    protected static string $resource = PromptTemplateResource::class;

    protected static ?string $title = 'System Prompts Templates';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
