<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoEndpointsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoRequestsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages\ListAmigoConnectors;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages\ViewAmigoConnector;
use BackedEnum;
use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoConnectorResource extends Resource
{
    protected static ?string $model = AmigoConnector::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-plug';

    protected static ?string $modelLabel = 'Connector';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'connectors';

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
            ->columns(3)
            ->schema([
                // Infolists\Components\TextEntry::make('name'),

                TextEntry::make('integration.name')
                    ->url(fn (AmigoConnector $record) => AmigoIntegrationResource::getUrl('view', ['record' => $record->integration]))
                    ->placeholder('All Integrations')
                    ->label('Integration'),

                TextEntry::make('display_name')
                    ->placeholder('Not Set')
                    ->label('Display Name'),

                TextEntry::make('rate_limit')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Limit'),

                TextEntry::make('rate_usage')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Usage'),

                TextEntry::make('rate_limit_remaining')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Limit Remaining'),

                TextEntry::make('rate_usage_percentage')
                    ->numeric()
                    ->badge()
                    ->label('Usage Percent')
                    ->placeholder('Unknown')
                    ->suffix('%'),

                // Infolists\Components\TextEntry::make('endpoints_count')
                //     ->label('Endpoints')
                //     ->placeholder('Unknown')
                //     ->badge(),
                //
                // Infolists\Components\TextEntry::make('aggregates_sum_total_requests')
                //     ->label('Requests')
                //     ->placeholder('Unknown')
                //     ->badge()
                //     ->numeric(),

                // Infolists\Components\TextEntry::make('requests_count')
                //     ->label('Requests')
                //     ->badge(),

                // Infolists\Components\TextEntry::make('deleted_at')
                //     ->dateTime()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Infolists\Components\TextEntry::make('created_at')
                //     ->dateTime(),
                // Infolists\Components\TextEntry::make('updated_at')
                //     ->dateTime(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->readOnly()
                    ->maxLength(255),

                Select::make('integration_id')
                    ->relationship('integration', 'name')
                    ->required(),

                TextInput::make('display_name')
                    // ->required()
                    ->maxLength(255),

                ColorPicker::make('color')
                    ->required(),

                // Forms\Components\TextInput::make('total_requests')
                //     ->required()
                //     ->numeric(),
                // Forms\Components\TextInput::make('total_errors')
                //     ->required()
                //     ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('integration.name')
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('endpoints_count')
                    ->label('Endpoints')
                    ->badge()
                    ->counts('endpoints')
                    ->sortable(),

                TextColumn::make('aggregates_sum_total_requests')
                    ->label('Requests')
                    ->badge()
                    ->sum('aggregates', 'total_requests')
                    ->placeholder('No Data')
                    ->numeric()
                    ->sortable(),

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
            ->filters([
                //
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
            AmigoEndpointsRelationManager::class,
            AmigoRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoConnectors::route('/'),
            // 'create' => Pages\CreateAmigoConnector::route('/create'),
            'view' => ViewAmigoConnector::route('/{record}'),
            // 'edit' => Pages\EditAmigoConnector::route('/{record}/edit'),
        ];
    }
}
