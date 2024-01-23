<?php

namespace ChrisReedIO\APIAmigo\Resources;

use const JSON_PRETTY_PRINT;

use ChrisReedIO\APIAmigo\Filament\Infolists\Components\ArrayEntry;
use ChrisReedIO\APIAmigo\Filament\Infolists\Components\ResponseBodyViewer;
use ChrisReedIO\APIAmigo\Models\AmigoResponse;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
// use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource\RelationManagers;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Spatie\ShikiPhp\Shiki;

use function collect;

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

                Infolists\Components\TextEntry::make('status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (AmigoResponse $record) => $record->status_code->value . ' ' . $record->status_code->getLabel())
                    ->badge(),

                Infolists\Components\TextEntry::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn (AmigoResponse $record) => ($record->duration * 1000) . 'ms')
                    // ->suffix('s')
                    ->icon('far-stopwatch')
                    ->badge(),

                Infolists\Components\TextEntry::make('request.path')
                    ->label('Request Path')
                    ->copyable()
                    // ->url(fn (AmigoResponse $record) => AmigoRequestResource::getUrl('view', ['record' => $record->request]))
                    ->formatStateUsing(function ($state) {
                        // $replacedVars = preg_replace('/\{.*?\}/', '<code style="color:#ea580c;">$0</code>', $state);
                        $formatted = '<code>' . $state . '</code>';

                        return new HtmlString($formatted);
                    })
                    ->icon('far-sign-post'),

                // Infolists\Components\TextEntry::make('body')
                //     ->columnSpanFull()
                //     ->getStateUsing(fn(AmigoResponse $record) => json_encode($record->body, JSON_PRETTY_PRINT))
                //     ->formatStateUsing(function ($state) {
                //         $prettyData = collect($state)
                //             ->mapWithKeys(function ($value, $key) {
                //                 return [
                //                     $key => json_encode($value, JSON_PRETTY_PRINT),
                //                     // $key => json_encode($value),
                //                 ];
                //             })->toArray();
                //         return new HtmlString('<pre>' . json_encode($prettyData, JSON_PRETTY_PRINT) . '</pre>');
                //     })
                //     ->html()
                //     ->grow(),

                // ResponseBodyViewer::make('body')
                //     ->columnSpanFull()
                //     ->label('Response Body Contents')
                //     ->getStateUsing(function (AmigoResponse $record) {
                //         // return json_encode(json_decode($record->body, true), JSON_PRETTY_PRINT);
                //         $responseBody = json_encode(json_decode($record->body, true), JSON_PRETTY_PRINT);
                //         return Shiki::highlight(
                //             code: $record->body,
                //             language: 'json',
                //             theme: 'github-light',
                //             // theme: 'github-dark',
                //         );
                //     }),

                Infolists\Components\TextEntry::make('body')
                    ->columnSpanFull()
                    ->label('Response Body Contents')
                    ->getStateUsing(function (AmigoResponse $record) {
                        if (is_array($record->body)) {
                            return json_encode($record->body, JSON_PRETTY_PRINT);
                        }

                        return json_encode(json_decode($record->body, true), JSON_PRETTY_PRINT);
                    })
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'No Response Body';
                        }

                        // return '```json \n' . $state . '\n```';
                        return new HtmlString('<pre>' . $state . '</pre>');
                    })
                    // ->html(),
                    ->copyable()
                    ->maxWidth('2xl')
                    ->markdown()
                    ->grow(),
                // Infolists\Components\TextEntry::make('body')
                //     ->columnSpanFull()
                //     ->label('Response Body Contents')
                //     ->getStateUsing(function (AmigoResponse $record) {
                //         return json_encode(json_decode($record->body, true), JSON_PRETTY_PRINT);
                //     })
                //     ->formatStateUsing(function ($state) {
                //         return '```json \n' . $state . '\n```';
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
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->readOnly()
                    ->maxLength(255),

                Forms\Components\TextInput::make('display_name')
                    // ->required()
                    ->maxLength(255),

                // Forms\Components\Textarea::make('body')
                //     ->readOnly()
                //     ->formatStateUsing(function ($state) {
                //         $prettyData = collect($state)
                //             ->mapWithKeys(function ($value, $key) {
                //                 return [
                //                     $key => json_encode($value, JSON_PRETTY_PRINT),
                //                     // $key => json_encode($value),
                //                 ];
                //             })->toArray();
                //         return new HtmlString('<pre>' . json_encode($prettyData, JSON_PRETTY_PRINT) . '</pre>');
                //     })
                //     ->grow(),

                // Forms\Components\KeyValue::make('body')
                //     ->columnSpanFull()
                //     ->formatStateUsing(function ($state) {
                //         // dd($state);
                //         return collect($state)->mapWithKeys(function ($value, $key) {
                //             return [
                //                 $key => json_encode($value, JSON_PRETTY_PRINT),
                //             ];
                //         })->toArray();
                //
                //         return [
                //             'key' => json_encode([
                //                 'value' => 'more data',
                //                 'placeholder' => 'Key',
                //             ]),
                //         ];
                //         // $replacedVars = preg_replace('/\{.*?\}/', '<code style="color:#ea580c;">$0</code>', $state);
                //         //
                //         // return new HtmlString("$replacedVars");
                //     }),
                // ->required()
                // ->readOnly()
                // ->maxLength(255),
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
            ])
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
