<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoResponse extends ViewRecord
{
    protected static string $resource = AmigoResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
