<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Resources\WebhookLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWebhookLog extends EditRecord
{
    protected static string $resource = WebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
