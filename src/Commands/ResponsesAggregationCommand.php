<?php

namespace ChrisReedIO\APIAmigo\Commands;

use Carbon\CarbonPeriod;
use ChrisReedIO\APIAmigo\Enums\RateGranularity;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Flowframe\Trend\Trend;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function array_filter;
use function collect;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;
use function microtime;
use function number_format;

class ResponsesAggregationCommand extends Command
{
    protected $signature = 'responses:aggregate {startDate?} {endDate?} {--regenerate}';

    protected $description = 'Aggregate responses for reporting purposes.';

    const INTERVAL = 60;

    const INTERVAL_UNITS = 'minute';

    public function handle(): void
    {
        $period = $this->calculatePeriod();

        $start = microtime(true);
        // ->map(fn (Carbon $date) => $this->processDay($date));
        // ->map(fn (Carbon $date) => $this->processWindow($date, $date->endOfDay()));

        // $stats = collect($period)
        //     ->mapWithKeys(function (Carbon $date) {
        //         $end = $date->copy()->addMinutes(self::INTERVAL);
        //
        //         return [$date->toDateTimeString() => $this->processWindow($date, $end)];
        //     });

        $stats = progress(
            label: 'Calculating intervals...',
            steps: $period,
            callback: function ($date, $progress) {
                $end = $date->copy()->addMinutes(self::INTERVAL);
                $progress
                    // ->label("Calculating intervals for {$date->toFormattedDateString()} to {$end->toFormattedDateString()}")
                    ->label("Calculating intervals for {$date->toFormattedDateString()}")
                    ->hint("Processing window from {$date->format('H:i')} to {$end->format('H:i')}...");

                $window = $this->processWindow($date, $end);
                // dd($window->toArray());

                return $window->toArray();
            },
            hint: 'This may take some time.',
        );

        $stats = collect($stats);

        $processingTime = number_format(microtime(true) - $start, 2);
        $this->info("Calculated {$stats->count()} intervals in {$processingTime} seconds.");

        // dump($stats->take(5)->toArray());

        // Loop through each day and gather the Amigo Responses for that day
        // and aggregate them into the AmigoEndpointAggregate model.
        // $trend = Trend::query(AmigoResponse::query())
        //     ->between($carbonStart, $carbonEnd)
        //     ->perHour()
        //     ->count();
        // dd($trend);

        // $stats = $stats->flatten();
        // dd($stats->each(fn ($stat) => dump($stat)));
        // Insert all of these stats into the aggregates table
        $total = 0;
        $startTime = microtime(true);
        $stats->each(function ($day) use (&$total) {
            $total += AmigoEndpointAggregate::insertOrIgnore($day);
        });
        // dd('done');
        // AmigoEndpointAggregate::insertOrIgnore($stats->toArray());
        $processingTime = number_format(microtime(true) - $startTime, 2);
        if ($total === 0) {
            warning('No new endpoint response aggregations were inserted.');
        } else {
            outro("Inserted {$total} endpoint response " . Str::plural('aggregation', $total) . " in {$processingTime} seconds.");
        }

        // $stats->each(fn ($stat) => $this->info($stat->window_start . ' - ' . $stat->name . ' - ' . $stat->total_requests));
        // dd($stats->toArray());
        // $stats->each(fn ($stat) => $this->printDay($stat));
        // ->each(fn (Carbon $date) => spin(fn () => $this->processDay($date), "Aggregating responses for {$date->toFormattedDayDateString()}..."));

        // $this->printStats($stats);
    }

