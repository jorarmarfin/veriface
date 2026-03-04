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

    {{-- Resultados de búsqueda --}}
    @if ($showSearchResults && !empty($searchResults))
        <div class="mt-8">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            📊 Resultados de Búsqueda
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Foto: <strong>{{ $lastSearchedImage }}</strong>
                        </p>
                    </div>
                </div>

                {{-- Resultados por colección --}}
                <div class="space-y-4">
                    @foreach ($searchResults as $collectionResult)
                        <div class="rounded-lg border border-gray-100 dark:border-gray-800 p-4 bg-gray-50 dark:bg-gray-800/50">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                📁 {{ $collectionResult['collection_name'] }}
                                <span class="ml-2 px-3 py-1 inline-block text-sm font-bold text-white bg-green-600 rounded-full">
                                    {{ $collectionResult['match_count'] }} coincidencia(s)
                                </span>
                            </h3>

                            {{-- Lista de coincidencias --}}
                            <div class="space-y-2">
                                @foreach ($collectionResult['matches'] as $match)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                📸 {{ $match['external_image_id'] ?? 'ID: ' . substr($match['face_id'], 0, 8) . '...' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Face ID: {{ substr($match['face_id'], 0, 12) }}...
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            @php
                                                $similarity = $match['similarity'];
                                                $bgColor = $similarity >= 90 ? 'bg-green-600' : ($similarity >= 80 ? 'bg-blue-600' : 'bg-orange-600');
                                            @endphp
                                            <span class="inline-block px-3 py-1 text-sm font-bold text-white rounded-full {{ $bgColor }}">
                                                {{ number_format($match['similarity'], 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Respuesta JSON de Rekognition --}}
                @if (!empty($rekognitionResponse))
                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                📋 Respuesta JSON de AWS Rekognition
                            </h3>
                            <details class="cursor-pointer">
                                <summary class="select-none px-3 py-1 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded">
                                    Ver/Ocultar JSON
                                </summary>
                                <div class="mt-4 space-y-4">
                                    @foreach ($rekognitionResponse as $response)
                                        <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 overflow-hidden">
                                            <div class="bg-gray-200 dark:bg-gray-700 px-4 py-3 border-b border-gray-300 dark:border-gray-600">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">
                                                    📁 {{ $response['collection_name'] }}
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                        {{ $response['timestamp']->format('d/m/Y H:i:s') }}
                                                    </span>
                                                </h4>
                                            </div>
                                            <pre class="p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200 font-mono">{{ json_encode($response['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    </div>
                @endif

                {{-- Botón para limpiar resultados --}}
                <div class="mt-6 flex justify-end">
                    <button
                        wire:click="resetSearchResults"
                        class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white font-medium hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                        Limpiar Resultados
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

