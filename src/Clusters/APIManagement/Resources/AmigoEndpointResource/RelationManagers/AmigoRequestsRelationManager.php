<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoEndpointResource\RelationManagers;

use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRequestResource;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoResponseResource;
use ChrisReedIO\APIAmigo\Models\AmigoRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AmigoRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    protected static string | \BackedEnum | null $icon = 'far-inbox-out';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Only the response columns the table shows, not its body and headers.
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'response' => fn (HasOne $response) => $response->select(['id', 'request_id', 'status_code', 'duration', 'cached']),
            ]))
            // ->recordTitleAttribute('name')
            ->recordUrl(fn (AmigoRequest $request) => AmigoRequestResource::getUrl('view', ['record' => $request]))
            ->columns([
                // Tables\Columns\TextColumn::make('endpoint.name')
                //     ->label('Endpoint')
                //     ->tooltip(fn (AmigoRequest $record) => $record->endpoint->path)
                //     ->searchable()
                //     ->sortable(),

                TextColumn::make('path')
                    ->label('Path')
                    ->getStateUsing(fn (AmigoRequest $record) => $record->path ?? $record->endpoint->styled_path)
                    ->html()
                    // ->copyable()
                    ->limit(100)
                    ->searchable(),

                TextColumn::make('response.status_code')
                    ->label('Status')
                    ->placeholder('No Response')
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response->status_code->value . ' ' . $record->response->status_code->getLabel())
                    ->badge()
                    ->sortable(),

                TextColumn::make('response.duration')
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

                TextColumn::make('response.cached')
                    ->label('Cached')
                    ->icon('far-database')
                    ->color(fn (AmigoRequest $record) => $record->response?->cached ? Color::Green : Color::Red)
                    ->formatStateUsing(fn (AmigoRequest $record) => $record->response?->cached ? 'Yes' : 'No')
                    ->badge()
                    // ->placeholder('N/A1')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
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
                CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ViewAction::make()
                //     ->url(fn (AmigoRequest $request) => $request->response == null ? null : AmigoResponseResource::getUrl('view', ['record' => $request->response])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
