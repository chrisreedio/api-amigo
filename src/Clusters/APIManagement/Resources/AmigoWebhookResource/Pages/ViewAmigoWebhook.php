<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use ChrisReedIO\APIAmigo\Jobs\ProcessWebhookJob;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoWebhook extends ViewRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            Actions\Action::make('download_large_payload')
                ->icon('far-download')
                ->label('Download Payload')
                ->button()
                ->hidden(fn (AmigoWebhook $record) => $record->body === null || $record->body === [])
                ->action(function (AmigoWebhook $record) {
                    // Stream the $record->body field as a JSON file to the browser for download
                    $filename = 'amigo-webhook-' . $record->id . '.json';
                    $headers = [
                        'Content-Type' => 'application/json',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    $body = $record->encoded_body;

                    return response()->streamDownload(function () use ($body) {
                        echo $body;
                    }, $filename, $headers);
                }),
            Actions\Action::make('reprocess')
                ->label('Reprocess')
                ->icon('far-arrows-rotate')
                ->requiresConfirmation()
                ->action(function (AmigoWebhook $record) {
                    /** @var ProcessWebhookJob $handler */
                    $handler = $record->listener->handler;
                    // Create a new instance of the handler and dispatch it
                    $job = $handler::dispatchSync($record);
                }),
        ];
    }
}
