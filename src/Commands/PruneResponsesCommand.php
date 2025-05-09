<?php

namespace ChrisReedIO\APIAmigo\Commands;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneResponsesCommand extends Command
{
    protected $signature = 'responses:prune {--force}';

    protected $description = 'Prune old API responses based on the configured lifetime.';

    public function handle(): void
    {
        $lifetimeDays = config('api-amigo.aggregation.prune_lifetime_days', 90);
        $cutoffDate = Carbon::now()->subDays($lifetimeDays);

        $toBeDeleted = number_format(AmigoResponse::query()
            ->where('created_at', '<', $cutoffDate)
            ->count());

        if (! $this->option('force')) {
            $this->info("This will delete {$toBeDeleted} responses older than {$cutoffDate->toDateTimeString()} ({$lifetimeDays} days ago).");
            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');

                return;
            }
        }

        $deletedCount = AmigoResponse::query()
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $deletedCount = number_format($deletedCount);

        $this->info("Successfully deleted {$deletedCount} old responses.");
    }
}
