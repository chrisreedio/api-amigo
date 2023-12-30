<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoResponse extends EditRecord
{
    protected static string $resource = AmigoResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
