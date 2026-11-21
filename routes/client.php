<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Middleware\AuthenticateInstitutionKey;
use Illuminate\Support\Facades\Route;

/*
| API para clientes desktop/kiosko. Prefijo: /api/v1/client
| Auth: UUID en la URL + cabecera X-Institution-Key (api_key de la institución).
| (routes/api.php es la API administrativa de Rekognition y no se carga.)
*/
Route::prefix('v1/client/{uuid}')
    ->middleware(['throttle:client-auth-guard', AuthenticateInstitutionKey::class])
    ->group(function () {
        Route::get('status', [ClientController::class, 'status'])->middleware('throttle:60,1');
        Route::post('analyze', [ClientController::class, 'analyze'])->middleware('throttle:120,1');
    });
