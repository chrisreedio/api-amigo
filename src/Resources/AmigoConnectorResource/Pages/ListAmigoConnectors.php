<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoConnectors extends ListRecords
{
    protected static string $resource = AmigoConnectorResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
