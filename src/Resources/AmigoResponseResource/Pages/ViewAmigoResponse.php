<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
            // Actions\EditAction::make(),
            Actions\Action::make('test')
                ->label('Test')
                ->action(function (AmigoResponse $record) {
                    // dd($record->body);
                    $testString = "Hello\nWorld!";
                    dd($testString);
                    // dd(json_encode($record->body));
                }),
        ];
    }
}
