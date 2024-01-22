<?php

namespace ChrisReedIO\APIAmigo\Resources;

// use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource\RelationManagers;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                //
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
