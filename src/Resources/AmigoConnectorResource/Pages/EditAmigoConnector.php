<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoConnector extends EditRecord
{
    protected static string $resource = AmigoConnectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
