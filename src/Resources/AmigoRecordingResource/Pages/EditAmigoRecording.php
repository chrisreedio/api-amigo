<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource;
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
