<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Resources\WebhookLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookLog extends CreateRecord
{
    protected static string $resource = WebhookLogResource::class;
}
