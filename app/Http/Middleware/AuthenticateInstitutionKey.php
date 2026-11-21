<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica clientes (desktop) con el UUID de la institución + cabecera X-Institution-Key.
 */
class AuthenticateInstitutionKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $institution = Institution::where('uuid', $request->route('uuid'))->first();
        $key = (string) $request->header('X-Institution-Key', '');

        // Misma respuesta para UUID inexistente, clave faltante o incorrecta (no revela cuál falló)
        if (!$institution || !$institution->checkApiKey($key)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $request->attributes->set('institution', $institution);

        return $next($request);
    }
}
