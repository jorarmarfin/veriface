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
            ->recordActions(self::getRecordActions());
    }

    private static function getColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Nombre de la Colección')
                ->searchable()
                ->sortable(),

            TextColumn::make('face_count')
                ->label('Número de Rostros')
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

            TextColumn::make('created_at')
                ->label('Fecha de Creación')
                ->dateTime('d/m/Y H:i')
                ->sortable(),

            TextColumn::make('face_model_version')
                ->label('Versión del Modelo'),
        ];
    }

    private static function getRecordActions(): array
    {
        return [
            Action::make('view')
                ->label('Ver Detalles')
                ->icon('heroicon-o-eye')
                ->color('info'),

            Action::make('delete')
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Eliminar colección?')
                ->modalDescription('Esta acción es irreversible.')
                ->modalSubmitActionLabel('Sí, eliminar'),
        ];
    }

}
