<?php

namespace App\Filament\Resources;

use App\Models\RekognitionIndexedImage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class RekognitionIndexedImageResource extends Resource
{
    protected static ?string $model = RekognitionIndexedImage::class;

    protected static ?string $modelLabel = 'Imagen Indexada';

    protected static ?string $pluralModelLabel = 'Imágenes Indexadas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'image_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Hidden::make('uuid'),

                TextInput::make('image_name')
                    ->label('Nombre de la Imagen')
                    ->disabled()
                    ->columnSpanFull(),

                TextInput::make('face_id')
                    ->label('ID del Rostro (AWS)')
                    ->disabled()
                    ->columnSpanFull(),

                TextInput::make('confidence')
                    ->label('Confianza del Rostro')
                    ->disabled()
                    ->suffix('%')
                    ->numeric(),

                TextInput::make('indexed_at')
                    ->label('Fecha de Indexación')
                    ->disabled(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->columnSpanFull(),

                Textarea::make('face_details')
                    ->label('Detalles del Rostro')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('image_name')
                    ->label('Imagen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('person.names')
                    ->label('Persona')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('collection.name')
                    ->label('Colección')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('face_id')
                    ->label('ID Rostro')
                    ->limit(20)
                    ->copyable(),

                TextColumn::make('confidence')
                    ->label('Confianza')
                    ->suffix('%')
                    ->alignment('center'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activa'),

                TextColumn::make('indexed_at')
                    ->label('Indexada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}


