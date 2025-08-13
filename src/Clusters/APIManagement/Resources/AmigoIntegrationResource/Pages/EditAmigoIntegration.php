<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoIntegration extends EditRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
