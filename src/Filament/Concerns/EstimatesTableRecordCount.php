<?php

namespace ChrisReedIO\APIAmigo\Filament\Concerns;

use ChrisReedIO\APIAmigo\Models\AmigoModel;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Paginates an unfiltered table with the estimated row count instead of a COUNT(*) over the whole table.
 * Filtered and searched tables still count exactly, since an estimate can't account for the filter.
 */
trait EstimatesTableRecordCount
{
    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        $model = $query->getModel();
        $perPage = $this->getTableRecordsPerPage();

        if (! $model instanceof AmigoModel || $perPage === 'all' || $query->toBase()->wheres !== []) {
            return parent::paginateTableQuery($query);
        }

        return $query->paginate(
            perPage: $perPage,
            pageName: $this->getTablePaginationPageName(),
            total: $model::estimatedCount(),
        )->onEachSide(0);
    }
}
