<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoRecording extends ViewRecord
{
    protected static string $resource = AmigoRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
