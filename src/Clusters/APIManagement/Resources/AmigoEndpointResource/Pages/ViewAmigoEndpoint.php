<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAmigoEndpoint extends ViewRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected ?string $subheading = 'Aggregate stats may be delayed by up to 5 minutes.';

    public function getTitle(): string | Htmlable
    {
        return 'Endpoint: ' . $this->getRecord()->displayName;
    }

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
