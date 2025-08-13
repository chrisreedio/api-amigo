<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ViewEndpointAggregates extends ManageRelatedRecords
{
    protected static string $resource = AmigoEndpointResource::class;

    protected static string $relationship = 'aggregates';

    protected static string | \BackedEnum | null $navigationIcon = 'far-chart-bar';

    public static function getNavigationLabel(): string
    {
        return 'Aggregates';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('window_start')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('window_start')
            ->columns([
                TextColumn::make('window_start')->dateTime(),
                TextColumn::make('interval')
                    ->formatStateUsing(fn (AmigoEndpointAggregate $record) => $record->interval / 60)
                    ->suffix(' minutes'),

                TextColumn::make('min_duration')
                    ->label('Min Duration')
                    ->floatDuration()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('max_duration')
                    ->label('Max Duration')
                    ->floatDuration()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                // Tables\Actions\AssociateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DissociateAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ]);
        // ->bulkActions([
        //     Tables\Actions\BulkActionGroup::make([
        //         Tables\Actions\DissociateBulkAction::make(),
        //         Tables\Actions\DeleteBulkAction::make(),
        //     ]),
        // ]);
    }
}
