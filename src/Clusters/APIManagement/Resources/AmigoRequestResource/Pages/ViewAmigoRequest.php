<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

use function response;

/**
 * @property AmigoRequest $record
 */
class ViewAmigoRequest extends ViewRecord
{
    protected static string $resource = AmigoRequestResource::class;

    protected Width | string | null $maxContentWidth = 'full';

    public function getHeading(): string
    {
        return $this->record->endpoint->display_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_response')
                ->label('Download Response')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (AmigoRequest $record) => $record->response?->getRawOriginal('body') !== null)
                ->action(function (AmigoRequest $record) {
                    $body = json_decode($record->response->getRawOriginal('body'), true);

                    return response()->streamDownload(
                        fn () => print ($body),
                        "amigo-response-{$record->response->id}.json",
                        ['Content-Type' => 'application/json'],
                    );
                }),
        ];
    }
}
