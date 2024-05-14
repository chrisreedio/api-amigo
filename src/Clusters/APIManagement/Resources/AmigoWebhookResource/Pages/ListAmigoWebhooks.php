<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use Filament\Resources\Pages\ListRecords;

class ListAmigoWebhooks extends ListRecords
{
    protected static string $resource = AmigoWebhookResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
