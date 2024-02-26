<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;

use ChrisReedIO\APIAmigo\Enums\ChartFilters;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Illuminate\Support\Carbon;

class EndpointResponsesChart extends ChartWidget
{
    protected static ?string $heading = 'Responses';

    public ?string $filter = 'month';

    public ?AmigoEndpoint $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        if (!$activeFilter) {
            $curFilter = ChartFilters::Today;
        } else {
            $curFilter = ChartFilters::from($activeFilter);
        }

        // Totals per month
        $query = AmigoEndpointAggregate::where('endpoint_id', $this->record->id);
        $totalsTrend = Trend::query($query)
            ->dateColumn('window_start')
            ->between(
                start: $curFilter->getStartDate(),
                end: now()
            )
            ->perDay()
            ->sum('total_requests');

        $totals = [
            'label' => 'Total Responses',
            'data' => $totalsTrend->pluck('aggregate'),
        ];

        // Failures
        $failuresTrend = Trend::query($query)
            ->dateColumn('window_start')
            ->between(
                start: $curFilter->getStartDate(),
                end: now()
            )
            ->perDay()
            ->sum('failed_requests');

        $failures = [
            'label' => 'Failures',
            'data' => $failuresTrend->pluck('aggregate'),
            'borderColor' => 'rgb('. Color::Rose[500] .')',
        ];

        $labels = $totalsTrend->pluck('date')->toArray();

        // dd($trend);


        return [
            'datasets' => [
                $totals,
                $failures,
            ],
            'labels' => $labels,
        ];
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
