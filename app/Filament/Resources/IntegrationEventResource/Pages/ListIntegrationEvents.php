<?php

namespace App\Filament\Resources\IntegrationEventResource\Pages;

use App\Filament\Resources\IntegrationEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationEvents extends ListRecords
{
    protected static string $resource = IntegrationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
