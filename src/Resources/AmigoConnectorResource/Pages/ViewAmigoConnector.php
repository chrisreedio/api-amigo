<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoConnector extends ViewRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
