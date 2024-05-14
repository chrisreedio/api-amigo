<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use Filament\Resources\Pages\ListRecords;

class ListAmigoResponses extends ListRecords
{
    protected static string $resource = AmigoResponseResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
