<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ListAmigoEndpoints;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ViewAmigoEndpoint;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\ViewEndpointAggregates;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers\AmigoRequestsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointListOverview;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointResponsesChart;
// use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointResponsesTableChart;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Widgets\EndpointStatsOverview;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

// use LaraZeus\InlineChart\Tables\Columns\InlineChart;

use function config;

class AmigoEndpointResource extends Resource
{
    use ExposesTableToWidgets;

    protected static ?string $model = AmigoEndpoint::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-outlet';

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->schema([
                TextEntry::make('name')->placeholder('No Name'),
                // ->getStateUsing(fn (AmigoEndpoint $record) => $record->attributes['name']),
                TextEntry::make('connector.name'),
                TextEntry::make('connector.integration.name')
                    ->label('Integration'),
                TextEntry::make('method')
                    ->badge(),
                TextEntry::make('styled_path')
                    ->label('Path')
                    ->columnSpan(2)
                    ->html(),
                TextEntry::make('class')
                    ->columnSpan(2)
                    ->placeholder('None'),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->maxLength(255),
                Select::make('connector_id')
                    ->relationship('connector', 'name')
                    ->required(),
                TextInput::make('path')
                    ->required()
                    ->maxLength(255),
                TextInput::make('method')
                    ->required()
                    ->maxLength(255),
                TextInput::make('class')
                    ->columnSpanFull()
                    ->maxLength(255),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('connector.integration.name')
                    ->sortable(),
                TextColumn::make('connector.name')
                    ->sortable(),
                TextColumn::make('name')
                    ->placeholder('Not Set')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable()
                    ->badge()
                    ->sortable(),
                TextColumn::make('styled_path')
                    ->label('Path')
                    ->copyable()
                    ->sortable()
                    ->html()
                    ->searchable(),
                TextColumn::make('responses_count')
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

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('responses_count', 'desc')
            ->filters([
                SelectFilter::make('connector_id')
                    ->label('Connector')
                    ->relationship('connector', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AmigoRequestsRelationManager::class,
            // RelationManagers\AmigoEndpointAggregatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoEndpoints::route('/'),
            // 'create' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\CreateAmigoEndpoint::route('/create'),
            'view' => ViewAmigoEndpoint::route('/{record}'),
            // 'edit' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\Pages\EditAmigoEndpoint::route('/{record}/edit'),
            'stats' => ViewEndpointAggregates::route('/{record}/stats'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            EndpointStatsOverview::class,
            EndpointResponsesChart::class,
            // EndpointResponsesTableChart::class,

            EndpointListOverview::class,
        ];
    }
}
