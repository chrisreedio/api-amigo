<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\RelationManagers;

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

class AmigoConnectorsRelationManager extends RelationManager
{
    protected static string $relationship = 'connectors';

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
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('integration.name')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('display_name')
                    ->placeholder('Not Set')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_usage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_limit_remaining')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_usage_percentage')
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
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
