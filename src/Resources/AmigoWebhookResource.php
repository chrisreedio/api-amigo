<?php

namespace ChrisReedIO\APIAmigo\Resources;

// use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\RelationManagers;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class AmigoWebhookResource extends Resource
{
    protected static ?string $model = AmigoWebhook::class;

    protected static ?string $navigationIcon = 'far-webhook';

    protected static ?string $modelLabel = 'Webhook';

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
            'index' => \ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages\ListAmigoWebhooks::route('/'),
            'create' => \ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages\CreateAmigoWebhook::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages\ViewAmigoWebhook::route('/{record}'),
            'edit' => \ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\Pages\EditAmigoWebhook::route('/{record}/edit'),
        ];
    }
}
