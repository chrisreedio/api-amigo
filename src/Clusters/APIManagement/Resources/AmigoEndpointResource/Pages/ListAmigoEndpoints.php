<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages;

use Filament\Support\Enums\Width;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets;
use Filament\Resources\Pages\ListRecords;

class ListAmigoEndpoints extends ListRecords
{
    protected static string $resource = AmigoEndpointResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets\EndpointListOverview::class,
        ];
    }
}
