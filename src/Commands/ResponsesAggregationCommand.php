<?php

namespace ChrisReedIO\APIAmigo\Commands;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Flowframe\Trend\Trend;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use function collect;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;
use function microtime;
use function number_format;

class ResponsesAggregationCommand extends Command
{
    protected $signature = 'responses:aggregate {startDate?} {endDate?} {--regenerate}';

    protected $description = 'Aggregate responses for reporting purposes.';

    public function handle(): void
    {
        // Get the date range...
        $startDate = $this->argument('startDate');
        $endDate = $this->argument('endDate');
        $period = null;

        $carbonStart = Carbon::parse($startDate);
        $carbonEnd = ($endDate === 'today') ? Carbon::now() : Carbon::parse($endDate);
        if ($carbonEnd > Carbon::now()) {
            warning('End date is in the future. Using today as the end date.');
            $carbonEnd = Carbon::now();
        }
        if ($startDate && $endDate) {
            // $carbonStart = Carbon::parse($startDate);
            // $carbonEnd = Carbon::parse($endDate);
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()} to {$carbonEnd->toFormattedDayDateString()}.");
            $period = $carbonStart->toPeriod($carbonEnd->endOfDay());
        } elseif ($startDate) {
            intro("Aggregating responses from {$carbonStart->toFormattedDayDateString()}.");
            $period = $carbonStart->toPeriod($carbonStart->endOfDay());
        } else {
            // $this->info('Aggregating all responses.');
            $this->error('You must specify a start date.');

            return;
        }

        // if ($regen = $this->option('regenerate')) {
        //     warning('Forcing regeneration of aggregations even if they already exist.');
        // }

        // Loop through each day and gather the Amigo Responses for that day
        // and aggregate them into the AmigoEndpointAggregate model.
        // $trend = Trend::query(AmigoResponse::query())
        //     ->between($carbonStart, $carbonEnd)
        //     ->perHour()
        //     ->count();
        // dd($trend);
        $stats = collect($period)
            ->map(fn (Carbon $date) => $this->processDay($date));
        // ->each(fn (Carbon $date) => spin(fn () => $this->processDay($date), "Aggregating responses for {$date->toFormattedDayDateString()}..."));

        // $this->printStats($stats);
    }

    private function processDay($date): array
    {
        return spin(
            function () use ($date) {
                $startTime = (float) microtime(true);
                // \DB::enableQueryLog();
                $responses = AmigoResponse::query()
                    // ->select('endpoint_id')
                    ->selectRaw(implode(', ', [
                        'endpoint_id',
                        'amigo_endpoints.name',
                        'COUNT(amigo_responses.id) as total_responses',
                        'SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as successful_responses',
                        'SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed_responses',
                        'AVG(duration) as average_duration',
                    ]))
                    ->whereDate('amigo_responses.created_at', $date->toDateString())
                    ->join('amigo_endpoints', 'amigo_endpoints.id', '=', 'amigo_responses.endpoint_id')
                    ->groupBy(['endpoint_id', 'amigo_endpoints.name'])
                    ->get();
                // ->toSql();
                // dd(\DB::getQueryLog());
                // dump($responses->first()->toArray());
                $trimmedData = $responses
                    ->map(fn ($response) => $response->only([
                        'name',
                        'total_responses',
                        'successful_responses',
                        'failed_responses',
                        'average_duration',
                    ]));
                // dd($trimmedData);
                $processingTime = number_format((float) microtime(true) - $startTime, 2);
                $totalResponse = number_format($responses->sum('total_responses'));
                info("Found {$totalResponse} responses for {$date->toFormattedDayDateString()} in {$processingTime} seconds.");

                $this->printDay($trimmedData);
                // dd($trimmedData->toArray());

                return [];
                // return [
                //     $date->toFormattedDayDateString(),
                //     number_format($responses->total_responses),
                //     number_format($responses->successful_responses),
                //     number_format($responses->failed_responses),
                //     number_format(round($responses->average_duration, 2) * 1000) . ' ms',
                //     number_format(round($processingTime * 1000)) . ' ms',
                // ];
            },
            "Aggregating responses for {$date->toFormattedDayDateString()}..."
        );
    }

    private function printDay(Collection $stats): void
    {
        // Preprocess
        // dd($stats);
        $stats = $stats->map(fn ($stat) => [
            $stat['name'],
            number_format($stat['total_responses']),
            number_format($stat['successful_responses']),
            number_format($stat['failed_responses']),
            number_format($stat['average_duration'] * 1000) . ' ms',
        ]);

        table(
            [
                'Endpoint',
                'Total Responses',
                'Successful Responses',
                'Failed Responses',
                'Average Duration',
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
}
