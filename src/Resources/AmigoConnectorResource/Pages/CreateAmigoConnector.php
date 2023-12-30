<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAmigoConnector extends CreateRecord
{
    protected static string $resource = AmigoIntegrationResource::class;
}
