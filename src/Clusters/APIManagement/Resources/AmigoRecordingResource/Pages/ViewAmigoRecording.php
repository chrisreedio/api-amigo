<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewAmigoRecording extends ViewRecord
{
    protected static string $resource = AmigoRecordingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label('Start')
                ->icon('far-play')
                ->color(Color::Green)
                ->hidden(fn (AmigoRecording $recording) => $recording->started_at || $recording->ended_at)
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to start this recording?')
                ->action(fn (AmigoRecording $recording) => $recording->start()),
            Action::make('stop')
                ->label('Stop')
                ->icon('far-stop')
                ->color(Color::Red)
                ->hidden(fn (AmigoRecording $recording) => ! $recording->started_at || $recording->ended_at)
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to stop this recording?')
                ->action(fn (AmigoRecording $recording) => $recording->stop()),
            EditAction::make(),
        ];
    }
}
