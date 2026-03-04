<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Imports\PeopleImport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class PeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'people';
    protected static ?string $recordTitleAttribute = 'names';
    protected static ?string $title = 'Postulantes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Form fields if needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('names')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Número de Documento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('photo_path')
                    ->label('Ruta de Foto')
                    ->limit(50)
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('download_template')
                    ->label('Descargar Template')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->url('/plantilla_postulantes.xlsx')
                    ->openUrlInNewTab(),
                Action::make('import_people')
                    ->label('Importar Postulantes')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->color('success')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo Excel (.xlsx, .csv)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->helperText('Máximo 10MB. Formatos permitidos: .xlsx, .csv'),
                    ])
                    ->action(function (array $data) {
                        $this->importPeople($data['file']);
                    }),
            ]);
    }

    protected function importPeople(string $filePath): void
    {
        try {
            $import = new PeopleImport($this->ownerRecord->id);
            Excel::import($import, storage_path('app/private/' . $filePath));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            if ($imported > 0) {
                Notification::make()
                    ->title('✅ Importación Completada')
                    ->body("Se importaron $imported postulantes. Se omitieron $skipped registros.")
                    ->success()
                    ->send();
            }

            if (!empty($errors)) {
                Notification::make()
                    ->title('⚠️ Errores en la Importación')
                    ->body(implode('<br>', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '<br>... y ' . (count($errors) - 5) . ' errores más.' : ''))
                    ->warning()
                    ->send();
            }

            if ($imported === 0 && empty($errors)) {
                Notification::make()
                    ->title('ℹ️ Importación Finalizada')
                    ->body('No se importaron nuevos registros.')
                    ->info()
                    ->send();
            }

            $this->dispatch('refresh');
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error en la Importación')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}


