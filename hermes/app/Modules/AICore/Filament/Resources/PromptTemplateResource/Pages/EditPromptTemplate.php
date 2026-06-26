<?php

namespace App\Modules\AICore\Filament\Resources\PromptTemplateResource\Pages;

use App\Modules\AICore\Filament\Resources\PromptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromptTemplate extends EditRecord
{
    protected static string $resource = PromptTemplateResource::class;

    protected static ?string $title = 'Edit Prompt Template';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
