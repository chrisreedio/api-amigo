<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAmigoConnector extends ViewRecord
{
    protected static string $resource = AmigoConnectorResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var AmigoConnector $connector */
        $connector = $this->getRecord();

        return 'Viewing Connector: ' . $connector->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
