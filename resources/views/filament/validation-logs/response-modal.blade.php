@php
    $data = $response;

    if (is_string($data)) {
        $decoded = json_decode($data, true);
        $data = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $data];
    }

    $formatLabel = static fn ($key) => ucwords(str_replace(['_', '-'], ' ', (string) $key));

    $formatScalar = static function ($value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    };
@endphp

<div class="space-y-3">
    @if (blank($data))
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            Este registro no tiene response guardado.
        </div>
    @elseif (is_array($data))
        @foreach ($data as $key => $value)
            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                    {{ $formatLabel($key) }}
                </p>

                @if (is_array($value))
                    <pre class="max-h-72 overflow-auto rounded-md bg-gray-100 p-3 text-xs text-gray-800 dark:bg-gray-950 dark:text-gray-200">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $formatScalar($value) }}</p>
                @endif
            </div>
        @endforeach
    @else
        <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            {{ $formatScalar($data) }}
        </div>
    @endif
</div>

