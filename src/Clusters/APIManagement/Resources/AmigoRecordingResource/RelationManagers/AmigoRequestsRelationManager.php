<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

use function config;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('endpoint.name')
                    ->label('Endpoint')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->recordUrl(fn (AmigoRequest $record) => $record->response()->exists() ? AmigoResponseResource::getUrl('view', ['record' => $record->response]) : null)
            ->columns([
                TextColumn::make('endpoint.connector.name')
                    ->label('Connector'),
                TextColumn::make('endpoint.name')
                    ->placeholder('No Display Name')
                    ->label('Endpoint Name'),
                TextColumn::make('endpoint.styled_path')
                    ->label('Endpoint Path')
                    ->html()
                    // ->copyable()
                    ->searchable(),
                TextColumn::make('response.status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response?->status_code?->value . ' ' . $record->response?->status_code?->getLabel())
                    ->alignCenter()
                    ->badge()
                    ->sortable(),
                TextColumn::make('response.duration')
                    ->label('Duration')
                    ->badge()
                    ->placeholder('No Response')
                    ->color(function (AmigoRequest $record) {
                        $duration = $record->response()->exists() ? $record->response->duration : 99999;
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

                TextColumn::make('response.cached')
                    ->label('Cached')
                    ->icon('far-database')
                    ->color(fn (AmigoRequest $record) => $record->response?->cached ? Color::Green : Color::Red)
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->placeholder('N/A')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('connector_id')
                    ->relationship('endpoint.connector', 'name')
                    ->label('Connector'),
                SelectFilter::make('endpoint_id')
                    ->relationship('endpoint', 'path')
                    ->label('Endpoint')
                    ->searchable()
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make()
                //     ->url(fn (AmigoRequest $record) => $record->response()->exists() ? AmigoResponseResource::getUrl('view', ['record' => $record->response]) : null),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
