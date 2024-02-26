<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoEndpoints extends ListRecords
{
    protected static string $resource = AmigoEndpointResource::class;

    protected ?string $maxContentWidth = 'full';

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
