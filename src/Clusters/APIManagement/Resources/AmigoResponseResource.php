<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages\CreateAmigoResponse;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages\EditAmigoResponse;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages\ListAmigoResponses;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\Pages\ViewAmigoResponse;
use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Phiki\Grammar\Grammar;

use function collect;
use function config;
use function number_format;

// use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource\RelationManagers;

class AmigoResponseResource extends Resource
{
    protected static ?string $model = AmigoResponse::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-reply';

    protected static ?string $modelLabel = 'Response';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'responses';

    // public static function getNavigationGroup(): ?string
    // {
    //     return config('api-amigo.filament.navigation_group');
    // }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('amigo_response_count', 60 * 1, fn () => number_format(static::getModel()::count()));
        // TODO: This should be used once we have hourly aggregation going
        // return number_format(AmigoEndpointAggregate::query()->sum('total_requests'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->schema([
                TextEntry::make('endpoint.connector.integration.name')
                    ->label('Integration')
                    ->url(fn (AmigoResponse $record) => AmigoIntegrationResource::getUrl('view', ['record' => $record->endpoint->connector->integration]))
                    ->icon('far-integral'),

                TextEntry::make('endpoint.connector.name')
                    ->label('Connector')
                    ->url(fn (AmigoResponse $record) => AmigoConnectorResource::getUrl('view', ['record' => $record->endpoint->connector]))
                    // ->icon('far-outlet'),
                    ->icon('far-plug'),

                TextEntry::make('endpoint.display_name')
                    ->label('Endpoint')
                    ->url(fn (AmigoResponse $record) => AmigoEndpointResource::getUrl('view', ['record' => $record->endpoint]))
                    ->icon('far-outlet'),

                TextEntry::make('response_size')
                    ->label('Response Size')
                    ->numeric()
                    ->getStateUsing(fn (AmigoResponse $record) => Number::fileSize($record->body_size, 2))
                    ->color(function (AmigoResponse $record) {
                        $size = $record->body_size;

                        return match (true) {
                            $size >= config('api-amigo.thresholds.response_size.error') => Color::Red,
                            $size >= config('api-amigo.thresholds.response_size.warning') => Color::Yellow,
                            default => Color::Green,
                        };
                    })
                    ->icon('far-hard-drive'),

                Grid::make(7)
                    ->schema([
                        TextEntry::make('status_code')
                            ->label('Status')
                            ->formatStateUsing(fn (AmigoResponse $record) => $record->status_code->value . ' ' . $record->status_code->getLabel())
                            ->badge(),

                        TextEntry::make('duration')
                            ->label('Duration')
                            ->formatStateUsing(fn (AmigoResponse $record) => ($record->duration * 1000) . 'ms')
                            ->color(function ($state) {
                                return match (true) {
                                    $state >= config('api-amigo.thresholds.duration.error') => Color::Red,
                                    $state >= config('api-amigo.thresholds.duration.warning') => Color::Yellow,
                                    default => Color::Green,
                                };
                            })
                            ->icon('far-stopwatch')
                            ->badge(),

                        TextEntry::make('cached')
                            ->label('Cached')
                            ->badge()
                            ->color(fn (AmigoResponse $record) => $record->cached ? Color::Green : Color::Red)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->icon('far-database'),

                        TextEntry::make('request.path')
                            ->label('Request Path')
                            ->copyable()
                            ->columnSpan(4)
                            ->formatStateUsing(fn ($state) => new HtmlString('<code>' . $state . '</code>'))
                            ->icon('far-sign-post'),
                    ]),

                TextEntry::make('no_response_body')
                    ->label('Response Body')
                    ->placeholder('No Recorded Response Body')
                    ->visible(fn (AmigoResponse $record) => $record->body === null || $record->body === []),

                Section::make('Response Headers')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CodeEntry::make('headers')
                            // ->label('Webhook Headers')
                            ->label('')
                            ->columnSpanFull(),
                    ]),

                CodeEntry::make('body')
                    ->hidden(function (AmigoResponse $record) {
                        if ($record->body_size >= config('api-amigo.thresholds.response_size.error')) {
                            return true;
                        }

                        if ($record->body === null || $record->body === []) {
                            return true;
                        }

                        return false;
                    })
                    ->label('Response Body Contents')
                    ->grammar(Grammar::Json)
                    // ->getStateUsing(fn (AmigoResponse $record) => json_encode($record->body, JSON_PRETTY_PRINT))
                    // ->getStateUsing(fn (AmigoResponse $record) => $record->getOriginal('body'))
                    ->columnSpanFull(),

                TextEntry::make('large_body')
                    ->hidden(fn (AmigoResponse $record) => $record->body_size < config('api-amigo.thresholds.response_size.error'))
                    ->label('Response Body')
                    ->columnSpanFull()
                    ->placeholder('Response body is too large to display. Click "Download Response" in the top right to download a JSON dump file.'),
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

                TextColumn::make('endpoint.connector.integration.name')
                    ->label('Integration')
                    // ->badge()
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('endpoint.connector.name')
                    ->label('Connector')
                    // ->tooltip(fn (AmigoResponse $record) => $record->endpoint->connector->base_url)
                    // ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('endpoint.method')
                    ->label('Method')
                    ->alignCenter()
                    ->badge()
                    ->sortable(),

                TextColumn::make('endpoint.display_name')
                    ->label('Endpoint')
                    // ->getStateUsing(fn (AmigoResponse $record) => $record->endpoint->name ?? $record->endpoint->path ?? 'hi')
                    ->tooltip(fn (AmigoResponse $record) => $record->endpoint->path)
                    ->placeholder('No Endpoint')
                    ->badge(fn (AmigoResponse $record) => $record->endpoint->name !== null)
                    // ->url(fn (AmigoResponse $record) => AmigoEndpointResource::getUrl('view', ['record' => $record->endpoint]))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (AmigoResponse $record) => $record->status_code->value . ' ' . $record->status_code->getLabel())
                    ->alignCenter()
                    ->badge()
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->alignCenter()
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

                TextColumn::make('cached')
                    ->label('Cached')
                    ->icon('far-database')
                    ->color(fn (AmigoResponse $record) => $record->cached ? Color::Green : Color::Red)
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->placeholder('N/A')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('connector_id')
                    ->label('Connector')
                    ->relationship('endpoint.connector', 'name'),
                SelectFilter::make('status_code')
                    ->getOptionLabelUsing(fn ($value) => $value->value . ' - ' . $value->getLabel())
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->options(function () {
                        return collect(HTTPStatus::cases())
                            ->mapWithKeys(fn (HTTPStatus $code) => [$code->value => $code->value . ' - ' . $code->getLabel()]);
                    }),
                TernaryFilter::make('success')
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
                QueryBuilder::make()
                    ->constraints([
                        DateConstraint::make('created_at'),
                    ]),
            ])
            ->persistFiltersInSession()
            ->filtersFormWidth('xl')
            ->defaultSort('created_at', 'desc')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoResponses::route('/'),
            'create' => CreateAmigoResponse::route('/create'),
            'view' => ViewAmigoResponse::route('/{record}'),
            'edit' => EditAmigoResponse::route('/{record}/edit'),
        ];
    }
}
