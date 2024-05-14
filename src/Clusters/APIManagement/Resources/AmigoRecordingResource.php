<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

use function config;

class AmigoRecordingResource extends Resource
{
    protected static ?string $model = AmigoRecording::class;

    protected static ?string $navigationIcon = 'far-cassette-tape';

    protected static ?string $modelLabel = 'Recording';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'recordings';

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
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->required(),
                Forms\Components\TextInput::make('name')
                    ->helperText('A unique name for this recording.')
                    ->maxLength(255),

                Forms\Components\Select::make('connector_id')
                    ->placeholder('Record all connectors')
                    ->helperText('Capture requests from a specific connector.')
                    ->relationship('connector', 'name'),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                // Forms\Components\DateTimePicker::make('started_at'),
                // Forms\Components\DateTimePicker::make('ended_at'),

                Forms\Components\Toggle::make('global')
                    // ->columnSpan(2)
                    // ->hint('Includes system requests')
                    ->helperText('Capture requests from all users including the system.'),

                Forms\Components\Toggle::make('capture_body')
                    ->label('Capture Response Body')
                    // ->columnSpan(2)
                    ->hintColor(Color::Rose)
                    ->hint('May log sensitive data!')
                    ->helperText('Will log the response body, could be a lot of data.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('connector.name')
                    ->placeholder('All Connectors')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->placeholder('No Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->placeholder('Not Started')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->placeholder('Not Ended')
                    ->sortable(),
                Tables\Columns\IconColumn::make('global')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->placeholder('All Users'),
                Tables\Filters\SelectFilter::make('connector_id')
                    ->relationship('connector', 'name')
                    ->placeholder('All Connectors'),
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at'),
                    ]),

            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label('Start')
                    ->icon('far-play')
                    ->color(Color::Green)
                    ->hidden(fn (AmigoRecording $recording) => $recording->started_at || $recording->ended_at)
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to start this recording?')
                    ->action(fn (AmigoRecording $recording) => $recording->start()),
                Tables\Actions\Action::make('stop')
                    ->label('Stop')
                    ->icon('far-stop')
                    ->color(Color::Red)
                    ->hidden(fn (AmigoRecording $recording) => ! $recording->started_at || $recording->ended_at)
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to stop this recording?')
                    ->action(fn (AmigoRecording $recording) => $recording->stop()),
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
            \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers\AmigoRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages\ListAmigoRecordings::route('/'),
            // 'create' => Pages\CreateAmigoRecording::route('/create'),
            'view' => \ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages\ViewAmigoRecording::route('/{record}'),
            // 'edit' => Pages\EditAmigoRecording::route('/{record}/edit'),
        ];
    }
}
