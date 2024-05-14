<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Widgets;

use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

use function number_format;

class EndpointStatsOverview extends BaseWidget
{
    public ?AmigoEndpoint $record = null;

    protected function getStats(): array
    {
        // Calculate the total number of requests
        $total = $this->record->aggregates->sum('total_requests');

        // Calculate the average response time -- TODO: Replace with P90 (or one of the other percentiles)
        $tempAverage = number_format(($this->record->aggregates->average('avg_duration') * 1000 ?? 0)) . 'ms';

        // Calculate the success percent from the total and failed counts
        $totalSuccesses = $this->record->aggregates->sum('successful_requests');
        if ($total === 0) {
            // $successPercent = number_format(100, 1);
            $successPercent = 'N/A';
            $successColor = Color::Gray[500];
        } else {
            $successPercent = number_format(($totalSuccesses / $total) * 100, 1);
            $successColor = match (true) {
                $successPercent > 90 => Color::Green[500],
                $successPercent > 80 => Color::Yellow[500],
                default => Color::Red[500],
            };
            $successPercent .= '%';
        }

        return [
            Stat::make('Total Responses', number_format($total)),
            Stat::make('P90 Response Time', $tempAverage),
            Stat::make('Success Rate', $successPercent)
                ->value(new HtmlString('<span style="color: rgb(' . $successColor . ')">' . $successPercent . '</span>')),
        ];
    }
}
