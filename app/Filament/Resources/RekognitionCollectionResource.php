<?php

namespace App\Filament\Resources;

use App\Models\RekognitionCollection;
use App\Filament\Resources\Pages\ListRekognitionCollections;
use App\Filament\Resources\Pages\ViewRekognitionCollection;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RekognitionCollectionResource extends Resource
{
    protected static ?string $model = RekognitionCollection::class;

    protected static ?string $modelLabel = 'Colección Rekognition';

    protected static ?string $pluralModelLabel = 'Colecciones Rekognition';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'collection_id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('collection_id')
                    ->label('ID de Colección')
                    ->required()
                    ->disabled()
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->disabled()
                    ->columnSpanFull(),

                TextInput::make('faces_count')
                    ->label('Rostros Indexados')
                    ->numeric()
                    ->disabled(),

                TextInput::make('face_model_version')
                    ->label('Versión del Modelo')
                    ->disabled(),

                Toggle::make('is_active')
                    ->label('Activa'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('collection_id')
                    ->label('ID de Colección')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-folder'),

                TextColumn::make('faces_count')
                    ->label('Rostros Indexados')
                    ->sortable()
                    ->badge()
                    ->color(function ($state) {
                        return match (true) {
                            $state === 0 => 'gray',
                            $state < 10 => 'info',
                            $state < 50 => 'warning',
                            default => 'success'
                        };
                    }),

                TextColumn::make('face_model_version')
                    ->label('Versión del Modelo')
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activa'),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
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
            \App\Filament\Resources\RekognitionCollections\Pages\IndexedImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRekognitionCollections::route('/'),
            'view' => ViewRekognitionCollection::route('/{record}'),
        ];
    }
}

