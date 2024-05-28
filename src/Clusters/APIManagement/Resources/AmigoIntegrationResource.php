<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoIntegrationResource extends Resource
{
    protected static ?string $model = AmigoIntegration::class;

    protected static ?string $navigationIcon = 'far-integral';

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(2)
            ->schema([
                Infolists\Components\TextEntry::make('name'),

                Infolists\Components\TextEntry::make('display_name')
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->readOnly()
                    ->maxLength(255),

                Forms\Components\TextInput::make('display_name')
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
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
                // Tables\Actions\ViewAction::make(),
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
            APIManagement\Resources\AmigoIntegrationResource\RelationManagers\AmigoConnectorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => APIManagement\Resources\AmigoIntegrationResource\Pages\ListAmigoIntegrations::route('/'),
            'create' => APIManagement\Resources\AmigoIntegrationResource\Pages\CreateAmigoIntegration::route('/create'),
            'view' => APIManagement\Resources\AmigoIntegrationResource\Pages\ViewAmigoIntegration::route('/{record}'),
            'edit' => APIManagement\Resources\AmigoIntegrationResource\Pages\EditAmigoIntegration::route('/{record}/edit'),
        ];
    }
}
