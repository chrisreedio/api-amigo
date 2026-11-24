<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            // Only the response columns the table shows, not its body and headers.
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'response' => fn (HasOne $response) => $response->select(['id', 'request_id', 'status_code', 'duration', 'cached']),
            ]))
            // ->recordTitleAttribute('name')
            ->recordUrl(fn (AmigoRequest $record) => AmigoRequestResource::getUrl('view', ['record' => $record]))
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
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereIn(
                        $query->qualifyColumn('endpoint_id'),
                        AmigoEndpoint::query()->whereLike('path', "%{$search}%")->select('id'),
                    )),
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
                        $duration = $record->response->duration ?? 99999;
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
