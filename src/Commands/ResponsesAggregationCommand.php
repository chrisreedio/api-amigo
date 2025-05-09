<?php

namespace ChrisReedIO\APIAmigo\Commands;

use Carbon\CarbonPeriod;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function array_chunk;
use function array_filter;
use function collect;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;
use function microtime;
use function number_format;

class ResponsesAggregationCommand extends Command
{
    protected $signature = 'responses:aggregate {startDate?} {endDate?} {--regenerate} {--bulk} {--all}';

    protected $description = 'Aggregate responses for reporting purposes.';

    const int INTERVAL = 60;

    const string INTERVAL_UNITS = 'minute';

    public function handle(): void
    {
        // If --all is specified, find the earliest response date
        if ($this->option('all')) {
            $earliestDate = AmigoResponse::query()
                ->selectRaw('MIN(created_at) as earliest_date')
                ->value('earliest_date');

            if (! $earliestDate) {
                $this->error('No responses found to aggregate.');

                return;
            }

            $this->input->setArgument('startDate', Carbon::parse($earliestDate)->toDateString());
            $this->input->setArgument('endDate', Carbon::yesterday()->endOfDay()->toDateTimeString());

            // Force bulk processing for --all
            $this->input->setOption('bulk', true);
        }

        $period = $this->calculatePeriod();

        if (! $period) {
            return;
        }

        // If bulk processing is requested, process in chunks
        if ($this->option('bulk')) {
            $this->processBulk($period);

            return;
        }

        $start = microtime(true);

        $stats = progress(
            label: 'Calculating intervals...',
            steps: $period,
            callback: function ($date, $progress) {
                $end = $date->copy()->addMinutes(self::INTERVAL);
                $progress
                    ->label("Calculating intervals for {$date->toFormattedDateString()}")
                    ->hint("Processing window from {$date->format('H:i')} to {$end->format('H:i')}...");

                $window = $this->processWindow($date, $end);

                return $window->toArray();
            },
            hint: 'This may take some time.',
        );

        $stats = collect($stats);

        $processingTime = number_format(microtime(true) - $start, 2);
        $this->info("Calculated {$stats->count()} intervals in {$processingTime} seconds.");

        $total = 0;
        $startTime = microtime(true);
        $stats->each(function ($day) use (&$total) {
            $total += AmigoEndpointAggregate::insertOrIgnore($day);
        });
        $processingTime = number_format(microtime(true) - $startTime, 2);
        if ($total === 0) {
            warning('No new endpoint response aggregations were inserted.');
        } else {
            outro("Inserted {$total} endpoint response " . Str::plural('aggregation', $total) . " in {$processingTime} seconds.");
        }
    }

    private function processBulk(CarbonPeriod $period): void
    {
        // Process in 30-day chunks to manage memory
        $chunkSize = 30;
        $days = $period->toArray();
        $chunks = array_chunk($days, $chunkSize);

        $totalProcessed = 0;
        $totalInserted = 0;

        foreach ($chunks as $index => $chunk) {
            $chunkStart = $chunk[0];
            $chunkEnd = end($chunk);

            info('Processing chunk ' . ($index + 1) . ' of ' . count($chunks) .
                " ({$chunkStart->toFormattedDateString()} to {$chunkEnd->toFormattedDateString()})");

            $chunkPeriod = CarbonPeriod::create($chunkStart, $chunkEnd);

            $start = microtime(true);
            $stats = collect();

            foreach ($chunkPeriod as $date) {
                $end = $date->copy()->addMinutes(self::INTERVAL);
                $window = $this->processWindow($date, $end);
                $stats = $stats->concat($window);
                $totalProcessed++;
            }

            $processingTime = number_format(microtime(true) - $start, 2);
            $this->info("Processed {$totalProcessed} intervals in {$processingTime} seconds.");

            // Insert the chunk's data
            $startTime = microtime(true);
            $chunkTotal = 0;
            $stats->each(function ($day) use (&$chunkTotal) {
                $chunkTotal += AmigoEndpointAggregate::insertOrIgnore($day);
            });
            $totalInserted += $chunkTotal;

            $processingTime = number_format(microtime(true) - $startTime, 2);
            if ($chunkTotal === 0) {
                warning('No new aggregations inserted for this chunk.');
            } else {
                info("Inserted {$chunkTotal} aggregations in {$processingTime} seconds.");
            }
        }

        outro("Bulk processing complete. Processed {$totalProcessed} intervals, inserted {$totalInserted} aggregations.");
    }

