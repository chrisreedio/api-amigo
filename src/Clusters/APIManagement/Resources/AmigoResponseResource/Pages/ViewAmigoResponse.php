<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use function response;

/**
 * @property AmigoResponse $record
 */
class ViewAmigoResponse extends ViewRecord
{
    protected static string $resource = AmigoResponseResource::class;

    public function getHeading(): string
    {
        return implode(' ', [
            'View',
            $this->record->endpoint->name,
            'Response',
        ]);
    }

    // public function getSubheading(): ?string
    // {
    //     return $this->record->endpoint->name;
    // }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_large_response')
                ->icon('far-download')
                ->label('Download Response')
                ->button()
                ->hidden(fn (AmigoResponse $record) => $record->body === null || $record->body === [])
                ->action(function (AmigoResponse $record) {
                    // Stream the $record->body field as a JSON file to the browser for download
                    $filename = 'amigo-response-' . $record->id . '.json';
                    $headers = [
                        'Content-Type' => 'application/json',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];
                    $body = $record->encoded_body;

                    return response()->streamDownload(function () use ($body) {
                        echo $body;
                    }, $filename, $headers);
                }),

            // Actions\EditAction::make(),
            // Actions\Action::make('test')
            //     ->label('Test')
            //     ->action(function (AmigoResponse $record) {
            //         // dd($record->body);
            //         $testString = "Hello\nWorld!";
            //         dd($testString);
            //         // dd(json_encode($record->body));
            //     }),
        ];
    }
}
