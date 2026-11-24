<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages\ListAmigoRequests;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource\Pages\ViewAmigoRequest;
use ChrisReedIO\APIAmigo\Enums\HTTPStatus;
use ChrisReedIO\APIAmigo\Models\AmigoEndpoint;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Phiki\Grammar\Grammar;

use function collect;
use function config;
use function filled;
use function json_decode;
use function json_encode;
use function json_validate;
use function now;
use function number_format;

/**
 * Each tracked call as one record: what was sent and what came back.
 */
class AmigoRequestResource extends Resource
{
    protected static ?string $model = AmigoRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-paper-plane';

    protected static ?string $modelLabel = 'Request';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'requests';

    public static function getNavigationBadge(): ?string
    {
        return number_format(AmigoRequest::estimatedCount());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->columns(6)
                    ->schema([
                        Flex::make([
                            TextEntry::make('endpoint.method')
                                ->hiddenLabel()
                                ->badge()
                                ->size(TextSize::Large)
                                ->grow(false),

                            TextEntry::make('path')
                                ->hiddenLabel()
                                ->fontFamily(FontFamily::Mono)
                                ->size(TextSize::Large)
                                ->weight(FontWeight::SemiBold)
                                ->copyable(),

                            TextEntry::make('response.status_code')
                                ->hiddenLabel()
                                ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                                ->placeholder('No response')
                                ->badge()
                                ->size(TextSize::Large)
                                ->grow(false),

                            TextEntry::make('response.duration')
                                ->hiddenLabel()
                                ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                                ->color(fn ($state) => self::durationColor($state))
                                ->placeholder('—')
                                ->badge()
                                ->size(TextSize::Large)
                                ->grow(false),
                        ])
                            ->verticallyAlignCenter()
                            ->columnSpanFull(),

                        TextEntry::make('endpoint.connector.integration.name')
                            ->label('Integration')
                            ->url(fn (AmigoRequest $record) => AmigoIntegrationResource::getUrl('view', ['record' => $record->endpoint->connector->integration]))
                            ->color('primary'),

                        TextEntry::make('endpoint.connector.name')
                            ->label('Connector')
                            ->url(fn (AmigoRequest $record) => AmigoConnectorResource::getUrl('view', ['record' => $record->endpoint->connector]))
                            ->color('primary'),

                        TextEntry::make('endpoint.display_name')
                            ->label('Endpoint')
                            ->url(fn (AmigoRequest $record) => AmigoEndpointResource::getUrl('view', ['record' => $record->endpoint]))
                            ->color('primary'),

                        TextEntry::make('created_at')
                            ->label('Sent')
                            ->since()
                            ->dateTimeTooltip(),

                        TextEntry::make('unique_id')
                            ->label('Request ID')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->columnSpan(2),
                    ]),

                Section::make('Request')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->iconColor('primary')
                    ->columnSpan(2)
                    ->schema(self::payloadEntries(
                        headers: fn (AmigoRequest $record) => $record->headers,
                        body: fn (AmigoRequest $record) => $record->body,
                        prefix: 'request',
                    )),

                Section::make('Response')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconColor('primary')
                    ->columnSpan(2)
                    ->schema(self::payloadEntries(
                        headers: fn (AmigoRequest $record) => $record->response?->headers,
                        // Bodies are stored JSON-encoded, so non-JSON bodies survive this, unlike AmigoResponse::$body.
                        body: fn (AmigoRequest $record) => json_decode($record->response?->getRawOriginal('body') ?? 'null', true),
                        prefix: 'response',
                    )),
            ]);
    }

    /**
     * @param  callable(AmigoRequest): ?array  $headers
     * @param  callable(AmigoRequest): ?string  $body
     */
    private static function payloadEntries(callable $headers, callable $body, string $prefix): array
    {
        $missing = 'Not captured. Enable api-amigo.responses.capture_body, or start a recording that captures bodies.';

        return [
            // Collapsed, since header lists run long and the body is usually what's being checked.
            Section::make(fn (AmigoRequest $record) => 'Headers (' . count($headers($record) ?? []) . ')')
                ->compact()
                ->secondary()
                ->collapsed()
                ->schema([
                    CodeEntry::make("{$prefix}_headers")
                        ->hiddenLabel()
                        ->state(fn (AmigoRequest $record) => $headers($record) ? json_encode($headers($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                        ->grammar(Grammar::Json)
                        ->placeholder($missing),
                ]),

            CodeEntry::make("{$prefix}_body")
                ->label('Body')
                ->hidden(fn (AmigoRequest $record) => self::isTooLarge($body($record)))
                ->state(fn (AmigoRequest $record) => self::prettyBody($body($record)))
                ->grammar(fn (AmigoRequest $record) => json_validate($body($record) ?? '') ? Grammar::Json : Grammar::Txt)
                ->placeholder(fn (AmigoRequest $record) => $headers($record) ? 'Empty' : $missing),

            TextEntry::make("{$prefix}_large_body")
                ->label('Body')
                ->visible(fn (AmigoRequest $record) => self::isTooLarge($body($record)))
                ->placeholder(fn (AmigoRequest $record) => Number::fileSize(strlen($body($record)), 2) . ' is too large to display. Download it from the top right.'),
        ];
    }

    private static function sentSince(?string $window): ?Carbon
    {
        return match ($window) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subDays(30),
            default => null,
        };
    }

    /** Highlighting a body this large would make the page unusably slow. */
    public static function isTooLarge(?string $body): bool
    {
        return strlen($body ?? '') >= config('api-amigo.thresholds.response_size.error');
    }

    private static function prettyBody(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (json_validate($body)) {
            return json_encode(json_decode($body), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $body;
    }

    private static function durationColor(?float $duration): array
    {
        return match (true) {
            $duration >= config('api-amigo.thresholds.duration.error') => Color::Red,
            $duration >= config('api-amigo.thresholds.duration.warning') => Color::Yellow,
            default => Color::Green,
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            // The list never shows payloads, only whether they were captured.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->select($query->qualifyColumns(['id', 'endpoint_id', 'unique_id', 'path', 'created_at']))
                ->selectRaw('headers is not null as captured')
                ->with(['response:id,request_id,status_code,duration,cached', 'endpoint.connector.integration']))
            ->columns([
                TextColumn::make('endpoint.connector.integration.name')
                    ->label('Integration')
                    ->toggleable(),

                TextColumn::make('endpoint.method')
                    ->label('Method')
                    ->alignCenter()
                    ->badge(),

                TextColumn::make('endpoint.display_name')
                    ->label('Endpoint')
                    ->badge()
                    // Matches endpoints first, so Postgres uses the endpoint_id index, as on the responses list.
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereIn('endpoint_id', AmigoEndpoint::query()
                        ->whereLike('name', "%{$search}%")
                        ->orWhereLike('path', "%{$search}%")
                        ->orWhereHas('connector.integration', fn (Builder $integration) => $integration->whereLike('name', "%{$search}%"))
                        ->select('id'))),

                TextColumn::make('path')
                    ->label('Path')
                    ->fontFamily(FontFamily::Mono)
                    ->limit(60)
                    ->tooltip(fn (AmigoRequest $record) => $record->path),

                TextColumn::make('response.status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                    ->placeholder('No response')
                    ->alignCenter()
                    ->badge(),

                TextColumn::make('response.duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => round($state * 1000) . 'ms')
                    ->color(fn ($state) => self::durationColor($state))
                    ->alignCenter()
                    ->badge(),

                IconColumn::make('response.cached')
                    ->label('Cached')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('captured')
                    ->label('Payload')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Recent calls are what's usually looked for, and a window keeps every other filter cheap.
                SelectFilter::make('sent')
                    ->label('Sent')
                    ->options([
                        'hour' => 'Last hour',
                        'day' => 'Last 24 hours',
                        'week' => 'Last 7 days',
                        'month' => 'Last 30 days',
                    ])
                    ->placeholder('All time')
                    ->default('day')
                    ->query(fn (Builder $query, array $data) => $query->when(
                        self::sentSince($data['value'] ?? null),
                        fn (Builder $query, Carbon $since) => $query->where('created_at', '>=', $since),
                    )),
                SelectFilter::make('connector_id')
                    ->label('Connector')
                    ->relationship('endpoint.connector', 'name'),
                SelectFilter::make('status_code')
                    ->label('Status')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => collect(HTTPStatus::cases())->mapWithKeys(fn (HTTPStatus $code) => [$code->value => $code->value . ' - ' . $code->getLabel()]))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['values'] ?? [],
                        fn (Builder $query, array $codes) => $query->whereHas('response', fn (Builder $response) => $response->whereIn('status_code', $codes)),
                    )),
                TernaryFilter::make('success')
                    ->label('Fail or Success')
                    ->trueLabel('Success')
                    ->falseLabel('Failed')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('response', fn (Builder $response) => $response->whereBetween('status_code', [200, 299])),
                        false: fn (Builder $query) => $query->whereHas('response', fn (Builder $response) => $response->whereNotBetween('status_code', [200, 299])),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('slow')
                    ->label(fn () => 'Slow (' . round(config('api-amigo.thresholds.duration.error') * 1000) . 'ms or more)')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('response', fn (Builder $response) => $response->where('duration', '>=', config('api-amigo.thresholds.duration.error')))),
                Filter::make('duration')
                    ->form([
                        TextInput::make('slower_than')
                            ->label('Slower than (ms)')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['slower_than'] ?? null,
                        fn (Builder $query, $milliseconds) => $query->whereHas('response', fn (Builder $response) => $response->where('duration', '>', $milliseconds / 1000)),
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['slower_than'] ?? null) ? "Slower than {$data['slower_than']}ms" : null),
                TernaryFilter::make('captured')
                    ->label('Payload captured')
                    ->nullable()
                    ->attribute('headers'),
                QueryBuilder::make()
                    ->constraints([
                        DateConstraint::make('created_at')->label('Sent'),
                    ]),
            ])
            ->persistFiltersInSession()
            ->filtersFormWidth('xl')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoRequests::route('/'),
            'view' => ViewAmigoRequest::route('/{record}'),
        ];
    }
}