    private function processWindow(Carbon $start, Carbon $end)
    {
        $maxRequestsPerMinute = $this->getRequestsPerMinute($start);
        $responses = AmigoResponse::query()
            ->selectRaw(implode(', ', array_filter([
                'endpoint_id',
                'amigo_connectors.integration_id',
                'COUNT(amigo_responses.id) as total_requests',
                'SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as successful_requests',
                'SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed_requests',
                'AVG(duration) as avg_duration',
                'MIN(duration) as min_duration',
                'MAX(duration) as max_duration',
                'PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY duration) as p50_duration',
                'PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY duration) as p75_duration',
                'PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration) as p95_duration',
                'PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY duration) as p99_duration',
                config('api-amigo.tdigest.enabled', false) ? 'tdigest(duration, 100) AS duration_histogram' : null,
            ])))
            ->whereBetween('amigo_responses.created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->join('amigo_endpoints', 'amigo_endpoints.id', '=', 'amigo_responses.endpoint_id')
            ->join('amigo_connectors', 'amigo_connectors.id', '=', 'amigo_endpoints.connector_id')
            ->groupBy(['endpoint_id', 'amigo_connectors.integration_id'])
            ->get();

        return $responses->map(function ($response) use ($maxRequestsPerMinute, $start) {
            $response->max_requests_per_minute = $maxRequestsPerMinute[$response->endpoint_id] ?? 0;
            $response->window_start = $start->toDateTimeString();
            $response->interval = self::INTERVAL * 60;
            $response->created_at = Carbon::now();

            return $response;
        });
    }

    private function getRequestsPerMinute(Carbon $start): Collection
    {
        $requestsPerMinute = AmigoResponse::select([
            'endpoint_id',
            DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI') as minute"),
            DB::raw('COUNT(*) as requests_per_minute'),
        ])
            ->whereBetween('created_at', [
                $start->toDateTimeString(),
                $start->copy()->addMinutes(self::INTERVAL)->toDateTimeString(),
            ])
            ->groupBy('endpoint_id', 'minute')
            ->get();

        return $requestsPerMinute->groupBy('endpoint_id')
            ->map(function ($items) {
                return $items->max('requests_per_minute');
            });
    }

    private function calculatePeriod(): ?CarbonPeriod
    {
        $start = $this->argument('startDate');
        $end = $this->argument('endDate');

        if (! $start) {
            // Set start to Jan 1st 2024
            $start = '2024-01-01';
        }

        $carbonStart = Carbon::parse($start)->startOfDay();
        if ($end === null) {
            $carbonEnd = $carbonStart->copy()->endOfDay();
        } else {
            $carbonEnd = ($end === 'today') ? Carbon::now() : Carbon::parse($end);
        }

        if ($carbonEnd > Carbon::now()) {
            warning('End date is in the future. Using today as the end date.');
            $carbonEnd = Carbon::now();
        }

        if ($start && $end) {
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()} to {$carbonEnd->toFormattedDayDateString()}.");

            return $carbonStart->toPeriod($carbonEnd->endOfDay(), self::INTERVAL, self::INTERVAL_UNITS);
        } elseif ($start) {
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()}.");

            return $carbonStart->toPeriod($carbonStart->copy()->endOfDay(), self::INTERVAL, self::INTERVAL_UNITS);
        } else {
            $this->error('You must specify a start date.');

            return null;
        }
    }

    private function printDay(Collection $stats): void
    {
        $stats = $stats->map(fn ($stat) => [
            $stat->window_start,
            $stat->interval,
            $stat['name'] ?? $stat['endpoint_id'],
            number_format($stat['total_requests']),
            number_format($stat['successful_requests']),
            number_format($stat['failed_requests']),
            number_format($stat['max_requests_per_minute']),
            round($stat['min_duration'] * 1000) . ' ms',
            round($stat['avg_duration'] * 1000) . ' ms',
            round($stat['p50_duration'] * 1000) . ' ms',
            round($stat['p75_duration'] * 1000) . ' ms',
            round($stat['p95_duration'] * 1000) . ' ms',
            round($stat['p99_duration'] * 1000) . ' ms',
            round($stat['max_duration'] * 1000) . ' ms',
        ]);

        table(
            [
                'Window Start',
                'Interval',
                'Endpoint',
                'Total',
                'Successful',
                'Failed',
                'Req/Min',
                'Minimum',
                'Average',
                '50th %',
                '75th %',
                '95th %',
                '99th %',
                'Maximum',
            ],
            $stats,
        );
    }

    private function printStats($stats): void
    {
        table(
            [
                'Date',
                'Total Responses',
                'Successful Responses',
                'Failed Responses',
                'Average Duration',
                'Processing Time',
            ],
            $stats,
        );

        $sums = $stats->reduce(
            fn ($carry, $item) => [
                $carry[0],
                $carry[1] + (int) str_replace(',', '', $item[1]),
                $carry[2] + (int) str_replace(',', '', $item[2]),
                $carry[3] + (int) str_replace(',', '', $item[3]),
                $carry[4] + (int) str_replace(',', '', str_replace(' ms', '', $item[4])),
                $carry[5] + (int) str_replace(',', '', str_replace(' ms', '', $item[5])),
            ],
            ['Totals', 0, 0, 0, 0, 0],
        );

        $sums[1] = number_format($sums[1]);
        $sums[2] = number_format($sums[2]);
        $sums[3] = number_format($sums[3]);
        $sums[4] = number_format($sums[4] / $stats->count()) . ' ms';
        $sums[5] = number_format($sums[5] / $stats->count()) . ' ms';

        table(
            [
                'Date',
                'Total Responses',
                'Successful Responses',
                'Failed Responses',
                'Average Duration',
                'Processing Time',
            ],
            [$sums],
        );
    }
}
