<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAmigoIntegration extends ViewRecord
{
    protected static string $resource = AmigoIntegrationResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Viewing Integration: '.$this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
