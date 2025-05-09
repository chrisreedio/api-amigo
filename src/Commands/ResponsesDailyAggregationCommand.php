<?php

namespace ChrisReedIO\APIAmigo\Commands;

use Illuminate\Support\Carbon;

class ResponsesDailyAggregationCommand extends ResponsesAggregationCommand
{
    protected $signature = 'responses:aggregate-daily {startDate?} {endDate?} {--regenerate} {--bulk} {--all}';

    protected $description = 'Aggregate responses for the previous day.';

    public function handle(): void
    {
        // Get the configured timezone
        $timezone = config('api-amigo.aggregation.timezone', 'UTC');

        // Set the start date to yesterday in the configured timezone
        $yesterday = Carbon::now($timezone)->subDay();
        $this->input->setArgument('startDate', $yesterday->startOfDay()->toDateString());

        // Set the end date to yesterday's end of day in the configured timezone
        $this->input->setArgument('endDate', $yesterday->endOfDay()->toDateTimeString());

        parent::handle();
    }
}
