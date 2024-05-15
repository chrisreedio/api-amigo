<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoIntegration extends EditRecord
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
