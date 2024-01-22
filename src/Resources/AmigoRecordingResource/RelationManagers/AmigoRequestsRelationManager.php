<?php

namespace ChrisReedIO\APIAmigo\Resources\AmigoRecordingResource\RelationManagers;

use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use ChrisReedIO\APIAmigo\Resources\AmigoResponseResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('endpoint.name')
                    ->label('Endpoint')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('endpoint.connector.name')
                    ->label('Connector'),
                Tables\Columns\TextColumn::make('endpoint.name')
                    ->label('Endpoint'),
                Tables\Columns\TextColumn::make('endpoint.styled_path')
                    ->label('Endpoint')
                    ->html()
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('response.duration')
                    ->label('Duration')
                    ->badge()
                    ->placeholder('No Response')
                    ->getStateUsing(fn (AmigoRequest $record) => $record->response !== null ? ($record->response->duration * 1000) . 'ms' : null)
                    // ->suffix('s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('connector_id')
                    ->relationship('endpoint.connector', 'name'),
                Tables\Filters\SelectFilter::make('endpoint_id')
                    ->relationship('endpoint', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->url(fn (AmigoRequest $record) => $record->response ? AmigoResponseResource::getUrl('view', ['record' => $record->response]) : null),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
