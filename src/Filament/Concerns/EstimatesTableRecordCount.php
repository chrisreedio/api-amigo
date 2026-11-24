<?php

namespace ChrisReedIO\APIAmigo\Filament\Concerns;

use ChrisReedIO\APIAmigo\Models\AmigoModel;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Paginates with PostgreSQL's row estimates instead of a COUNT(*) over every matching row: the table's
 * statistics when unfiltered, and the planner's estimate for the query when filtered or searched.
 * Small results are counted exactly.
 */
trait EstimatesTableRecordCount
{
    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        $model = $query->getModel();
        $perPage = $this->getTableRecordsPerPage();

        if (! $model instanceof AmigoModel || $perPage === 'all') {
            return parent::paginateTableQuery($query);
        }

        return $query->paginate(
            perPage: $perPage,
            pageName: $this->getTablePaginationPageName(),
            total: $query->toBase()->wheres === [] ? $model::estimatedCount() : $model::estimatedCountFor($query),
        )->onEachSide(0);
    }
}
