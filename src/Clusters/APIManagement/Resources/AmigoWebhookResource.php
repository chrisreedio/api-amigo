<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

// use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\RelationManagers;
use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Parallax\FilamentSyntaxEntry\SyntaxEntry;

class AmigoWebhookResource extends Resource
{
    protected static ?string $model = AmigoWebhook::class;

    protected static ?string $navigationIcon = 'far-webhook';

    protected static ?string $modelLabel = 'Webhook';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'webhooks';

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
                Infolists\Components\TextEntry::make('listener.integration.name')
                    ->label('Integration')
                    ->url(function (AmigoWebhook $record) {
                        return $record->listener?->integration ? AmigoIntegrationResource::getUrl('view', ['record' => $record->listener->integration]) : null;
                    })
                    ->placeholder('No Integration')
                    ->icon('far-integral'),

                Infolists\Components\TextEntry::make('listener.display_name')
                    ->label('Listener')
                    ->url(fn (AmigoWebhook $record) => AmigoListenerResource::getUrl('view', ['record' => $record->listener]))
                    ->icon('far-headphones-simple'),

                Infolists\Components\TextEntry::make('error.message')
                    ->label('Processing Error Message')
                    ->icon(fn (AmigoWebhook $record) => $record->error !== null ? 'far-triangle-exclamation' : 'far-face-cowboy-hat')
                    ->iconColor(fn (AmigoWebhook $record) => $record->error ? Color::Rose : Color::Green)
                    ->words(5)
                    ->copyable(fn (AmigoWebhook $record) => $record->error !== null)
                    ->default('Processed Successfully. No Errors reported.')
                    ->color(fn (AmigoWebhook $record) => $record->error ? Color::Rose : Color::Green),

                Infolists\Components\TextEntry::make('listener.url')
                    ->label('Listener Path')
                    ->copyable()
                    ->columnSpan(2)
                    ->formatStateUsing(fn ($state) => new HtmlString('<code>'.$state.'</code>'))
                    ->icon('far-sign-post'),

                Infolists\Components\TextEntry::make('listener.handler')
                    ->label('Handler')
                    ->badge()
                    ->icon('far-code'),

                Infolists\Components\TextEntry::make('created_at')
                    ->label('Received At')
                    ->formatStateUsing(fn (AmigoWebhook $record) => Carbon::make($record->created_at)->format('M j, Y H:i:s'))
                    ->icon('far-calendar'),

                Infolists\Components\TextEntry::make('processed_at')
                    ->label('Processed At')
                    ->formatStateUsing(fn (AmigoWebhook $record) => $record->processed_at ? Carbon::make($record->processed_at)->format('M j, Y H:i:s') : 'Not Processed')
                    ->icon('far-calendar'),

                Infolists\Components\TextEntry::make('processing_time')
                    ->label('Processing Duration')
                    // ->getStateUsing(function (AmigoWebhook $record) {
                    //     if (!$record->processed_at) {
                    //         return 'Not Processed';
                    //     }
                    //     return $record->processed_at ? ($record->created_at->diffInSeconds($record->processed_at)) . ' seconds' : 'Not Processed');
                    //     })
                    ->getStateUsing(function (AmigoWebhook $record) {
                        return match ($record->processing_time) {
                            null => 'Not Processed',
                            0 => 'Instant',
                            default => $record->processing_time.'s',
                        };
                    })
                    ->color(fn (AmigoWebhook $record) => match ($record->processing_time) {
                        null => 'gray',
                        0 => 'warning',
                        default => 'primary',
                    })
                    ->icon(fn (AmigoWebhook $record) => match ($record->processing_time) {
                        null => 'far-hourglass-start',
                        0 => 'far-bolt-lightning',
                        default => 'far-stopwatch',
                    })
                    ->badge(),

                SyntaxEntry::make('headers')
                    ->label('Webhook Headers')
                    ->columnSpanFull(),
                // ->getStateUsing(function (AmigoWebhook $record) {
                //     if (is_array($record->headers)) {
                //         return json_encode($record->headers, JSON_PRETTY_PRINT);
                //     }
                //
                //     return json_encode(json_decode($record->headers, true), JSON_PRETTY_PRINT);
                // }),
                // Disabling this for now as it's misbehaving
                // ->getStateUsing(fn (AmigoWebhook $record) => json_encode($record->headers, JSON_PRETTY_PRINT)),

