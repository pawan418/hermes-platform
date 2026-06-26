<?php

namespace App\Modules\Knowledge\Filament\Resources\KnowledgeBaseResource\Pages;

use App\Modules\Knowledge\Filament\Resources\KnowledgeBaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBase extends CreateRecord
{
    protected static string $resource = KnowledgeBaseResource::class;

    protected static ?string $title = 'Create Knowledge Base';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
