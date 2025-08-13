<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAmigoWebhook extends ViewRecord
{
    protected static string $resource = AmigoWebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            Action::make('download_large_payload')
                ->icon('far-download')
                ->label('Download Payload')
                ->button()
                ->hidden(fn (AmigoWebhook $record) => $record->payload === null || $record->payload === [] || $record->payload === '')
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
            Action::make('reprocess')
                ->label('Reprocess')
                ->icon('far-arrows-rotate')
                ->requiresConfirmation()
                ->action(function (AmigoWebhook $record) {
                    $record->reprocess();
                }),
        ];
    }
}
