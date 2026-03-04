<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio No Disponible</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="w-full h-screen flex flex-col items-center justify-center px-4">
        <!-- Container -->
        <div class="max-w-md w-full">
            <!-- Icon Container -->
            <div class="flex justify-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-orange-500 rounded-full flex items-center justify-center shadow-2xl">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-2H9m3 0h3"></path>
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-slate-800 border-2 border-slate-700 rounded-2xl p-8 text-center shadow-2xl">
                <h1 class="text-2xl font-bold text-white mb-3">Servicio No Disponible</h1>

                <p class="text-slate-300 mb-6">
                    El servicio de validación biométrica de <span class="font-semibold">{{ $institution->name }}</span> no está disponible en este momento.
                </p>

                <!-- Reason Card -->
                <div class="bg-slate-700 rounded-lg p-4 mb-6 border border-amber-500 border-opacity-30">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-amber-300">Renovación Requerida</p>
                            <p class="text-xs text-slate-400 mt-1">Su suscripción ha expirado o requiere renovación</p>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <p class="text-slate-400 text-sm mb-8">
                    Para reactivar el servicio de validación biométrica, comuníquese con el equipo administrativo.
                </p>

                <!-- Contact Info -->
                <div class="space-y-3 mb-8">
                    <div class="text-slate-300 text-sm">
                        <p class="font-semibold mb-2">Contacto Administrativo:</p>
                        <div class="bg-slate-900 rounded p-3 space-y-1">
                            <p><span class="text-slate-400">Institución:</span> <span class="text-white font-semibold">{{ $institution->name }}</span></p>
                            @if($institution->email)
                                <p><span class="text-slate-400">Email:</span> <span class="text-white break-all">{{ $institution->email }}</span></p>
                            @endif
                            @if($institution->phone)
                                <p><span class="text-slate-400">Teléfono:</span> <span class="text-white">{{ $institution->phone }}</span></p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <a href="javascript:history.back()" class="inline-block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl">
                    <span class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Volver Atrás</span>
                    </span>
                </a>
            </div>

            <!-- Footer Info -->
            <div class="mt-8 text-center">
                <p class="text-slate-500 text-xs">
                    Sistema de Validación Biométrica
                </p>
                <p class="text-slate-600 text-xs mt-1">
                    © {{ date('Y') }} Todos los derechos reservados
                </p>
            </div>
        </div>
    </div>
</body>
</html>

