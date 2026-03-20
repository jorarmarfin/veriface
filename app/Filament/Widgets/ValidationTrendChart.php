<?php

namespace App\Filament\Widgets;

use App\Models\ValidationLog;
use Filament\Widgets\ChartWidget;

class ValidationTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tendencia de Validaciones (Últimos 14 días)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $successData = [];
        $failedData = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');

            $successData[] = ValidationLog::whereDate('validated_at', $date->toDateString())
                ->where('matched', true)
                ->count();

            $failedData[] = ValidationLog::whereDate('validated_at', $date->toDateString())
                ->where('matched', false)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Exitosas',
                    'data' => $successData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.18)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Fallidas',
                    'data' => $failedData,
                    'borderColor' => '#F97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.15)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
