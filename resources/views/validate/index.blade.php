<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación Biométrica</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="w-full h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-slate-950 border-b border-slate-700 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">Validación Biométrica</h1>
                            <p class="text-xs text-slate-400">Sistema de Reconocimiento Facial</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-300">Estado: <span class="text-green-400 font-semibold">En Línea</span></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-hidden">
            <div class="max-w-7xl mx-auto w-full h-full px-4 sm:px-6 lg:px-8 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">

                    <!-- Left Panel - Camera Section -->
                    <div class="flex flex-col space-y-4">
                        <!-- Camera Feed Container -->
                        <div class="flex-1 bg-slate-950 border-2 border-slate-700 rounded-xl overflow-hidden shadow-2xl hover:border-blue-500 transition-colors">
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 to-slate-800">
                                <!-- Video Stream Placeholder -->
                                <div id="camera-container" class="w-full h-full bg-black flex items-center justify-center relative">
                                    <svg class="w-24 h-24 text-slate-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <!-- Video element will be inserted here by Vite -->
                                </div>

                                <!-- Status Badge -->
                                <div class="absolute top-4 right-4 bg-red-500 rounded-full px-3 py-1 flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                    <span class="text-white text-sm font-semibold">En vivo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Control Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Analizar Rostro</span>
                            </button>
                            <button class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Cancelar</span>
                            </button>
                        </div>

                        <!-- Camera Info -->
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-slate-300 font-semibold">Información de Cámara</p>
                            </div>
                            <div class="space-y-2 text-xs text-slate-400">
                                <div class="flex justify-between">
                                    <span>Dispositivo:</span>
                                    <span class="text-slate-200">Cámara Web Frontal</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Resolución:</span>
                                    <span class="text-slate-200">1280x720</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estado:</span>
                                    <span class="text-green-400 font-semibold">Conectada</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Information Section -->
                    <div class="flex flex-col space-y-4">
                        <!-- Results Card -->
                        <div class="bg-slate-800 border-2 border-slate-700 rounded-xl p-6 shadow-2xl hover:border-green-500 transition-colors">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-lg font-bold text-white">Información Encontrada</h2>
                                <div class="w-10 h-10 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Confidence Score -->
                            <div class="mb-6 pb-6 border-b border-slate-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-slate-300 font-semibold">Similitud</span>
                                    <span class="text-2xl font-bold text-green-400">92%</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-2 rounded-full" style="width: 92%"></div>
                                </div>
                                <p class="text-xs text-slate-400 mt-2">Coincidencia muy buena con el registro</p>
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Nombres</p>
                                    <p class="text-base text-white font-semibold">Juan Carlos Pérez García</p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Documento</p>
                                    <div class="flex items-center space-x-2">
                                        <p class="text-base text-white font-mono font-semibold">12345678</p>
                                        <button class="text-slate-400 hover:text-slate-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Institución</p>
                                    <p class="text-base text-white font-semibold">Universidad Central</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Fecha Registro</p>
                                        <p class="text-sm text-white">2026-02-15</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Última Validación</p>
                                        <p class="text-sm text-white">2026-03-04</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-3">
                            <button class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Confirmar</span>
                            </button>
                            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M9 3h6a2 2 0 012 2v14a2 2 0 01-2 2H9a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                                </svg>
                                <span>Revisar</span>
                            </button>
                        </div>

                        <!-- Status Section -->
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center space-x-2 mb-3">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-slate-300 font-semibold">Estado de Validación</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-sm text-slate-300">Rostro detectado</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-sm text-slate-300">Registro encontrado</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-sm text-slate-300">Validación completada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Hidden states for different scenarios -->
    <!-- Loading State (hidden by default) -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-sm text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-white font-semibold">Analizando rostro...</p>
            <p class="text-slate-400 text-sm mt-2">Por favor espera mientras procesamos</p>
        </div>
    </div>

    <!-- No Match State (hidden by default) -->
    <div id="no-match-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-sm border-2 border-red-500">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <p class="text-white font-bold text-lg text-center mb-2">No se encontró coincidencia</p>
            <p class="text-slate-400 text-sm text-center">El rostro detectado no coincide con ningún registro en la base de datos</p>
            <button class="mt-6 w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                Intentar de Nuevo
            </button>
        </div>
    </div>
</body>
</html>
