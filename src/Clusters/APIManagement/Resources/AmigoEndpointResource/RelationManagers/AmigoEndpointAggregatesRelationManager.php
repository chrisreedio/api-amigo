<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoEndpointAggregatesRelationManager extends RelationManager
{
    protected static string $relationship = 'aggregates';

    protected static string | \BackedEnum | null $icon = 'far-chart-bar';

    // protected static string $relationship = 'aggregates';

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
                // Tables\Columns\TextColumn::make('endpoint.name')
                //     ->label('Endpoint')
                //     ->tooltip(fn (AmigoRequest $record) => $record->endpoint->path)
                //     ->searchable()
                //     ->sortable(),
                TextColumn::make('min_duration')
                    ->label('Min Duration')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('max_duration')
                    ->label('Max Duration')
                    ->sortable()
                    ->formatStateUsing(fn (AmigoEndpointAggregate $record) => $record->max_duration * 1000)
                    ->suffix('ms')
                    ->toggleable(isToggledHiddenByDefault: false),

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
                // Tables\Actions\ViewAction::make()->url(fn (AmigoRequest $request) => $request->response == null ? null : AmigoResponseResource::getUrl('view', ['record' => $request->response])),
            ]);
        // ->bulkActions([
        // Tables\Actions\BulkActionGroup::make([
        //     Tables\Actions\DeleteBulkAction::make(),
        // ]),
        // ]);
    }
}
