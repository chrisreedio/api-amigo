<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAmigoWebhook extends CreateRecord
{
    protected static string $resource = AmigoWebhookResource::class;
}
