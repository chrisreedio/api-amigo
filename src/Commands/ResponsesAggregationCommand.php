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
        $totalStartTime = microtime(true);

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

        $totalDuration = number_format(microtime(true) - $totalStartTime, 2);
        info("Total process completed in {$totalDuration} seconds.");
    }

    private function processBulk(CarbonPeriod $period): void
    {
        $bulkStartTime = microtime(true);

        // Process in 30-day chunks to manage memory
        $chunkSize = 30;
        $days = $period->toArray();
        $chunks = array_chunk($days, $chunkSize);

        $totalProcessed = 0;
        $totalInserted = 0;

        $progress = progress(
            label: 'Processing chunks...',
            steps: $chunks,
            callback: function ($chunk, $progress) use (&$totalProcessed, &$totalInserted, $chunkSize) {
                $chunkStart = $chunk[0];
                $chunkEnd = end($chunk);

                $progress
                    ->label("Processing chunk from {$chunkStart->toFormattedDateString()} to {$chunkEnd->toFormattedDateString()}")
                    ->hint("Processing {$chunkSize} days of data...");

                // Create hourly intervals for the chunk
                $intervals = collect();
                $current = $chunkStart->copy();
                while ($current <= $chunkEnd) {
                    $intervals->push($current->copy());
                    $current->addMinutes(self::INTERVAL);
                }

                $start = microtime(true);
                $stats = collect();

                // Process intervals in smaller batches to reduce memory usage
                foreach ($intervals->chunk(24) as $intervalBatch) {
                    foreach ($intervalBatch as $date) {
                        $end = $date->copy()->addMinutes(self::INTERVAL);
                        $window = $this->processWindow($date, $end);
                        $stats = $stats->concat($window);
                        $totalProcessed++;
                    }
                }

                $processingTime = number_format(microtime(true) - $start, 2);
                $progress->hint("Processed {$totalProcessed} intervals in {$processingTime} seconds.");

                // Insert the chunk's data in batches
                $startTime = microtime(true);
                $chunkTotal = 0;

                // Use chunking for large datasets
                foreach ($stats->chunk(1000) as $batch) {
                    $chunkTotal += AmigoEndpointAggregate::insertOrIgnore($batch->toArray());
                }

                $totalInserted += $chunkTotal;

                $processingTime = number_format(microtime(true) - $startTime, 2);
                if ($chunkTotal === 0) {
                    $progress->hint('No new aggregations inserted for this chunk.');
                } else {
                    $progress->hint("Inserted {$chunkTotal} aggregations in {$processingTime} seconds.");
                }

                return [
                    'chunk_start' => $chunkStart->toFormattedDateString(),
                    'chunk_end' => $chunkEnd->toFormattedDateString(),
                    'intervals_processed' => $totalProcessed,
                    'aggregations_inserted' => $chunkTotal,
                ];
            },
            hint: 'This may take some time.',
        );

        $totalDuration = number_format(microtime(true) - $bulkStartTime, 2);
        outro("Processed {$totalProcessed} intervals, inserted {$totalInserted} aggregations in {$totalDuration} seconds.");
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
                'ROUND(AVG(duration)::numeric, 6) as avg_duration',
                'ROUND(MIN(duration)::numeric, 6) as min_duration',
                'ROUND(MAX(duration)::numeric, 6) as max_duration',
                'ROUND(PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY duration)::numeric, 6) as p50_duration',
                'ROUND(PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY duration)::numeric, 6) as p75_duration',
                'ROUND(PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration)::numeric, 6) as p95_duration',
                'ROUND(PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY duration)::numeric, 6) as p99_duration',
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
