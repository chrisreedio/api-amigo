<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoWebhook extends EditRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
