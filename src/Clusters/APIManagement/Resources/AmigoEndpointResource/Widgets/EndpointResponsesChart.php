<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets;

use ChrisReedIO\APIAmigo\Enums\ChartFilters;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Illuminate\Support\Collection;

use function now;

class EndpointResponsesChart extends ChartWidget
{
    protected static ?string $heading = 'Response Times';

    public ?string $filter = 'month';

    public ?AmigoEndpoint $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $curFilter = ! $activeFilter ? ChartFilters::Today : ChartFilters::from($activeFilter);

        $totalsTrend = $this->calculateTrend('total_requests', $curFilter);
        $failuresTrend = $this->calculateTrend('failed_requests', $curFilter);

        return [
            'datasets' => [
                // Totals
                [
                    'label' => 'Total Responses',
                    'data' => $totalsTrend->pluck('aggregate'),
                ],
                // Failures
                [
                    'label' => 'Failures',
                    'data' => $failuresTrend->pluck('aggregate'),
                    'borderColor' => 'rgb(' . Color::Rose[500] . ')',
                ],
            ],
            'labels' => $totalsTrend->pluck('date')->toArray(),
        ];
    }

    private function calculateTrend(string $column, ChartFilters $curFilter): Collection
    {
        $query = AmigoEndpointAggregate::query()
            ->where('endpoint_id', $this->record->id);

        return Trend::query($query)
            // ->dateColumn('window_start')
            ->between(
                start: $curFilter->getStartDate(),
                end: now()
            )
            ->perDay()
            // ->perHour()
            ->sum('total_requests');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return ChartFilters::filterArray();
    }
}
