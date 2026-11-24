<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource;
use ChrisReedIO\APIAmigo\Filament\Concerns\EstimatesTableRecordCount;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAmigoRequests extends ListRecords
{
    use EstimatesTableRecordCount;

    protected static string $resource = AmigoRequestResource::class;

    protected Width | string | null $maxContentWidth = 'full';
}
