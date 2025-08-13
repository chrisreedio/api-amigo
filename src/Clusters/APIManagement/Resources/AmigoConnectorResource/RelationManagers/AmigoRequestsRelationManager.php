<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use function config;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->columns([
                // Tables\Columns\TextColumn::make('endpoint.connector.name')
                //     ->label('Connector'),
                // Tables\Columns\TextColumn::make('endpoint.name')
                //     ->label('Endpoint'),
                // Tables\Columns\TextColumn::make('endpoint.path')
                //     ->label('Endpoint')
                //     ->formatStateUsing(function ($state) {
                //         $replacedVars = preg_replace('/\{.*?\}/', '<code style="color:#ea580c;">$0</code>', $state);
                //
                //         return new HtmlString("$replacedVars");
                //     })
                //     ->html()
                //     ->copyable()
                //     ->searchable(),
                TextColumn::make('endpoint.name')
                    ->label('Endpoint')
                    ->tooltip(fn (AmigoRequest $record) => $record->endpoint->path)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('path')
                    ->label('Path')
                    ->getStateUsing(fn (AmigoRequest $record) => $record->path ?? $record->endpoint->styled_path)
                    ->html()
                    ->copyable()
                    ->searchable(),

                TextColumn::make('response.status_code')
                    ->label('Status')
                    ->placeholder('No Response')
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                    ->badge()
                    ->sortable(),

                TextColumn::make('response.duration')
                    ->label('Duration')
                    ->badge()
                    ->color(function ($record) {
                        $duration = $record->response->duration;
                        if ($duration >= config('api-amigo.thresholds.duration.error')) {
                            return Color::Red;
                        } elseif ($duration >= config('api-amigo.thresholds.duration.warning')) {
                            return Color::Yellow;
                        } else {
                            return Color::Green;
                        }
                    })
                    ->getStateUsing(fn (AmigoRequest $record) => $record->response ? ($record->response->duration * 1000) . 'ms' : null)
                    ->placeholder('No Response')
                    // ->suffix('s')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('requests_count')
                //     ->label('Requests')
                //     ->badge()
                //     ->counts('requests')
                //     ->sortable(),

                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                ViewAction::make()->url(fn (AmigoRequest $request) => $request->response == null ? null : AmigoResponseResource::getUrl('view', ['record' => $request->response])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
