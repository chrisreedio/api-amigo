<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoListeners extends ListRecords
{
    protected static string $resource = AmigoListenerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
