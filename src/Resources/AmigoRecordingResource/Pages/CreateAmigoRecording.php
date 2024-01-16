<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAmigoRecording extends CreateRecord
{
    protected static string $resource = AmigoRecordingResource::class;
}
