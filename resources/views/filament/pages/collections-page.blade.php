<x-filament-panels::page>
    {{-- Mensaje de carga --}}
    @if ($this->loading)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center mb-8">
            <div class="inline-flex">
                <div class="animate-spin">
                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Cargando colecciones...</p>
        </div>
    @endif

    {{-- Header con estadísticas --}}
    <div class="grid gap-4 md:grid-cols-4 mb-8">
        {{-- Total de colecciones --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de colecciones</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ count($this->collections) }}
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rostros totales indexados --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rostros indexados</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ array_sum(array_column($this->collections, 'face_count')) }}
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/30">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Colecciones activas --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Colecciones activas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ count(array_filter($this->collections, fn($c) => $c['face_count'] > 0)) }}
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Estado del sistema --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-green-900/30 px-2 py-1">
                            <span class="h-2 w-2 rounded-full bg-green-600 dark:bg-green-400"></span>
                            <span class="text-sm font-medium text-green-700 dark:text-green-400">Operativo</span>
                        </span>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-teal-50 dark:bg-teal-900/30">
                    <svg class="w-8 h-8 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Sección de creación de colección --}}
    @if (isset($this->form))
        {{ $this->form }}
    @endif

    {{-- Tabla de colecciones --}}
    <div class="mt-8">
        {{ $this->table }}
    </div>

    {{-- Sin colecciones --}}
    @if (empty($this->collections) && !$this->loading)
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                Sin colecciones
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Crea tu primera colección para comenzar a indexar rostros
            </p>
        </div>
    @endif

    {{-- Cargando --}}
    @if ($this->loading)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="inline-flex">
                <div class="animate-spin">
                    <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Cargando colecciones...</p>
        </div>
    @endif
</x-filament-panels::page>
