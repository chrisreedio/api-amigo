<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoListener extends EditRecord
{
    protected static string $resource = AmigoListenerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
