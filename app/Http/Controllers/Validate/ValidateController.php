<?php

namespace App\Http\Controllers\Validate;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Actions\ValidateFace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ValidateController extends Controller
{
    public function __construct(protected ValidateFace $validateFace)
    {
    }

    public function index($uuid)
    {
        // Obtener institución por UUID
        $institution = Institution::where('uuid', $uuid)->firstOrFail();

        // Validar si la institución está activa
        if (!$institution->is_active) {
            return view('validate.inactive', [
                'institution' => $institution,
                'inactive_reason' => 'inactive',
            ]);
        }

        // Validar si superó el límite de validaciones contratadas
        if ($institution->isValidationQuotaExceeded()) {
            return view('validate.inactive', [
                'institution' => $institution,
                'inactive_reason' => 'quota_exceeded',
            ]);
        }

        return view($this->resolveValidationView($institution), [
            'institution' => $institution,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Analizar rostro de la foto capturada
     */
    public function analyzeFace(Request $request, $uuid): JsonResponse
    {
        $institution = Institution::where('uuid', $uuid)->first();

        if (!$institution) {
            return response()->json(['success' => false, 'message' => 'Institución no encontrada'], 404);
        }

        try {
            $result = $this->validateFace->handle($institution, $request->input('image'));

            return response()->json($result['payload'], $result['status']);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno al procesar la validación',
            ], 500);
        }
    }

    /**
     * Resuelve la vista pública de validación.
     * Si existe una vista por slug en validate/custom/{slug}.blade.php la usa,
     * de lo contrario usa la vista general validate.index.
     */
    private function resolveValidationView(Institution $institution): string
    {
        $rawSlug = strtolower(trim((string) $institution->slug));
        $safeSlug = trim((string) preg_replace('/[^a-z0-9-]+/', '-', $rawSlug), '-');

        if ($safeSlug !== '') {
            $customView = "validate.custom.{$safeSlug}";
            if (view()->exists($customView)) {
                return $customView;
            }
        }

        return 'validate.index';
    }
}
