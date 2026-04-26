<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $institution->name }} — Monitor Biométrico</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="margin:0;padding:0;background:#030d1a;">
    <livewire:institution-dashboard :institution="$institution" />
    @livewireScripts
</body>
</html>
