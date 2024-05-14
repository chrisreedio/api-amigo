<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoWebhook extends EditRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
