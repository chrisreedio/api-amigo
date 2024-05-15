<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoConnectorsRelationManager extends RelationManager
{
    protected static string $relationship = 'connectors';

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
                // Tables\Columns\TextColumn::make('integration.name')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->placeholder('Not Set')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_limit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_usage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_limit_remaining')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_usage_percentage')
                    ->numeric()
                    ->label('Usage Percent')
                    ->badge()
                    ->color(function ($record) {
                        if ($record->rate_usage_percentage > 90) {
                            return Color::Red;
                        }
                        if ($record->rate_usage_percentage > 70) {
                            return Color::Yellow;
                        }

                        return Color::Green;
                    })
                    ->suffix('%')
                    ->sortable(),
                // Tables\Columns\TextColumn::make('endpoints_count')
                //     ->label('Endpoints')
                //     ->badge()
                //     ->counts('endpoints')
                //     ->sortable(),
                //
                // Tables\Columns\TextColumn::make('requests_count')
                //     ->label('Requests')
                //     ->badge()
                //     ->counts('requests')
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('deleted_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
