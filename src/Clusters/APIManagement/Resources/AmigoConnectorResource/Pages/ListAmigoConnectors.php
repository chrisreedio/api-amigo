<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;

use Filament\Support\Enums\Width;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource;
use Filament\Resources\Pages\ListRecords;

class ListAmigoConnectors extends ListRecords
{
    protected static string $resource = AmigoConnectorResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
