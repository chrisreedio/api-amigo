<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoEndpoint extends ViewRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
