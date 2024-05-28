<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAmigoConnector extends ViewRecord
{
    protected static string $resource = AmigoConnectorResource::class;

    public function getTitle(): string|Htmlable
    {
           return 'Viewing Connector: ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
