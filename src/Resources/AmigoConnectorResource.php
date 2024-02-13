<?php

namespace ChrisReedIO\APIAmigo\Resources;

use ChrisReedIO\APIAmigo\Models\AmigoConnector;
use ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\Pages;
use ChrisReedIO\APIAmigo\Resources\AmigoConnectorResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoConnectorResource extends Resource
{
    protected static ?string $model = AmigoConnector::class;

    protected static ?string $navigationIcon = 'far-plug';

    protected static ?string $modelLabel = 'Connector';

    public static function getNavigationGroup(): ?string
    {
        return config('api-amigo.filament.navigation_group');
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
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

                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
                    ->badge()
                    ->counts('requests')
                    ->sortable(),

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
            RelationManagers\AmigoEndpointsRelationManager::class,
            RelationManagers\AmigoRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmigoConnectors::route('/'),
            // 'create' => Pages\CreateAmigoConnector::route('/create'),
            'view' => Pages\ViewAmigoConnector::route('/{record}'),
            // 'edit' => Pages\EditAmigoConnector::route('/{record}/edit'),
        ];
    }
}
