<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoEndpoint extends ViewRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AmigoEndpointResource\Widgets\EndpointStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Widgets\EndpointStatsOverview::class,
            AmigoEndpointResource\Widgets\EndpointResponsesChart::class,
            // Widgets\EndpointResponsesChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            // Actions\Action::make('stats')
            //     ->url(AmigoEndpointResource::getUrl('stats', ['record' => $this->record])),
        ];
    }
}
