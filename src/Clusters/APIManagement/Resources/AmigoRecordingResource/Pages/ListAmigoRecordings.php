<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages;

use Filament\Support\Enums\Width;
use Filament\Actions\CreateAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoRecordings extends ListRecords
{
    protected static string $resource = AmigoRecordingResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
