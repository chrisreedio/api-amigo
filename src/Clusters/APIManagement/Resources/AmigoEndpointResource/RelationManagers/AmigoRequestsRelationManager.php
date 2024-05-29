<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    protected static ?string $icon = 'far-inbox-out';

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
            ->recordUrl(fn (AmigoRequest $request) => $request->response == null ? null : AmigoResponseResource::getUrl('view', ['record' => $request->response]))
            ->columns([
                // Tables\Columns\TextColumn::make('endpoint.name')
                //     ->label('Endpoint')
                //     ->tooltip(fn (AmigoRequest $record) => $record->endpoint->path)
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->getStateUsing(fn (AmigoRequest $record) => $record->path ?? $record->endpoint->styled_path)
                    ->html()
                    // ->copyable()
                    ->limit(100)
                    ->searchable(),

                Tables\Columns\TextColumn::make('response.status_code')
                    ->label('Status')
                    ->placeholder('No Response')
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('response.duration')
                    ->label('Duration')
                    ->badge()
                    ->getStateUsing(fn (AmigoRequest $record) => $record->response ? ($record->response->duration * 1000) . 'ms' : null)
                    ->placeholder('No Response')
                    // ->suffix('s')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('requests_count')
                //     ->label('Requests')
                //     ->badge()
                //     ->counts('requests')
                //     ->sortable(),

                // Tables\Columns\IconColumn::make('cached')
                //     ->label('Cached')
                //     ->boolean()
                //     ->placeholder('N/A')
                //     ->getStateUsing(fn (AmigoRequest $record) => $record->response?->cached)
                //     ->alignCenter()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('response.cached')
                    ->label('Cached')
                    ->icon('far-database')
                    ->color(fn (AmigoRequest $record) => $record->response?->cached ? Color::Green : Color::Red)
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response?->cached ? 'Yes' : 'No')
                    ->badge()
                    // ->placeholder('N/A1')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ViewAction::make()
                //     ->url(fn (AmigoRequest $request) => $request->response == null ? null : AmigoResponseResource::getUrl('view', ['record' => $request->response])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
