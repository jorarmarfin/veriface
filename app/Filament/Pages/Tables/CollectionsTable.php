<?php

namespace App\Filament\Pages\Tables;

use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\IconPosition;


class CollectionsTable
{
    public static function configure(Table $table, $collections = []): Table
    {
        return $table
            ->records(fn () => collect($collections))
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->recordActions(self::getRecordActions())
            ->headerActions(self::getHeaderActions())
            ;
    }

    private static function getColumns(): array
    {
        return [
            TextColumn::make('name')->label('Nombre de la Colección')->searchable(),
            TextColumn::make('face_count')->label('Número de Rostros')->sortable(),
            TextColumn::make('created_at')->label('Fecha de Creación')->dateTime()->sortable(),
            TextColumn::make('arn')->label('ARN de la Colección')->wrap(),
            TextColumn::make('face_model_version')->label('Versión del Modelo Facial'),
        ];
    }

    private static function getFilters(): array
    {
        return [
            // Aquí puedes agregar filtros personalizados si es necesario
        ];
    }

    private static function getRecordActions(): array
    {
        return [
            Action::make('view')
                ->label('Ver Detalles')
                ->icon('heroicon-o-eye')
                ->iconPosition(IconPosition::Before)
                ->openUrlInNewTab(),
            Action::make('delete')
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->iconPosition(IconPosition::Before)
                ->color('danger')
                ->requiresConfirmation()
                ->livewireClickHandlerEnabled(false)
        ];
    }

    /**
     * Definir acciones de página
     */
    protected static function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refrescar')
                ->icon('heroicon-m-arrow-path')
                ->color('info')
                ->action('refreshCollections'),

            Action::make('documentation')
                ->label('Documentación')
                ->icon('heroicon-m-question-mark-circle')
                ->color('gray')
                ->url('https://docs.aws.amazon.com/rekognition/')
                ->openUrlInNewTab(),
        ];
    }

}
