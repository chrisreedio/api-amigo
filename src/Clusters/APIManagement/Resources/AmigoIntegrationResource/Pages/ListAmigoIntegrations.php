<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages;

use Filament\Support\Enums\Width;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use Filament\Resources\Pages\ListRecords;

class ListAmigoIntegrations extends ListRecords
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
