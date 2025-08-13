<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages;

use Filament\Support\Enums\Width;
use Filament\Actions\CreateAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoListeners extends ListRecords
{
    protected static string $resource = AmigoListenerResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('sync')
            //     ->label('Sync')
            //     ->icon('far-arrows-rotate')
            //     ->requiresConfirmation()
            //     ->modalHeading('Sync Webhook Listeners')
            //     ->modalDescription('This will ensure that all configured listeners in the code are tracked in the database.')
            //     ->color('primary')
            //     ->action(function () {
            //
            //     }),
            CreateAction::make(),
        ];
    }
}
