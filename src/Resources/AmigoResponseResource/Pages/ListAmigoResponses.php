<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoResponses extends ListRecords
{
    protected static string $resource = AmigoResponseResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
