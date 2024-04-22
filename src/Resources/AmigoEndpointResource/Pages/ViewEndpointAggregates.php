<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages;

use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ViewEndpointAggregates extends ManageRelatedRecords
{
    protected static string $resource = AmigoEndpointResource::class;

    protected static string $relationship = 'aggregates';

    protected static ?string $navigationIcon = 'far-chart-bar';

    public static function getNavigationLabel(): string
    {
        return 'Aggregates';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('window_start')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('window_start')
            ->columns([
                Tables\Columns\TextColumn::make('window_start')->dateTime(),
                Tables\Columns\TextColumn::make('interval')
                    ->formatStateUsing(fn (AmigoEndpointAggregate $record) => $record->interval / 60)
                    ->suffix(' minutes'),

                Tables\Columns\TextColumn::make('min_duration')
                    ->label('Min Duration')
                    ->floatDuration()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('max_duration')
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
            ->actions([
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
