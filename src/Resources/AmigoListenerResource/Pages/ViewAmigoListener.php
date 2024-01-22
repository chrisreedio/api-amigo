<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Models\AmigoListener;
use ChrisReedIO\APIAmigo\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewAmigoListener
 * @package ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\Pages
 * @property AmigoListener $record
 */
class ViewAmigoListener extends ViewRecord
{
    protected static string $resource = AmigoListenerResource::class;

    public function getSubheading(): ?string
    {
        return $this->record->url;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
