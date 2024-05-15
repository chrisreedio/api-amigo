<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoConnector extends ViewRecord
{
    protected static string $resource = AmigoConnectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
