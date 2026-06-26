<?php

namespace App\Modules\Administration\Filament\Resources\AuditLogResource\Pages;

use App\Modules\Administration\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuditLogs extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;

    protected static ?string $title = 'System Compliance Audit Logs';

    protected function getHeaderActions(): array
    {
        return [
            // Read-only history has no manual creation trigger
        ];
    }
}
