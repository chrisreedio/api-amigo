<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets;

use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use Filament\Support\Colors\Color;
use Flowframe\Trend\Trend;
use function now;

class EndpointResponsesTableChart extends \LaraZeus\InlineChart\InlineChartWidget
{
    protected static ?string $heading = 'Responses';

    // public ?string $filter = 'week';

    // public ?AmigoEndpoint $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->subMonth()->startOfDay();
        // Totals per month
        $query = AmigoEndpointAggregate::where('endpoint_id', $this->record->id);
        $totalsTrend = Trend::query($query)
            ->dateColumn('window_start')
            ->between(
                start: $start,
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
                start: $start,
                end: now()
            )
            ->perDay()
            ->sum('failed_requests');

        $failures = [
            'label' => 'Failures',
            'data' => $failuresTrend->pluck('aggregate'),
            'borderColor' => 'rgb(' . Color::Rose[500] . ')',
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
}
