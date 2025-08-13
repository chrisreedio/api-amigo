<?php

namespace ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources;

use ChrisReedIO\APIAmigo\Clusters\APIManagement;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages\ListAmigoRecordings;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\Pages\ViewAmigoRecording;
use ChrisReedIO\APIAmigo\Clusters\APIManagement\Resources\AmigoRecordingResource\RelationManagers\AmigoRequestsRelationManager;
use ChrisReedIO\APIAmigo\Models\AmigoRecording;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

use function config;

class AmigoRecordingResource extends Resource
{
    protected static ?string $model = AmigoRecording::class;

    protected static string | \BackedEnum | null $navigationIcon = 'far-cassette-tape';

    protected static ?string $modelLabel = 'Recording';

    protected static ?string $cluster = APIManagement::class;

    protected static ?string $slug = 'recordings';

    // public static function getNavigationGroup(): ?string
    // {
    //     return config('api-amigo.filament.navigation_group');
    // }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->schema([
                TextEntry::make('name'),

                TextEntry::make('connector.name')
                    ->placeholder('All Connectors')
                    ->label('Connector'),

                TextEntry::make('started_at')
                    ->placeholder('Not Started')
                    ->icon(fn (AmigoRecording $recording) => $recording->started_at ? 'far-play' : null)
                    ->iconColor(fn (AmigoRecording $recording) => $recording->started_at ? Color::Green : null)
                    ->dateTime()
                    ->label('Started At'),

                TextEntry::make('ended_at')
                    ->placeholder('Not Ended')
                    ->icon(fn (AmigoRecording $recording) => $recording->ended_at ? 'far-stop' : null)
                    ->iconColor(fn (AmigoRecording $recording) => $recording->ended_at ? Color::Red : null)
                    ->dateTime()
                    ->label('Ended At'),

                IconEntry::make('global')
                    ->boolean()
                    ->columnSpan(2)
                    // ->columnSpan([
                    //     'sm' => 2,
                    //     '2xl' => 1,
                    // ])
                    // ->hint('Includes system requests'),
                    ->helperText('Includes system requests'),

                IconEntry::make('capture_body')
                    ->boolean()
                    ->label('Capture Response Body')
                    // ->hint('Could include sensitive data!')
                    ->columnSpan(2)
                    // ->columnSpan([
                    //     'sm' => 2,
                    //     '2xl' => 1,
                    // ])
                    // ->hintColor(Color::Yellow),
                    ->helperText('Could include sensitive data!'),

                TextEntry::make('description')
                    ->hidden(fn (AmigoRecording $recording) => ! $recording->description)
                    ->placeholder('No Description')
                    ->columnSpanFull(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->required(),
                TextInput::make('name')
                    ->helperText('A unique name for this recording.')
                    ->maxLength(255),

                Select::make('connector_id')
                    ->placeholder('Record all connectors')
                    ->helperText('Capture requests from a specific connector.')
                    ->relationship('connector', 'name'),

                Textarea::make('description')
                    ->columnSpanFull(),

                // Forms\Components\DateTimePicker::make('started_at'),
                // Forms\Components\DateTimePicker::make('ended_at'),

                Toggle::make('global')
                    // ->columnSpan(2)
                    // ->hint('Includes system requests')
                    ->helperText('Capture requests from all users including the system.'),

                Toggle::make('capture_body')
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
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('connector.name')
                    ->placeholder('All Connectors')
                    ->sortable(),
                TextColumn::make('name')
                    ->placeholder('No Name')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->placeholder('Not Started')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->placeholder('Not Ended')
                    ->sortable(),
                IconColumn::make('global')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->placeholder('All Users'),
                SelectFilter::make('connector_id')
                    ->relationship('connector', 'name')
                    ->placeholder('All Connectors'),
                QueryBuilder::make()
                    ->constraints([
                        DateConstraint::make('created_at'),
                    ]),

            ])
            ->recordActions([
                Action::make('start')
                    ->label('Start')
                    ->icon('far-play')
                    ->color(Color::Green)
                    ->hidden(fn (AmigoRecording $recording) => $recording->started_at || $recording->ended_at)
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to start this recording?')
                    ->action(fn (AmigoRecording $recording) => $recording->start()),
                Action::make('stop')
                    ->label('Stop')
                    ->icon('far-stop')
                    ->color(Color::Red)
                    ->hidden(fn (AmigoRecording $recording) => ! $recording->started_at || $recording->ended_at)
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to stop this recording?')
                    ->action(fn (AmigoRecording $recording) => $recording->stop()),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AmigoRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmigoRecordings::route('/'),
            // 'create' => Pages\CreateAmigoRecording::route('/create'),
            'view' => ViewAmigoRecording::route('/{record}'),
            // 'edit' => Pages\EditAmigoRecording::route('/{record}/edit'),
        ];
    }
}
