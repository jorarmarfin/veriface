<?php

namespace App\Exports;

use App\Models\ValidationLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ValidationLogsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly int     $institutionId,
        private readonly ?bool   $matched  = null,
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo   = null,
    ) {}

    public function query(): Builder
    {
        return ValidationLog::query()
            ->with('institution')
            ->where('institution_id', $this->institutionId)
            ->when($this->matched !== null, fn ($q) => $q->where('matched', $this->matched))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('validated_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('validated_at', '<=', $this->dateTo))
            ->orderByDesc('validated_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Institución',
            'Documento',
            'Nombres',
            'Similaridad (%)',
            'Estado',
            'Tipo',
            'Evento',
            'Fecha Validación',
            'Resuelto Por',
        ];
    }

    public function map($row): array
    {
        $data = $row->response['data'] ?? [];

        return [
            $row->id,
            $row->institution?->name ?? '-',
            $row->document_number ?? '-',
            $data['names'] ?? '-',
            $row->similarity !== null ? number_format((float) $row->similarity, 2) : '-',
            $row->matched ? 'Exitoso' : 'Fallido',
            $row->response['type'] ?? '-',
            $data['event'] ?? $row->institution?->event ?? '-',
            $row->validated_at?->format('Y-m-d H:i:s') ?? '-',
            $data['resolved_by'] ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Validaciones';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E40AF'],
                ],
            ],
        ];
    }
}
