<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

use function config;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('endpoint.name')
                    ->label('Endpoint')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('endpoint.connector.name')
                    ->label('Connector'),
                Tables\Columns\TextColumn::make('endpoint.name')
                    ->label('Endpoint Name'),
                Tables\Columns\TextColumn::make('endpoint.styled_path')
                    ->label('Endpoint Path')
                    ->html()
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('response.duration')
                    ->label('Duration')
                    ->badge()
                    ->placeholder('No Response')
                    ->color(function ($record) {
                        $duration = $record->response !== null ? $record->response->duration : 99999;
                        if ($duration >= config('api-amigo.thresholds.duration.error')) {
                            return Color::Red;
                        } elseif ($duration >= config('api-amigo.thresholds.duration.warning')) {
                            return Color::Yellow;
                        } else {
                            return Color::Green;
                        }
                    })
                    ->getStateUsing(fn (AmigoRequest $record) => $record->response !== null ? ($record->response->duration * 1000) . 'ms' : null)
                    // ->suffix('s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('connector_id')
                    ->relationship('endpoint.connector', 'name'),
                Tables\Filters\SelectFilter::make('endpoint_id')
                    ->relationship('endpoint', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->url(fn (AmigoRequest $record) => $record->response ? AmigoResponseResource::getUrl('view', ['record' => $record->response]) : null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
