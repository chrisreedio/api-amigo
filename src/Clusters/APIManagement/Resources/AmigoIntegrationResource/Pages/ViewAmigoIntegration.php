<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAmigoIntegration extends ViewRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var AmigoIntegration $integration */
        $integration = $this->getRecord();

        return 'Viewing Integration: ' . $integration->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
