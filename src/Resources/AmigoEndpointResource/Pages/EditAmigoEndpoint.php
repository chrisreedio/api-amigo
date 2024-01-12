<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoEndpoint extends EditRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
