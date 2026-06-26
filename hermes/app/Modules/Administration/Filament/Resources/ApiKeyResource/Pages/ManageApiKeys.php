<?php

namespace App\Modules\Administration\Filament\Resources\ApiKeyResource\Pages;

use App\Models\ApiKey;
use App\Modules\Administration\Filament\Resources\ApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;

class ManageApiKeys extends ManageRecords
{
    protected static string $resource = ApiKeyResource::class;

    protected static ?string $title = 'Integrations & API Keys';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->using(function (array $data) {
                    $name = $data['name'];
                    $expiresInDays = $data['expires_in_days'] ?? null;
                    
                    $result = ApiKey::generate($name, $expiresInDays ? (int) $expiresInDays : null);
                    
                    // Flash raw key to session to display to user after creation
                    session()->flash('raw_api_key', $result['raw_key']);
                    
                    return $result['api_key'];
                })
                ->after(function () {
                    $rawKey = session()->get('raw_api_key');
                    if ($rawKey) {
                        Notification::make()
                            ->warning()
                            ->title('API Key Created')
                            ->body("Make sure to copy your API key now. You won't be able to see it again:\n\n**{$rawKey}**")
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
