<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use Filament\Support\Enums\Width;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use Filament\Resources\Pages\ListRecords;

class ListAmigoWebhooks extends ListRecords
{
    protected static string $resource = AmigoWebhookResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
