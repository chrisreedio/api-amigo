<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoListenerResource\RelationManagers;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Models\AmigoWebhook;
use ChrisReedIO\APIAmigo\Resources\AmigoWebhookResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class AmigoWebhooksRelationManager extends RelationManager
{
    protected static string $relationship = 'webhooks';

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

                // Tables\Columns\IconColumn::make('error.message')
                //     ->label('Error')
                //     ->icon(fn (AmigoWebhook $record) => $record->error !== null ? 'far-triangle-exclamation' : null)
                //     ->color(fn (AmigoWebhook $record) => $record->error ? 'danger' : null)
                //     ->tooltip(fn (AmigoWebhook $record) => $record->error['message'] ?? null),

                // ->tooltip(fn (AmigoWebhook $record) => $record->error ? new HtmlString('<code>' . $record->error['message'] . '</code>') : null),

                Tables\Columns\TextColumn::make('sender')
                    ->label('Sender')
                    // ->getStateUsing(fn (AmigoWebhook $record) => $record->sender)
                    // ->html()
                    ->badge()
                    ->copyable()
                    ->searchable(),

                // Tables\Columns\TextColumn::make('endpoint.name')
                //     ->label('Endpoint')
                //     ->tooltip(fn (AmigoRequest $record) => $record->endpoint->path)
                //     ->searchable()
                //     ->sortable(),
                //
                // Tables\Columns\TextColumn::make('path')
                //     ->label('Path')
                //     ->getStateUsing(fn (AmigoRequest $record) => $record->path ?? $record->endpoint->styled_path)
                //     ->html()
                //     ->copyable()
                //     ->searchable(),
                //
                // Tables\Columns\TextColumn::make('response.status_code')
                //     ->label('Status')
                //     ->placeholder('No Response')
                //     ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                //     ->badge()
                //     ->sortable(),
                //
                // Tables\Columns\TextColumn::make('response.duration')
                //     ->label('Duration')
                //     ->badge()
                //
                //     ->getStateUsing(fn (AmigoRequest $record) => $record->response ? ($record->response->duration * 1000) . 'ms' : null)
                //     ->placeholder('No Response')
                //     // ->suffix('s')
                //     ->sortable(),
                //
                // // Tables\Columns\TextColumn::make('requests_count')
                // //     ->label('Requests')
                // //     ->badge()
                // //     ->counts('requests')
                // //     ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->emptyStateHeading('No Webhooks')
            ->emptyStateDescription('No webhooks have been sent yet to this listener.')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()->url(fn (AmigoWebhook $record) => AmigoWebhookResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
