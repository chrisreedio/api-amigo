<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AmigoModel extends Model
{
    /**
     * Below this many rows, counting exactly is cheap and the planner's estimate may be stale.
     */
    public const int EXACT_COUNT_THRESHOLD = 100_000;

    /**
     * Below this many matching rows, a filtered query is counted exactly. Counting a filtered query
     * can't use the table's statistics, so the cutoff is lower than for the whole table.
     */
    public const int EXACT_FILTERED_COUNT_THRESHOLD = 10_000;

    /**
     * Counts the table's rows, using PostgreSQL's planner estimate for large tables instead of a full COUNT(*).
     */
    public static function estimatedCount(): int
    {
        $model = static::query()->getModel();
        $connection = $model->getConnection();

        if ($connection->getDriverName() === 'pgsql') {
            $estimate = (int) $connection->scalar(
                'select reltuples::bigint from pg_class where oid = to_regclass(?)',
                [$connection->getTablePrefix() . $model->getTable()],
            );

            if ($estimate >= self::EXACT_COUNT_THRESHOLD) {
                return $estimate;
            }
        }

        return static::query()->count();
    }

    /**
     * Counts a query's rows, using PostgreSQL's planner estimate when it expects many, so filtered
     * tables don't scan every matching row to show a page count.
     *
     * @param  Builder<static>  $query
     */
    public static function estimatedCountFor(Builder $query): int
    {
        $base = $query->toBase()->cloneWithout(['orders', 'limit', 'offset']);
        $connection = $query->getModel()->getConnection();

        if ($connection->getDriverName() === 'pgsql') {
            $plan = json_decode($connection->selectOne('explain (format json) ' . $base->toSql(), $base->getBindings())->{'QUERY PLAN'}, true);
            $estimate = (int) $plan[0]['Plan']['Plan Rows'];

            if ($estimate >= self::EXACT_FILTERED_COUNT_THRESHOLD) {
                return $estimate;
            }
        }

        return $query->toBase()->getCountForPagination();
    }
}