                // Infolists\Components\TextEntry::make('headers')
                //     ->columnSpanFull()
                //     ->label('Webhook Headers')
                //     ->getStateUsing(function (AmigoWebhook $record) {
                //         if (is_array($record->headers)) {
                //             return json_encode($record->headers, JSON_PRETTY_PRINT);
                //         }
                //
                //         return json_encode(json_decode($record->headers, true), JSON_PRETTY_PRINT);
                //     })
                //     ->formatStateUsing(function ($state) {
                //         if (empty($state)) {
                //             return 'No Headers';
                //         }
                //
                //         // return '```json' . $state . '';
                //         return new HtmlString('<pre>' . $state . '</pre>');
                //     })
                //     // ->html(),
                //     ->copyable()
                //     ->maxWidth('2xl')
                //     ->markdown()
                //     ->grow(),

                SyntaxEntry::make('payload')
                    ->label('Webhook Payload')
                    ->columnSpanFull(),
                // ->getStateUsing(fn (AmigoWebhook $record) => json_encode($record->payload, JSON_PRETTY_PRINT)),

                // Infolists\Components\TextEntry::make('payload')
                //     ->columnSpanFull()
                //     ->label('Webhook Payload')
                //     ->getStateUsing(function (AmigoWebhook $record) {
                //         if (is_array($record->payload)) {
                //             return json_encode($record->payload, JSON_PRETTY_PRINT);
                //         }
                //
                //         return json_encode(json_decode($record->payload, true), JSON_PRETTY_PRINT);
                //     })
                //     ->formatStateUsing(function ($state) {
                //         if (empty($state)) {
                //             return 'No Payload';
                //         }
                //
                //         // return '```json \n' . $state . '\n```';
                //         return new HtmlString('<pre>' . $state . '</pre>');
                //     })
                //     // ->html(),
                //     ->copyable()
                //     ->maxWidth('2xl')
                //     ->markdown()
                //     ->grow(),

                // ArrayEntry::make('body')
                //     ->label('Body Contents')
                //
                //     ->columnSpanFull(),

                // Infolists\Components\KeyValueEntry::make('body')
                //     ->label('Connector'),
                // ->icon('far-outlet')
                // ->formatStateUsing(function ($state) {
                //     return collect($state)->mapWithKeys(function ($value, $key) {
                //         return [
                //             $key => json_encode($value, JSON_PRETTY_PRINT),
                //         ];
                //     })->toArray();
                // }),
                // ->url(fn (AmigoResponse $record) => $record->endpoint->connector->base_url),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('listener.display_name')
                    ->label('Listener')
                    ->url(fn (AmigoWebhook $record) => $record->listener ? AmigoListenerResource::getUrl('view', ['record' => $record->listener]) : null)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->icon(fn (AmigoWebhook $record) => $record->processed_at ? 'far-circle-check' : 'far-hourglass-start')
                    ->default('Not Processed')
                    ->getStateUsing(fn (AmigoWebhook $record) => $record->processed_at ? Carbon::make($record->processed_at)->format('M j, Y H:i:s') : 'Not Processed')
                    ->color(fn (AmigoWebhook $record) => $record->processed_at ? 'success' : 'warning')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('error.message')
                    ->label('Result / Error Message')
                    // ->icon(fn (AmigoWebhook $record) => $record->error !== null ? 'far-triangle-exclamation' : null)
                    ->words(5)
                    ->copyable()
                    ->default('Success')
                    ->color(fn (AmigoWebhook $record) => $record->error ? Color::Rose : Color::Green),
                // ->tooltip(fn (AmigoWebhook $record) => $record->error ? new HtmlString('<code>' . $record->error['message'] . '</code>') : null),

                Tables\Columns\TextColumn::make('sender')
                    ->label('Sender')
                    // ->getStateUsing(fn (AmigoWebhook $record) => $record->sender)
                    // ->html()
                    ->badge()
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Webhooks')
            ->emptyStateDescription('Setup a listener to start receiving webhooks.')
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
            'index' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages\ListAmigoWebhooks::route('/'),
            'create' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages\CreateAmigoWebhook::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages\ViewAmigoWebhook::route('/{record}'),
            'edit' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource\Pages\EditAmigoWebhook::route('/{record}/edit'),
        ];
    }
}
