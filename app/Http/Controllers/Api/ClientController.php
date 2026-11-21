<?php

namespace App\Http\Controllers\Api;

use App\Actions\ValidateFace;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API para clientes (desktop/kiosko) autenticados por api_key de institución.
 */
class ClientController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        /** @var Institution $institution */
        $institution = $request->attributes->get('institution');

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $institution->name,
                'event' => $institution->event,
                'is_active' => $institution->is_active,
                'unlimited' => !$institution->hasValidationLimit(),
                'validations_contracted' => $institution->validations_contracted,
                'validations_used' => $institution->validations_used,
                'validations_remaining' => $institution->validations_remaining,
                'quota_exceeded' => $institution->isValidationQuotaExceeded(),
            ],
        ]);
    }

    public function analyze(Request $request, ValidateFace $validateFace): JsonResponse
    {
        $request->validate(['image' => ['required', 'string']]);

        /** @var Institution $institution */
        $institution = $request->attributes->get('institution');

        try {
            $result = $validateFace->handle($institution, $request->input('image'));

            return response()->json($result['payload'], $result['status']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar la validación',
            ], 500);
        }
    }
}
