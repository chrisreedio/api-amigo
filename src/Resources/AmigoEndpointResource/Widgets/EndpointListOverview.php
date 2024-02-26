<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;

use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages\ListAmigoEndpoints;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EndpointListOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListAmigoEndpoints::class;
    }

    protected function getStats(): array
    {
        $slowestEndpoint = AmigoEndpoint::inRandomOrder()->first();
        $fastestEndpoint = AmigoEndpoint::inRandomOrder()->first();
        $mostActiveEndpoint = AmigoEndpoint::inRandomOrder()->first();

        return [
            Stat::make('Slowest Endpoint', $slowestEndpoint->name),
                // ->description('Average response time')
            Stat::make('Fastest Endpoint', $fastestEndpoint->name),
            Stat::make('Most Calls', $mostActiveEndpoint->name),
        ];
    }
}