    private function processWindow(Carbon $start, Carbon $end)
    {
        // $startTime = (float) microtime(true);
        // $end = $start->copy()->addMinutes(self::INTERVAL);
        // $this->info('Processing window from ' . $start->toDateTimeString() . ' to ' . $end->toDateTimeString() . '.');
        $maxRequestsPerMinute = $this->getRequestsPerMinute($start);
        $responses = AmigoResponse::query()
            ->selectRaw(implode(', ', array_filter([
                'endpoint_id',
                'amigo_connectors.integration_id',
                // 'amigo_endpoints.name',
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
            // ->whereBetween('amigo_responses.created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->whereBetween('amigo_responses.created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->join('amigo_endpoints', 'amigo_endpoints.id', '=', 'amigo_responses.endpoint_id')
            ->join('amigo_connectors', 'amigo_connectors.id', '=', 'amigo_endpoints.connector_id')
            // ->groupBy(['endpoint_id', 'amigo_endpoints.name'])
            ->groupBy(['endpoint_id', 'amigo_connectors.integration_id'])
            ->get();

        // ->toRawSql();
        // dd($responses->toArray());
        // Merge the max requests per minute into the responses
        return $responses->map(function ($response) use ($maxRequestsPerMinute, $start) {
            $response->max_requests_per_minute = $maxRequestsPerMinute[$response->endpoint_id] ?? 0;
            $response->window_start = $start->toDateTimeString();
            $response->interval = self::INTERVAL * 60;
            $response->created_at = Carbon::now();
            // $response->max_requests_per_second = $maxRequestsPerSecond[$response->endpoint_id] ?? 0;

            return $response;
        });
    }

    private function getRequestsPerMinute(Carbon $start): Collection
    {
        $requestsPerMinute = AmigoResponse::select([
            'endpoint_id',
            // DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as minute"), // MySQL
            DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI') as minute"), // Postgres
            DB::raw('COUNT(*) as requests_per_minute'),
        ])
            // ->whereDate('created_at', $date->toDateString())
            ->whereBetween('created_at', [
                $start->toDateTimeString(),
                $start->copy()->addMinutes(self::INTERVAL)->toDateTimeString(),
            ])
            ->groupBy('endpoint_id', 'minute')
            ->get();

        // Find the maximum requests per minute for each endpoint
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
        // $this->info('Carbon Start: ' . $carbonStart->toDateTimeString());
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
            // $carbonStart = Carbon::parse($startDate);
            // $carbonEnd = Carbon::parse($endDate);
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()} to {$carbonEnd->toFormattedDayDateString()}.");

            return $carbonStart->toPeriod($carbonEnd->endOfDay(), self::INTERVAL, self::INTERVAL_UNITS);
        } elseif ($start) {
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()}.");

            // $period = $carbonStart->toPeriod($carbonStart->endOfDay());
            // $period = $carbonStart->toPeriod($carbonStart);
            return $carbonStart->toPeriod($carbonStart->copy()->endOfDay(), self::INTERVAL, self::INTERVAL_UNITS);
            // TODO - Go back to the line above this
            // $period = $carbonStart->toPeriod($carbonStart->copy()->addHour()->subSecond(), $interval, $intervalUnits);

            // $fakeEnd = $carbonStart->copy()->addHours(14)->subSecond();

            // return $carbonStart
            //     ->addHours(12)
            //     ->toPeriod($fakeEnd, self::INTERVAL, self::INTERVAL_UNITS);
        } else {
            // $this->info('Aggregating all responses.');
            $this->error('You must specify a start date.');

            return null;
        }
    }

    private function printDay(Collection $stats): void
    {
        // Preprocess
        // dd($stats);
        $stats = $stats->map(fn ($stat) => [
            $stat->window_start,
            $stat->interval,
            $stat['name'] ?? $stat['endpoint_id'],
            number_format($stat['total_requests']),
            number_format($stat['successful_requests']),
            number_format($stat['failed_requests']),
            number_format($stat['max_requests_per_minute']),
            // number_format($stat['max_requests_per_second']),
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
                // 'Max Req/Sec',
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

        // Display another table of sums, Making sure to parse the string numbers as integers.
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

        // Format the values
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

    // private function getRequestRates(Carbon $startDate, RateGranularity $granularity = RateGranularity::MINUTE): Collection
    // {
    //     $rates = AmigoResponse::select([
    //         'endpoint_id',
    //         // DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as "  . $granularity->value), // MySQL
    //         match ($granularity) {
    //             RateGranularity::MINUTE => DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI') as minute"), // Postgres
    //             RateGranularity::SECOND => DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI:SS') as second"), // Postgres
    //         },
    //         // DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI:SS') as " . $granularity->value), // Postgres
    //         DB::raw('COUNT(*) as requests_per_' . $granularity->value),
    //     ])
    //         ->whereDate('created_at', $startDate->toDateString())
    //         ->groupBy('endpoint_id', $granularity->value)
    //         ->get();
    //
    //     // dd($rates->take(10)->toArray());
    //
    //     // Find the maximum requests per minute for each endpoint
    //     return $rates->groupBy('endpoint_id')
    //         ->map(function ($items) use ($granularity) {
    //             return $items->max('requests_per_' . $granularity->value);
    //         });
    // }
}
