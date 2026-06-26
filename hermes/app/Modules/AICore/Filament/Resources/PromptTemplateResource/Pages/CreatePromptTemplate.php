<?php

namespace App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages;

use App\Modules\AICore\Filament\Resources\PromptTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromptTemplate extends CreateRecord
{
    protected static string $resource = PromptTemplateResource::class;

    protected static ?string $title = 'Create Prompt Template';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
