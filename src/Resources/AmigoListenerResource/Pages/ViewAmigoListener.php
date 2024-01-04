<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoListener extends ViewRecord
{
    protected static string $resource = AmigoListenerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
