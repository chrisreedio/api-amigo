<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoEndpoint extends EditRecord
{
    protected static string $resource = AmigoEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
