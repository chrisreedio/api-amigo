<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use Carbon\CarbonInterface;
use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Facades\APIAmigo;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rawilk\FilamentPasswordInput\Password;

use function class_basename;
use function config;

class AmigoListenerResource extends Resource
{
    protected static ?string $model = AmigoListener::class;

    protected static ?string $navigationIcon = 'far-headphones-simple';

    protected static ?string $modelLabel = 'Listener';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'listeners';

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
            ->schema([
                Infolists\Components\TextEntry::make('display_name'),

                Infolists\Components\TextEntry::make('integration.name')
                    ->label('Integration')
                    ->placeholder('No Integration'),

                Infolists\Components\TextEntry::make('webhook_secret')
                    ->formatStateUsing(fn ($record) => $record->webhook_secret ? 'Yes' : 'No')
                    ->placeholder('Unsecured')
                    ->label('Webhook Secret'),

                Infolists\Components\TextEntry::make('handler')
                    ->badge(),

                Infolists\Components\TextEntry::make('uses')
                    ->badge()
                    ->label('Successful Uses'),

                Infolists\Components\TextEntry::make('max_uses')
                    ->placeholder('Unlimited')
                    ->badge()
                    ->label('Max Uses'),

                Infolists\Components\TextEntry::make('url')
                    // ->formatStateUsing(fn ($record) => $record->url)
                    ->columnSpanFull()
                    ->copyable(),

                // Infolists\Components\TextEntry::make('listenable.name')
                //     ->label('Listenable Type'),

                Infolists\Components\TextEntry::make('listenable_type')
                    ->label('Listenable Type'),

                Infolists\Components\TextEntry::make('listenable_id')
                    ->label('Listenable ID'),

            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('display_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('integration_id')
                    // ->required()
                    ->relationship('integration', 'name'),

                Password::make('webhook_secret')
                    ->label('Webhook Secret')
                    ->columnSpan(2)
                    ->helperText('Leave blank to disable signature verification, not recommended.')
                    ->copyable()
                    ->hidePasswordManagerIcons()
                    // ->regeneratePassword()
                    // ->generatePasswordUsing(fn () => Str::password(symbols: false))
                    ->regeneratePassword(
                        condition: true,
                        color: 'primary',
                        using: fn () => Str::password(symbols: false),
                        notify: true,
                    )
                    ->maxLength(255),

                Forms\Components\Select::make('handler')
                    ->columnSpan(2)
                    ->options(fn () => collect(APIAmigo::getWebhookHandlers())->mapWithKeys(fn ($handler) => [$handler => $handler]))
                    ->required(),

                // Forms\Components\TextInput::make('listenable_type')
                //     ->visibleOn(['view'])
                //     // ->columnSpan(2)
                //     ->readOnly()
                //     // ->default('App\Models\AmigoWebhook')
                //     ->label('Listenable Type'),
                //
                // Forms\Components\Select::make('listenable')
                //     ->relationship('listenable'),

                Forms\Components\TextInput::make('uses')
                    // ->columnSpan(2)
                    ->readOnly()
                    ->hiddenOn(['create', 'edit'])
                    ->numeric()
                    ->label('Uses'),

                Forms\Components\TextInput::make('max_uses')
                    ->label('Max Uses')
                    ->hint('Leave blank for unlimited uses.')
                    ->numeric(),

                // Forms\Components\TextInput::make('url_preview')
                //     ->visibleOn(['view'])
                //     // ->columnSpan(2)
                //     ->readOnly()
                //     ->placeholder(fn (?AmigoListener $record) => $record->url)
                //     // ->default('Generated on save')
                //     ->label('URL Preview'),

                // Forms\Components\TextInput::make('url')
                //     // ->default(fn (AmigoListener $record) => $record->url)
                //     ->placeholder(fn (?AmigoListener $record) => 'default')
                //     ->readOnly()
                //     // ->hintAction(
                //     //     Forms\Components\Actions\Action::make('View Handlers')
                //     //         // ->icon('heroicon-s-clipboard')
                //     //         ->action(function ($livewire, $record) {
                //     //             $livewire->js(
                //     //                 'window.navigator.clipboard.writeText("' . $record->url . '");
                //     //             $tooltip("Copied to clipboard!", { timeout: 1500 });'
                //     //             );
                //     //         })
                //     // )
                //     ->columnSpan(2)
                //     ->default('Generated on save')
                //     ->label('Webhook URL'),

                // Forms\Components\ColorPicker::make('color')
                //     ->columnSpan(2)
                //     ->helperText('Leave blank to use the integration color.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\ColorColumn::make('color')
                //     ->label(''),

                Tables\Columns\TextColumn::make('display_name')
                    ->searchable()
                    // ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('integration.name')
                    ->label('Integration')
                    ->placeholder('No Integration')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('url_preview')
                //     ->label('URL Preview')
                //     ->searchable()
                //     ->sortable()
                //     ->copyable()
                //     ->getStateUsing(fn (AmigoListener $record) => $record->url),

                Tables\Columns\TextColumn::make('handler')
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (AmigoListener $record) => class_basename($record->handler))
                    ->tooltip(fn (AmigoListener $record) => $record->handler)
                    ->sortable(),

                Tables\Columns\TextColumn::make('uses')
                    ->badge()
                    ->color(function (AmigoListener $record) {
                        if ($record->max_uses && $record->uses >= $record->max_uses) {
                            return Color::Red;
                        }

                        if ($record->uses > 0) {
                            return Color::Green;
                        }

                        return Color::Gray;
                    })
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_uses')
                    ->badge()
                    ->numeric()
                    ->placeholder('Unlimited')
                    ->sortable(),

                Tables\Columns\TextColumn::make('linked_model')
                    ->label('Linked Model')
                    ->getStateUsing(fn (AmigoListener $record) => $record->listenable_type ? 'Yes' : 'No')
                    ->color(fn (AmigoListener $record) => $record->listenable_type ? 'success' : 'danger')
                    ->badge()
                    ->placeholder('Not Linked')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires in')
                    ->dateTime()
                    ->placeholder('Never')
                    ->tooltip(fn (AmigoListener $record) => $record->expires_at ? 'Expires at ' . $record->expires_at->format('F j, Y g:i A') : null)
                    ->formatStateUsing(function (AmigoListener $record) {
                        if ($record->expires_at->isPast()) {
                            return 'Expired';
                        }

                        return $record->expires_at->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE);
                        // return $record->expires_at->diffForHumans(now(), CarbonInterface::DIFF_RELATIVE_TO_NOW);
                        // print this in a very readable format like Month Day, Year Hour:Minute AM/PM
                        // return $record->expires_at->format('F j, Y g:i A');

                        // return $record->expires_at ? $record->expires_at->diffForHumans() : null;
                    })
                    ->sortable(),

                // Tables\Columns\TextColumn::make('created_at')
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable()
                //     ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('isTransient')
                    ->label('Transient')
                    ->nullable()
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->transient(),
                        false: fn (Builder $query) => $query->transient(false),
                        blank: fn (Builder $query) => $query // In this example, we do not want to filter the query when it is blank.
                    ),
                Tables\Filters\TernaryFilter::make('linked_model')
                    ->label('Linked Model')
                    ->nullable()
                    // ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('listenable_type'),
                        false: fn (Builder $query) => $query->whereNull('listenable_type'),
                        blank: fn (Builder $query) => $query // In this example, we do not want to filter the query when it is blank.
                    ),

                Tables\Filters\TernaryFilter::make('expires')
                    ->label('Expires')
                    ->nullable()
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('expires_at'),
                        false: fn (Builder $query) => $query->whereNull('expires_at'),
                        blank: fn (Builder $query) => $query // In this example, we do not want to filter the query when it is blank.
                    ),

                Tables\Filters\TernaryFilter::make('expired')
                    ->label('Expired')
                    ->nullable()
                    // visible only if the tableFilters[expires] is true
                    // ->visible(function (Request $request) {
                    //     dd($request->input('tableFilters.expires'));
                    //     return $request->input('tableFilters.expires') === 'true';
                    // })
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->expired(),
                        false: fn (Builder $query) => $query->expired(false),
                        blank: fn (Builder $query) => $query // In this example, we do not want to filter the query when it is blank.
                    ),
            ])
            ->actions([
                // Tables\Actions\Action::make('test')
                //     ->label('Test')
                //     ->icon('far-play')
                //     ->color('primary')
                //     ->action(function ($livewire, $record) {
                //         $data = [
                //             'my_key' => 'my_value',
                //         ];
                //         $request = SimpleWebhook::make($record->url, $data, $record->webhook_secret);
                //         // This is needed to allow the local, non-verified SSL cert to work
                //         if (App::environment('local')) {
                //             $request->config()->add('verify', false);
                //         }
                //
                //         $response = $request->send();
                //         dd($response->json());
                //
                //     }),
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
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\RelationManagers\AmigoWebhooksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\ListAmigoListeners::route('/'),
            // 'create' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\CreateAmigoListener::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\ViewAmigoListener::route('/{record}'),
            // 'edit' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages\EditAmigoListener::route('/{record}/edit'),
        ];
    }
}
