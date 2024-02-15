<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoWebhook extends ViewRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            Actions\Action::make('reprocess')
                ->label('Reprocess')
                ->icon('far-arrows-rotate')
                ->action(function (AmigoWebhook $record) {
                    /** @var ProcessWebhookJob $handler */
                    $handler = $record->listener->handler;
                    // Create a new instance of the handler and dispatch it
                    $job = $handler::dispatchSync($record);
                }),
        ];
    }
}
