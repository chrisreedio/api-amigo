<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Filament\Concerns\EstimatesTableRecordCount;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAmigoResponses extends ListRecords
{
    use EstimatesTableRecordCount;

    protected static string $resource = AmigoResponseResource::class;

    protected Width | string | null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
