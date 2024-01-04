<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoWebhook extends ViewRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
