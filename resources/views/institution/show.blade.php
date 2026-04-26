<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institution->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="w-full min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <div class="max-w-lg w-full">

            <!-- Header -->
            <div class="flex justify-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-2xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>

            <!-- Card principal -->
            <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden">

                <!-- Título -->
                <div class="px-8 pt-8 pb-6 border-b border-slate-700">
                    <h1 class="text-2xl font-bold text-white text-center">{{ $institution->name }}</h1>
                    @if($institution->event)
                        <p class="text-slate-400 text-sm text-center mt-1">{{ $institution->event }}</p>
                    @endif
                    <div class="flex justify-center mt-3">
                        @if($institution->is_active)
                            <span class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                                Activa
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-red-500/10 text-red-400 text-xs font-semibold px-3 py-1 rounded-full border border-red-500/20">
                                <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                Inactiva
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Datos -->
                <div class="px-8 py-6 space-y-4">

                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <span class="text-slate-400 text-sm">Slug</span>
                        <span class="text-white font-mono text-sm bg-slate-700 px-3 py-1 rounded">{{ $institution->slug }}</span>
                    </div>

                    @if($institution->rekognitionCollection)
                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <span class="text-slate-400 text-sm">Colección Rekognition</span>
                        <span class="text-white text-sm">{{ $institution->rekognitionCollection->name }}</span>
                    </div>
                    @endif

                    <!-- Validaciones -->
                    <div class="py-3">
                        <p class="text-slate-400 text-sm mb-3">Validaciones</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-slate-700/50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-white">
                                    {{ $institution->validations_contracted === null ? '∞' : number_format($institution->validations_contracted) }}
                                </p>
                                <p class="text-slate-400 text-xs mt-1">Contratadas</p>
                            </div>
                            <div class="bg-slate-700/50 rounded-xl p-4 text-center">
                                <p class="text-2xl font-bold text-amber-400">{{ number_format($institution->validations_used) }}</p>
                                <p class="text-slate-400 text-xs mt-1">Usadas</p>
                            </div>
                            <div class="bg-slate-700/50 rounded-xl p-4 text-center">
                                @php
                                    $remaining = $institution->validations_contracted === null
                                        ? null
                                        : max(0, $institution->validations_contracted - $institution->validations_used);
                                    $color = match(true) {
                                        $remaining === null => 'text-slate-300',
                                        $remaining === 0 => 'text-red-400',
                                        $remaining <= 10 => 'text-amber-400',
                                        default => 'text-emerald-400',
                                    };
                                @endphp
                                <p class="text-2xl font-bold {{ $color }}">
                                    {{ $remaining === null ? '∞' : number_format($remaining) }}
                                </p>
                                <p class="text-slate-400 text-xs mt-1">Restantes</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer de la card -->
                <div class="px-8 py-4 bg-slate-900/40 border-t border-slate-700/50">
                    <p class="text-slate-500 text-xs text-center">
                        Creada el {{ $institution->created_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-slate-500 text-xs">Sistema de Validación Biométrica</p>
                <p class="text-slate-600 text-xs mt-1">© {{ date('Y') }} Todos los derechos reservados</p>
            </div>

        </div>
    </div>
</body>
</html>
