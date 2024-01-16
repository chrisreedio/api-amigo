<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoRecordings extends ListRecords
{
    protected static string $resource = AmigoRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false),
        ];
    }
}
