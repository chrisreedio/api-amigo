<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoWebhooks extends ListRecords
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
