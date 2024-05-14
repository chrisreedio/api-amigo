<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Facades\APIAmigo;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Rawilk\FilamentPasswordInput\Password;

use function config;

class AmigoListenerResource extends Resource
{
    protected static ?string $model = AmigoListener::class;

    protected static ?string $navigationIcon = 'far-headphones-simple';

    protected static ?string $modelLabel = 'Listener';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'listeners';

    public static function getNavigationGroup(): ?string
    {
        return config('api-amigo.filament.navigation_group');
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    // public static function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist
    //         ->schema([
    //             Infolists\Components\TextEntry::make('display_name'),
    //
    //             Infolists\Components\TextEntry::make('integration.name')
    //                 ->label('Integration'),
    //         ]);
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('display_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('integration_id')
                    ->relationship('integration', 'name')
                    ->required(),

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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('url_preview')
                    ->label('URL Preview')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->getStateUsing(fn (AmigoListener $record) => $record->url),

                Tables\Columns\TextColumn::make('handler')
                    ->searchable()
                    ->badge()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('created_at')
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable()
                //     ->toggledHiddenByDefault(),
            ])
            ->filters([
                //
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
