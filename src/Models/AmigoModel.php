<?php

namespace ChrisReedIO\APIAmigo\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AmigoModel extends Model
{
    /**
     * Below this many rows, counting exactly is cheap and the planner's estimate may be stale.
     */
    public const int EXACT_COUNT_THRESHOLD = 100_000;

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
}
