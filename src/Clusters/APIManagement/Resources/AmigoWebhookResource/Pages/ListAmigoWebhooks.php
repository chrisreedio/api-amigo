<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use ChrisReedIO\APIAmigo\Filament\Concerns\EstimatesTableRecordCount;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAmigoWebhooks extends ListRecords
{
    use EstimatesTableRecordCount;

    protected static string $resource = AmigoWebhookResource::class;

    protected Width | string | null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
