<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoIntegration extends ViewRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
