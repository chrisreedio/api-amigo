<?php

namespace ChrisReedIO\APIAmigo\Resources;

use ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\RelationManagers;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

use function config;

class AmigoEndpointResource extends Resource
{
    protected static ?string $model = AmigoEndpoint::class;

    protected static ?string $navigationIcon = 'far-outlet';

    protected static ?string $modelLabel = 'Endpoint';

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
                Tables\Columns\TextColumn::make('connector.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->searchable()
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('styled_path')
                    ->copyable()
                    ->sortable()
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Responses')
                    ->badge()
                    ->counts('responses')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responses_avg_duration')
                    ->avg('responses', 'duration')
                    ->badge()
                    ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                    ->label('Avg. Duration')
                    // ->numeric()
                    ->sortable(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages\ListAmigoEndpoints::route('/'),
            // 'create' => \ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages\CreateAmigoEndpoint::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages\ViewAmigoEndpoint::route('/{record}'),
            // 'edit' => \ChrisReedIO\APIAmigo\Resources\AmigoEndpointResource\Pages\EditAmigoEndpoint::route('/{record}/edit'),
        ];
    }
}
