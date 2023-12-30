<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoConnector extends EditRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
