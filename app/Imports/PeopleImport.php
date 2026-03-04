<?php

namespace App\Imports;

use App\Models\People;
use App\Models\Institution;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class PeopleImport implements ToCollection, WithHeadingRow
{
    protected int $institutionId;
    protected string $filepath;
    protected array $errors = [];
    protected int $imported = 0;
    protected int $skipped = 0;
    protected array $baseColumns = ['names', 'document_number'];

    public function __construct(int $institutionId)
    {
        $this->institutionId = $institutionId;

        // Obtener el filepath de la institución
        $institution = Institution::find($institutionId);
        if (!$institution || !$institution->filepath) {
            throw new \Exception('Institución no encontrada o sin filepath configurado');
        }

        $this->filepath = $institution->filepath;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // Chunk de 500 registros para optimizar la memoria
        $collection->chunk(500)->each(function (Collection $chunk) {
            $this->processChunk($chunk);
        });
    }

    protected function processChunk(Collection $chunk)
    {
        $records = [];

        foreach ($chunk as $index => $row) {
            if ($row->filter()->isEmpty()) {
                $this->skipped++;
                continue;
            }

            // Validar campos requeridos
            if (empty($row['names']) || empty($row['document_number'])) {
                $this->errors[] = "Fila " . ($index + 2) . ": Campos requeridos faltantes (names, document_number)";
                $this->skipped++;
                continue;
            }

            // Verificar duplicados
            $documentNumber = trim($row['document_number']);

            if (People::where('institution_id', $this->institutionId)
                ->where('document_number', $documentNumber)
                ->exists()) {
                $this->skipped++;
                continue;
            }

            // Generar photo_path automáticamente
            // Formato: {filepath}/{document_number}.jpg (sin storage/app/public)
            $photoPath = "{$this->filepath}/{$documentNumber}.jpg";

            // Extraer metadata de campos adicionales
            $metadata = [];
            foreach ($row as $key => $value) {
                if (!in_array($key, $this->baseColumns) && !empty($value)) {
                    $metadata[$key] = $value;
                }
            }

            $records[] = [
                'institution_id' => $this->institutionId,
                'document_number' => $documentNumber,
                'names' => trim($row['names'] ?? ''),
                'photo_path' => $photoPath,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar todos los registros del chunk de una vez
        if (!empty($records)) {
            try {
                DB::table('people')->insert($records);
                $this->imported += count($records);
            } catch (\Exception $e) {
                $this->errors[] = "Error al insertar registros: " . $e->getMessage();
            }
        }
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
