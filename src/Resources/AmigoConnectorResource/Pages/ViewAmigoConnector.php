<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource;
use Filament\Actions;
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
