<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\RelationManagers\AmigoConnectorsRelationManager;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages\ListAmigoIntegrations;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages\CreateAmigoIntegration;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages\ViewAmigoIntegration;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoIntegrationResource\Pages\EditAmigoIntegration;
use BackedEnum;
use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoIntegrationResource extends Resource
{
    protected static ?string $model = AmigoIntegration::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-integral';

    protected static ?string $modelLabel = 'Integration';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'integrations';

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
            ->columns(2)
            ->schema([
                TextEntry::make('name'),

                TextEntry::make('display_name')
                    ->placeholder('Not Set')
                    ->label('Display Name'),

                // Infolists\Components\TextEntry::make('total_requests')
                //     ->numeric()
                //     ->label('Total Requests'),

                // Infolists\Components\TextEntry::make('total_errors')
                //     ->numeric()
                //     ->label('Total Errors'),

                // Infolists\Components\TextEntry::make('deleted_at')
                //     ->dateTime()
                //     ->label('Deleted At'),

                // Infolists\Components\TextEntry::make('created_at')
                //     ->dateTime()
                //     ->label('Created At'),

                // Infolists\Components\TextEntry::make('updated_at')
                //     ->dateTime()
                //     ->label('Updated At'),
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

                TextInput::make('display_name')
                    // ->required()
                    ->maxLength(255),

                // Forms\Components\ColorPicker::make('color')
                //     ->required(),

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
                    ->searchable(),
                TextColumn::make('display_name')
                    ->placeholder('Not Set')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('total_requests')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('total_errors')
                //     ->numeric()
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
                // Tables\Actions\ViewAction::make(),
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
            AmigoConnectorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoIntegrations::route('/'),
            'create' => CreateAmigoIntegration::route('/create'),
            'view' => ViewAmigoIntegration::route('/{record}'),
            'edit' => EditAmigoIntegration::route('/{record}/edit'),
        ];
    }
}
