<?php

namespace App\Filament\Resources\ValidationLogs\Pages;

use App\Filament\Resources\ValidationLogs\ValidationLogResource;
use App\Models\Institution;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRecords;

class ManageValidationLogs extends ManageRecords
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Exportar Excel')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->modalHeading('Exportar Validaciones a Excel')
                ->modalIcon('heroicon-o-arrow-down-tray')
                ->modalSubmitActionLabel('Descargar Excel')
                ->modalWidth('md')
                ->form([
                    Select::make('institution_id')
                        ->label('Institución')
                        ->options(Institution::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),

                    Select::make('matched')
                        ->label('Estado de validación')
                        ->placeholder('Todos los estados')
                        ->options([
                            '1' => 'Solo exitosos',
                            '0' => 'Solo fallidos',
                        ])
                        ->columnSpanFull(),

                    DatePicker::make('date_from')
                        ->label('Desde')
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    DatePicker::make('date_to')
                        ->label('Hasta')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('date_from'),
                ])
                ->action(function (array $data) {
                    $params = array_filter([
                        'institution_id' => $data['institution_id'],
                        'matched'        => ($data['matched'] ?? '') !== '' ? $data['matched'] : null,
                        'date_from'      => $data['date_from'] ?? null,
                        'date_to'        => $data['date_to'] ?? null,
                    ], fn ($v) => $v !== null);

                    return redirect(route('validation-logs.export', $params));
                }),

            CreateAction::make(),
        ];
    }
}
