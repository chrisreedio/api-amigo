<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\Pages;

use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewAmigoRecording extends ViewRecord
{
    protected static string $resource = AmigoRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('start')
                ->label('Start')
                ->icon('far-play')
                ->color(Color::Green)
                ->hidden(fn (AmigoRecording $recording) => $recording->started_at || $recording->ended_at)
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to start this recording?')
                ->action(fn (AmigoRecording $recording) => $recording->start()),
            Actions\Action::make('stop')
                ->label('Stop')
                ->icon('far-stop')
                ->color(Color::Red)
                ->hidden(fn (AmigoRecording $recording) => ! $recording->started_at || $recording->ended_at)
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to stop this recording?')
                ->action(fn (AmigoRecording $recording) => $recording->stop()),
            Actions\EditAction::make(),
        ];
    }
}
