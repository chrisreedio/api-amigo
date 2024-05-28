<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages;
use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoConnectorResource extends Resource
{
    protected static ?string $model = AmigoConnector::class;

    protected static ?string $navigationIcon = 'far-plug';

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
            ->schema([
                // Infolists\Components\TextEntry::make('name'),

                Infolists\Components\TextEntry::make('integration.name')
                    ->url(fn (AmigoConnector $record) => AmigoIntegrationResource::getUrl('view', ['record' => $record->integration]))
                    ->placeholder('All Integrations')
                    ->label('Integration'),

                Infolists\Components\TextEntry::make('display_name')
                    ->placeholder('Not Set')
                    ->label('Display Name'),

                Infolists\Components\TextEntry::make('rate_limit')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Limit'),

                Infolists\Components\TextEntry::make('rate_usage')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Usage'),

                Infolists\Components\TextEntry::make('rate_limit_remaining')
                    ->numeric()
                    ->badge()
                    ->placeholder('Unknown')
                    ->label('Rate Limit Remaining'),

                Infolists\Components\TextEntry::make('rate_usage_percentage')
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->readOnly()
                    ->maxLength(255),

                Forms\Components\Select::make('integration_id')
                    ->relationship('integration', 'name')
                    ->required(),

                Forms\Components\TextInput::make('display_name')
                    // ->required()
                    ->maxLength(255),

                Forms\Components\ColorPicker::make('color')
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
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('integration.name')
                    ->sortable()
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('endpoints_count')
                    ->label('Endpoints')
                    ->badge()
                    ->counts('endpoints')
                    ->sortable(),

                Tables\Columns\TextColumn::make('aggregates_sum_total_requests')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoEndpointsRelationManager::class,
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\RelationManagers\AmigoRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages\ListAmigoConnectors::route('/'),
            // 'create' => Pages\CreateAmigoConnector::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoConnectorResource\Pages\ViewAmigoConnector::route('/{record}'),
            // 'edit' => Pages\EditAmigoConnector::route('/{record}/edit'),
        ];
    }
}
