<?php

namespace App\Modules\Knowledge\Filament\Resources\KnowledgeBaseResource\Pages;

use App\Modules\Knowledge\Filament\Resources\KnowledgeBaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBases extends ListRecords
{
    protected static string $resource = KnowledgeBaseResource::class;

    protected static ?string $title = 'Company Knowledge Bases';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
