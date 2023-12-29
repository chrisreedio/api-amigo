<?php

namespace ChrisReedIO\APIAmigo\Resources;

use ChrisReedIO\APIAmigo\Models\AmigoIntegration;
// use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource\RelationManagers;
use ChrisReedIO\APIAmigo\Resources\AmigoIntegrationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoIntegrationResource extends Resource
{
    protected static ?string $model = AmigoIntegration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'API Amigo';

    protected static ?string $navigationLabel = 'Integrations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('total_requests')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_errors')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmigoIntegrations::route('/'),
            'create' => Pages\CreateAmigoIntegration::route('/create'),
            'view' => Pages\ViewAmigoIntegration::route('/{record}'),
            'edit' => Pages\EditAmigoIntegration::route('/{record}/edit'),
        ];
    }
}
