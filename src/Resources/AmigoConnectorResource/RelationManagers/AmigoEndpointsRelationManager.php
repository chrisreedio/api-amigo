<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\RelationManagers;

use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoEndpointsRelationManager extends RelationManager
{
    protected static string $relationship = 'endpoints';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->searchable()
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('styled_path')
                    ->label('Path')
                    ->sortable()
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Responses')
                    ->badge()
                    ->counts('responses')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responses_avg_duration')
                    ->avg('responses', 'duration')
                    ->badge()
                    ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                    ->label('Avg. Duration')
                    // ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('responses_count', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()->url(fn (AmigoEndpoint $endpoint) => AmigoEndpointResource::getUrl('view', ['record' => $endpoint])),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
