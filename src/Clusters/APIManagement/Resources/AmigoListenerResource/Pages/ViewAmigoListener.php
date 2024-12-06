<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource\Pages;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoListenerResource;
use ChrisReedIO\APIAmigo\Models\AmigoListener;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewAmigoListener
 *
 * @property AmigoListener $record
 */
class ViewAmigoListener extends ViewRecord
{
    protected static string $resource = AmigoListenerResource::class;

    // public function getSubheading(): ?string
    // {
    //     return $this->record->url;
    // }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateSignature')
                ->label('Generate Payload Signature')
                ->icon('far-flask-vial')
                ->form([
                    Textarea::make('payload')
                        ->rows(15)
                        ->grow(),
                ])
                ->action(function (AmigoListener $record, array $data) {
                    // $secret = $record->webhook_secret;
                    // $payload = $data['payload'];

                    // dump("secret: '{$record->webhook_secret}'");
                    // dump("payload: '{$data['payload']}'");
                    // dump('payload: ', $data['payload']);

                    $hash = hash_hmac('sha256', $data['payload'], $record->webhook_secret);

                    // dd("hash: '$hash'");

                    Notification::make()
                        ->title('Signature Generated')
                        ->body("<code>$hash</code>")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}
