<x-filament-panels::page>
    {{-- Estadísticas --}}
    <div class="grid gap-4 md:grid-cols-2 mb-8">
        {{-- Fotos en carpeta --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fotos en carpeta</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ $this->countPhotos() }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">storage/app/public/fotos</p>
                </div>
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/30">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total de colecciones --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de colecciones</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        @php
                            $collectionCount = \App\Models\RekognitionCollection::where('is_active', true)->count();
                            echo $collectionCount;
                        @endphp
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de colecciones --}}
    {{ $this->table }}
</x-filament-panels::page>

