<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use function dd;
use function dump;

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
