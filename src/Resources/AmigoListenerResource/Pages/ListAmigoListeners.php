<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Resources\AmigoListenerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmigoListeners extends ListRecords
{
    protected static string $resource = AmigoListenerResource::class;

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
            // Actions\CreateAction::make(),
        ];
    }
}
