<?php

namespace ChrisReedIO\APIAmigo\Resources;

use ChrisReedIO\APIAmigo\Models\AmigoResponse;
// use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\RelationManagers;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoResponseResource extends Resource
{
    protected static ?string $model = AmigoResponse::class;

    protected static ?string $navigationIcon = 'far-reply';

    protected static ?string $modelLabel = 'Response';

    public static function getNavigationGroup(): ?string
    {
        return config('api-amigo.filament.navigation_group');
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
                // Tables\Columns\TextColumn::make('name')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('display_name')
                //     ->placeholder('Not Set')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('endpoint.connector.integration.name')
                    ->label('Integration')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('endpoint.connector.name')
                    ->label('Connector')
                    ->tooltip(fn (AmigoResponse $record) => $record->endpoint->connector->base_url)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('endpoint.path')
                    ->label('Endpoint')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->badge()
                    ->getStateUsing(fn (AmigoResponse $record) => ($record->duration * 1000) . 'ms')
                    // ->suffix('s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListAmigoResponses::route('/'),
            'create' => Pages\CreateAmigoResponse::route('/create'),
            'view' => Pages\ViewAmigoResponse::route('/{record}'),
            'edit' => Pages\EditAmigoResponse::route('/{record}/edit'),
        ];
    }
}
