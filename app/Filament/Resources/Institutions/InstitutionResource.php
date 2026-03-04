<?php

namespace App\Filament\Resources\Institutions;

use App\Filament\Resources\Institutions\Pages\ManageInstitutions;
use App\Models\Institution;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;
    protected static ?string $modelLabel = 'Institución';
    protected static ?string $pluralModelLabel = 'Instituciones';


    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la Institución')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $slug = \Str::slug($state);
                        $set('slug', $slug);
                        $set('filepath', $slug);
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('URL Amigable (Slug)')
                    ->disabled()
                    ->dehydrated(true)
                    ->helperText('Se genera automáticamente desde el nombre')
                    ->columnSpanFull(),

                TextInput::make('filepath')
                    ->label('Ruta de la Carpeta')
                    ->helperText('Ruta donde se guardarán las fotos (se creará en storage/app/public/)')
                    ->placeholder('ej: universidad-central')
                    ->required()
                    ->columnSpanFull(),

                Select::make('rekognition_collection_id')
                    ->label('Colección de Rekognition')
                    ->options(function () {
                        return \App\Models\RekognitionCollection::pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->inline(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rekognitionCollection.name')
                    ->label('Colección Rekognition')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filepath')
                    ->label('Carpeta')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Directorio: storage/app/public/'),
                TextColumn::make('photo_count')
                    ->label('📸 Fotos')
                    ->state(function (Institution $record): string {
                        if (empty($record->filepath)) {
                            return '0';
                        }

                        $basePath = storage_path('app/public/' . $record->filepath);

                        if (!is_dir($basePath)) {
                            return '0';
                        }

                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $files = scandir($basePath);
                        $photoCount = 0;

                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..') continue;

                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (in_array($extension, $imageExtensions)) {
                                $photoCount++;
                            }
                        }

                        return (string) $photoCount;
                    })
                    ->alignment('center')
                    ->sortable(false),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('index-photos')
                    ->label('📸 Indexar Fotos')
                    ->icon('heroicon-m-photo')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Indexar fotos de la institución')
                    ->modalDescription('Se indexarán todas las fotos de la carpeta: ' . fn(Institution $record) => $record->filepath)
                    ->modalSubmitActionLabel('Sí, indexar')
                    ->action(function (Institution $record) {
                        self::indexInstitutionPhotos($record);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstitutions::route('/'),
        ];
    }
}
