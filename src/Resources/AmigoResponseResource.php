<?php

namespace ChrisReedIO\APIAmigo\Resources;

use const JSON_PRETTY_PRINT;

use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Parallax\FilamentSyntaxEntry\SyntaxEntry;

// use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\RelationManagers;
use function collect;
use function config;

class AmigoResponseResource extends Resource
{
    protected static ?string $model = AmigoResponse::class;

    protected static ?string $navigationIcon = 'far-reply';

    protected static ?string $modelLabel = 'Response';

    public static function getNavigationGroup(): ?string
    {
        return config('api-amigo.filament.navigation_group');
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
            ->schema([
                Infolists\Components\TextEntry::make('endpoint.connector.integration.name')
                    ->label('Integration')
                    ->url(fn (AmigoResponse $record) => AmigoIntegrationResource::getUrl('view', ['record' => $record->endpoint->connector->integration]))
                    ->icon('far-integral'),

                Infolists\Components\TextEntry::make('endpoint.connector.name')
                    ->label('Connector')
                    ->url(fn (AmigoResponse $record) => AmigoConnectorResource::getUrl('view', ['record' => $record->endpoint->connector]))
                    // ->icon('far-outlet'),
                    ->icon('far-plug'),

                Infolists\Components\TextEntry::make('endpoint.name')
                    ->label('Endpoint')
                    ->url(fn (AmigoResponse $record) => AmigoEndpointResource::getUrl('view', ['record' => $record->endpoint]))
                    ->icon('far-outlet'),

                Infolists\Components\Grid::make(6)
                    ->schema([
                        Infolists\Components\TextEntry::make('status_code')
                            ->label('Status')
                            ->formatStateUsing(fn (AmigoResponse $record) => $record->status_code->value . ' ' . $record->status_code->getLabel())
                            ->badge(),

                        Infolists\Components\TextEntry::make('duration')
                            ->label('Duration')
                            ->formatStateUsing(fn (AmigoResponse $record) => ($record->duration * 1000) . 'ms')
                            // ->suffix('s')
                            ->color(function ($state) {
                                if ($state >= config('api-amigo.thresholds.duration.error')) {
                                    return Color::Red;
                                } elseif ($state >= config('api-amigo.thresholds.duration.warning')) {
                                    return Color::Yellow;
                                } else {
                                    return Color::Green;
                                }
                            })
                            ->icon('far-stopwatch')
                            ->badge(),

                        Infolists\Components\TextEntry::make('request.path')
                            ->label('Request Path')
                            ->copyable()
                            ->columnSpan(4)
                            // ->url(fn (AmigoResponse $record) => AmigoRequestResource::getUrl('view', ['record' => $record->request]))
                            ->formatStateUsing(function ($state) {
                                // $replacedVars = preg_replace('/\{.*?\}/', '<code style="color:#ea580c;">$0</code>', $state);
                                $formatted = '<code>' . $state . '</code>';

                                return new HtmlString($formatted);
                            })
                            ->icon('far-sign-post'),
                    ]),

                SyntaxEntry::make('body')
                    ->label('Response Body Contents')
                    ->columnSpanFull()
                    ->getStateUsing(fn (AmigoResponse $record) => json_encode($record->body, JSON_PRETTY_PRINT)),
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

                Tables\Columns\TextColumn::make('endpoint.name')
                    ->label('Endpoint')
                    ->tooltip(fn (AmigoResponse $record) => $record->endpoint->path)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (AmigoResponse $record) => $record->status_code->value . ' ' . $record->status_code->getLabel())
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->badge()
                    ->color(function ($record) {
                        $duration = $record->duration;
                        if ($duration >= config('api-amigo.thresholds.duration.error')) {
                            return Color::Red;
                        } elseif ($duration >= config('api-amigo.thresholds.duration.warning')) {
                            return Color::Yellow;
                        } else {
                            return Color::Green;
                        }
                    })
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
                Tables\Filters\SelectFilter::make('connector_id')
                    ->label('Connector')
                    ->relationship('endpoint.connector', 'name'),
                Tables\Filters\SelectFilter::make('status_code')
                    ->getOptionLabelUsing(fn ($value) => $value->value . ' - ' . $value->getLabel())
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->options(function () {
                        return collect(HTTPStatus::cases())
                            ->mapWithKeys(fn (HTTPStatus $code) => [$code->value => $code->value . ' - ' . $code->getLabel()]);
                    }),
                Tables\Filters\TernaryFilter::make('success')
                    ->label('Fail or Success')
                    ->attribute('status_code')
                    ->boolean()
                    ->falseLabel('Failed')
                    ->trueLabel('Success')
                    ->queries(
                        true: fn ($query, $value) => $query->where('status_code', 'like', '2%'),
                        false: fn ($query, $value) => $query->where('status_code', 'not like', '2%'),
                        blank: fn ($query, $value) => $query,
                    ),
                // ->getOptionLabelUsing(fn ($value) => 'hi'), //$value->value . ' - ' . $value->getLabel()),
                // ->getOptionLabelsUsing(fn ($values) => $values->map(fn ($value) => $value->value . ' - ' . $value->getLabel())),
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        DateConstraint::make('created_at'),
                    ]),
            ])
            ->persistFiltersInSession()
            ->filtersFormWidth('xl')
            ->defaultSort('created_at', 'desc')
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
