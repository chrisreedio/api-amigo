<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoEndpoint extends ViewRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\EndpointStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Widgets\EndpointStatsOverview::class,
            Widgets\EndpointResponsesChart::class,
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
