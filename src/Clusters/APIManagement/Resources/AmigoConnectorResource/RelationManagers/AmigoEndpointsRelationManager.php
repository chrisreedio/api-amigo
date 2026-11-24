<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoEndpointAggregate;
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
use Illuminate\Database\Eloquent\Builder;

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
            // Averages each endpoint's hourly aggregates, weighted by their request counts.
            ->modifyQueryUsing(fn (Builder $query) => $query->addSelect([
                'avg_duration' => AmigoEndpointAggregate::query()
                    ->selectRaw('sum(avg_duration * total_requests) / nullif(sum(total_requests), 0)')
                    ->whereColumn('endpoint_id', $query->qualifyColumn('id')),
            ]))
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
                    ->sortable(['path'])
                    ->html()
                    ->searchable(['path']),
                TextColumn::make('aggregates_sum_total_requests')
                    ->label('Responses')
                    ->badge()
                    ->sum('aggregates', 'total_requests')
                    ->placeholder('No Data')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('avg_duration')
                    ->placeholder('No Data')
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
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('avg_duration', $direction)),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('aggregates_sum_total_requests desc nulls last'))
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
