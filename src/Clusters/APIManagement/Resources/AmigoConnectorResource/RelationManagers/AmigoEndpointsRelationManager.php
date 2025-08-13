<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AmigoEndpointsRelationManager extends RelationManager
{
    protected static string $relationship = 'endpoints';

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
                TextColumn::make('method')
                    ->searchable()
                    ->badge()
                    ->sortable(),
                TextColumn::make('styled_path')
                    ->label('Path')
                    ->sortable()
                    ->html()
                    ->searchable(),
                TextColumn::make('responses_count')
                    ->label('Responses')
                    ->badge()
                    ->counts('responses')
                    ->sortable(),
                TextColumn::make('responses_avg_duration')
                    ->avg('responses', 'duration')
                    ->badge()
                    ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                    ->color(function ($state) {
                        if ($state >= config('api-amigo.thresholds.duration.error')) {
                            return Color::Red;
                        } elseif ($state >= config('api-amigo.thresholds.duration.warning')) {
                            return Color::Yellow;
                        } else {
                            return Color::Green;
                        }
                    })
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
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                ViewAction::make()->url(fn (AmigoEndpoint $endpoint) => AmigoEndpointResource::getUrl('view', ['record' => $endpoint])),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
