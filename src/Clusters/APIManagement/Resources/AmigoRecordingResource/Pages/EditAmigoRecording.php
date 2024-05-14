<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmigoRecording extends EditRecord
{
    protected static string $resource = AmigoRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
