<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Blueprint::macro('addColumnRaw', function ($rawType, $name) {
            return $this->addColumn('raw', $name, ['raw_type' => $rawType]);
        });

        $tablePrefix = config('api-amigo.table_prefix');

        Schema::dropIfExists($tablePrefix . 'endpoint_aggregates');

        Schema::create($tablePrefix . 'endpoint_aggregates', function (Blueprint $table) use ($tablePrefix) {
            $table->id();

            $table->foreignId('integration_id')
                ->nullable()
                ->index()
                ->constrained($tablePrefix . 'integrations')
                ->cascadeOnDelete();

            $table->foreignId('endpoint_id')
                ->index()
                ->constrained($tablePrefix . 'endpoints')
                ->cascadeOnDelete();

            $table->timestamp('window_start')
                ->index();
            $table->integer('interval')
                ->comment('The duration of the window in seconds.');

            $table->bigInteger('total_requests')->default(0);
            $table->bigInteger('successful_requests')->default(0);
            $table->bigInteger('failed_requests')->default(0);
            $table->bigInteger('max_requests_per_minute')->default(0);

            $table->float('min_duration')->nullable();
            $table->float('max_duration')->nullable();
            $table->float('avg_duration')->nullable();
            $table->float('p50_duration')->nullable();
            $table->float('p75_duration')->nullable();
            $table->float('p95_duration')->nullable();
            $table->float('p99_duration')->nullable();

            // Check for the existence of the tdigest extension, if so create a tdigest column
            if (DB::query()->from('pg_extension')->where('extname', 'tdigest')->count() > 0) {
                $table->addColumn('tdigest', 'duration_histogram')->nullable();
                // $table->
            }

            $table->timestamps();

            $table->unique(['integration_id', 'endpoint_id', 'window_start', 'interval']);
        });
    }

    public function down(): void
    {
        $tablePrefix = config('api-amigo.table_prefix');
        Schema::dropIfExists($tablePrefix . 'endpoint_aggregates');
    }
};
