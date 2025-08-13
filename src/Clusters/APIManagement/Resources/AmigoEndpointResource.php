<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointResponsesTableChart;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// use LaraZeus\InlineChart\Tables\Columns\InlineChart;

use function config;

class AmigoEndpointResource extends Resource
{
    use ExposesTableToWidgets;

    protected static ?string $model = AmigoEndpoint::class;

    protected static ?string $navigationIcon = 'far-outlet';

    protected static ?string $modelLabel = 'Endpoint';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'endpoints';

    // public static function getNavigationGroup(): ?string
    // {
    //     return config('api-amigo.filament.navigation_group');
    // }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(4)
            ->schema([
                Infolists\Components\TextEntry::make('name')->placeholder('No Name'),
                // ->getStateUsing(fn (AmigoEndpoint $record) => $record->attributes['name']),
                Infolists\Components\TextEntry::make('connector.name'),
                Infolists\Components\TextEntry::make('connector.integration.name')
                    ->label('Integration'),
                Infolists\Components\TextEntry::make('method')
                    ->badge(),
                Infolists\Components\TextEntry::make('styled_path')
                    ->label('Path')
                    ->columnSpan(2)
                    ->html(),
                Infolists\Components\TextEntry::make('class')
                    ->columnSpan(2)
                    ->placeholder('None'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\Select::make('connector_id')
                    ->relationship('connector', 'name')
                    ->required(),
                Forms\Components\TextInput::make('path')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('method')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('class')
                    ->columnSpanFull()
                    ->maxLength(255),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('connector.integration.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('connector.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->placeholder('Not Set')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->searchable()
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('styled_path')
                    ->label('Path')
                    ->copyable()
                    ->sortable()
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Responses')
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->counts('responses')
                    ->sortable(),
                // InlineChart::make('response_chart')
                //     ->label('Successful vs Failed Requests')
                //     ->chart(EndpointResponsesTableChart::class)
                //     ->maxWidth(350)// int, default 200
                //     ->maxHeight(90)// int, default 50
                //     // ->description('description')
                //     ->toggleable(),

                // Tables\Columns\TextColumn::make('aggregates_avg_duration')
                //     // ->avg('responses', 'duration')
                //     ->avg('aggregates', 'avg_duration')
                //     ->badge()
                // ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                // ->color(function ($state) {
                //     if ($state >= config('api-amigo.thresholds.duration.error')) {
                //         return Color::Red;
                //     } elseif ($state >= config('api-amigo.thresholds.duration.warning')) {
                //         return Color::Yellow;
                //     } else {
                //         return Color::Green;
                //     }
                // })
                // ->label('Avg. Duration')
                // ->numeric()
                // ->sortable(),
                // ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('responses_count', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('connector_id')
                    ->label('Connector')
                    ->relationship('connector', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AmigoRequestsRelationManager::class,
            // RelationManagers\AmigoEndpointAggregatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ListAmigoEndpoints::route('/'),
            // 'create' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\CreateAmigoEndpoint::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ViewAmigoEndpoint::route('/{record}'),
            // 'edit' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\EditAmigoEndpoint::route('/{record}/edit'),
            'stats' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ViewEndpointAggregates::route('/{record}/stats'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointStatsOverview::class,
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointResponsesChart::class,
            EndpointResponsesTableChart::class,

            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointListOverview::class,
        ];
    }
}
