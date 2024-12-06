<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoWebhookResource;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

use function __;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $icon = 'far-key';

    protected $listeners = ['copy-token-clipboard' => 'copyTokenClipboard'];

    public function copyTokenClipboard(string $token): void
    {
        $this->js(
            'window.navigator.clipboard.writeText("' . $token . '");
                        $tooltip("' . __('Copied to clipboard') . '", { timeout: 1500 });'
        );
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return number_format($ownerRecord->tokens()->count()) ?: null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->formatStateUsing(function (PersonalAccessToken $record) {
                        return $record->created_at->format('M j, Y H:i:s');
                    })
                    ->placeholder('Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(function (PersonalAccessToken $record) {
                        return $record->created_at->format('M j, Y H:i:s');
                    })
                    ->sortable(),
            ])
            // ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->emptyStateHeading('No Tokens')
            ->emptyStateDescription('No tokens have been created for this listener.')
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('generateToken')
                    ->label('Generate Token')
                    ->icon('far-circle-plus')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->color('success')
                    ->action(function (array $data) {
                        /* @var AmigoListener $record */
                        $listener = $this->ownerRecord;
                        $token = $listener->createToken($data['name']);
                        $plainToken = explode('|', $token->plainTextToken, 2)[1];
                        Notification::make()
                            ->title('Bearer Token Generated')
                            ->icon('far-key')
                            ->body('The token has been generated.')
                            ->success()
                            ->actions([
                                Action::make('Copy')
                                    ->icon('heroicon-s-clipboard-document-check')
                                    ->button()
                                    ->dispatch('copy-token-clipboard', ['token' => $plainToken]),
                            ])
                            ->persistent()
                            ->send();

                        $this->replaceMountedAction('doGenerate', ['token' => $token]);
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ViewAction::make()->url(fn (AmigoWebhook $record) => AmigoWebhookResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function displayTokenValue($token): void
    {
        $this->displayingToken = true;
        $this->plainTextToken = explode('|', $token->plainTextToken, 2)[1];
        $this->dispatchBrowserEvent('showing-token-modal');
    }
}
