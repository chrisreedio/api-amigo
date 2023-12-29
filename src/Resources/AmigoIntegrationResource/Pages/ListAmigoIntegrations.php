<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoIntegrations extends ListRecords
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
