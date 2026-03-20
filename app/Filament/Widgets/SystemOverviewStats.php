<?php

namespace App\Filament\Widgets;

use App\Models\Institution;
use App\Models\People;
use App\Models\ValidationLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalInstitutions = Institution::count();
        $activeInstitutions = Institution::where('is_active', true)->count();
        $totalPeople = People::count();

        $today = now()->toDateString();
        $todayValidations = ValidationLog::whereDate('validated_at', $today)->count();
        $todaySuccess = ValidationLog::whereDate('validated_at', $today)->where('matched', true)->count();

        $last7DaysTotal = ValidationLog::where('validated_at', '>=', now()->subDays(7))->count();
        $last7DaysSuccess = ValidationLog::where('validated_at', '>=', now()->subDays(7))
            ->where('matched', true)
            ->count();
        $successRate7d = $last7DaysTotal > 0
            ? round(($last7DaysSuccess / $last7DaysTotal) * 100, 2)
            : 0;

        return [
            Stat::make('Instituciones Activas', "{$activeInstitutions} / {$totalInstitutions}")
                ->description('Total registradas en el sistema')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Postulantes', number_format($totalPeople))
                ->description('Personas cargadas en la base')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Validaciones Hoy', (string) $todayValidations)
                ->description("Éxitos: {$todaySuccess}")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Tasa de Éxito (7 días)', "{$successRate7d}%")
                ->description("{$last7DaysSuccess} de {$last7DaysTotal} validaciones")
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color($successRate7d >= 80 ? 'success' : ($successRate7d >= 60 ? 'warning' : 'danger')),
        ];
    }
}

