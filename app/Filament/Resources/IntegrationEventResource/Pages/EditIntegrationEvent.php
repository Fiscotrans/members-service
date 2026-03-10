<?php

namespace App\Filament\Resources\IntegrationEventResource\Pages;

use App\Filament\Resources\IntegrationEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationEvent extends EditRecord
{
    protected static string $resource = IntegrationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
