<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validación Biométrica</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="w-full h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-slate-950 border-b border-slate-700 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
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
            <div class="max-w-7xl mx-auto w-full h-full px-4 sm:px-6 lg:px-8 py-3">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 h-full">

                    <!-- Left Panel - Camera Section -->
                    <div class="flex flex-col space-y-2">
                        <!-- Camera Feed Container -->
                        <div class="flex-1 bg-slate-950 border-2 border-slate-700 rounded-xl overflow-hidden shadow-2xl hover:border-blue-500 transition-colors">
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 to-slate-800">
                                <!-- Video Stream -->
                                <div id="camera-container" class="w-full h-full bg-black flex items-center justify-center relative">
                                    <video id="video" autoplay playsinline class="w-full h-full object-cover hidden"></video>
                                    <canvas id="canvas" class="hidden"></canvas>

                                    <!-- Placeholder mientras carga -->
                                    <div id="video-placeholder" class="flex flex-col items-center justify-center">
                                        <svg class="w-24 h-24 text-slate-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-slate-400 mt-4 text-sm">Inicializando cámara...</p>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div id="status-badge" class="absolute top-4 right-4 bg-red-500 rounded-full px-3 py-1 flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                    <span class="text-white text-sm font-semibold">En vivo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Control Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button id="analyze-btn" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Analizar Rostro</span>
                            </button>
                            <button id="cancel-btn" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Cancelar</span>
                            </button>
                        </div>

                        <!-- Camera Info -->
                        <div class="bg-slate-800 border border-slate-700 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-slate-300 font-semibold">Información de Cámara</p>
                            </div>
                            <div class="space-y-2 text-xs text-slate-400">
                                <div class="flex justify-between">
                                    <span>Dispositivo:</span>
                                    <span class="text-slate-200" id="device-name">Detectando...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Resolución:</span>
                                    <span class="text-slate-200" id="device-resolution">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estado:</span>
                                    <span class="text-green-400 font-semibold" id="device-status">Conectando...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Information Section -->
                    <div class="flex flex-col space-y-2">
                        <!-- Initial State - No results -->
                        <div id="initial-state" class="flex-1 bg-slate-800 border-2 border-slate-700 rounded-xl p-6 shadow-2xl flex flex-col items-center justify-center">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-slate-300 font-semibold">En espera de análisis</p>
                                <p class="text-slate-400 text-sm mt-2">Presiona "Analizar Rostro" para comenzar</p>
                            </div>
                        </div>

                        <!-- Results Card (hidden initially) -->
                        <div id="results-state" class="hidden flex-1 flex flex-col space-y-2 overflow-auto">
                            <div class="bg-slate-800 border-2 border-green-500 rounded-xl p-4 shadow-2xl">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-base font-bold text-white">Información Encontrada</h2>
                                    <div class="w-10 h-10 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Confidence Score -->
                                <div class="mb-4 pb-4 border-b border-slate-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-slate-300 font-semibold">Similitud</span>
                                        <span class="text-2xl font-bold text-green-400" id="similarity-score">92%</span>
                                    </div>
                                    <div class="w-full bg-slate-700 rounded-full h-2">
                                        <div id="similarity-bar" class="bg-gradient-to-r from-green-500 to-emerald-500 h-2 rounded-full" style="width: 92%"></div>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">Coincidencia muy buena con el registro</p>
                                </div>

                                <!-- Personal Information with Photo -->
                                <div class="flex flex-col lg:flex-row gap-4">
                                    <!-- Left Column - Personal Data -->
                                    <div class="flex-1 space-y-3">
                                        <div>
                                            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Nombres</p>
                                            <p class="text-base text-white font-semibold" id="person-names">-</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Documento</p>
                                            <div class="flex items-center space-x-2">
                                                <p class="text-base text-white font-mono font-semibold" id="person-document">-</p>
                                                <button onclick="copyToClipboard()" class="text-slate-400 hover:text-slate-200 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Institución</p>
                                            <p class="text-base text-white font-semibold" id="person-institution">-</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-2">Fecha Registro</p>
                                            <p class="text-sm text-white" id="person-date">-</p>
                                        </div>
                                    </div>

                                    <!-- Right Column - Photo (Responsive) -->
                                    <div id="photo-section" class="hidden lg:flex lg:flex-shrink-0">
                                        <div class="bg-slate-700 border border-slate-600 rounded-lg overflow-hidden flex flex-col items-center justify-center p-2" style="width: 300px;">
                                            <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold mb-3 text-center">Foto Registrada</p>
                                            <img id="person-photo" src="" alt="Foto de la persona" class="w-full h-64 object-cover rounded">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3">
                                <button id="confirm-btn" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Confirmar</span>
                                </button>
                                <button id="retry-btn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span>Reintentar</span>
                                </button>
                            </div>
                        </div>

                        <!-- No Match State (hidden) -->
                        <div id="no-match-state" class="hidden flex-1 bg-slate-800 border-2 border-red-500 rounded-xl p-4 shadow-2xl flex flex-col items-center justify-center">
                            <div class="w-16 h-16 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <p class="text-white font-bold text-lg text-center mb-2">No se encontró coincidencia</p>
                            <p class="text-slate-400 text-sm text-center">El rostro detectado no coincide con ningún registro en la base de datos</p>
                            <button id="no-match-retry-btn" class="mt-6 w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                Intentar de Nuevo
                            </button>
                        </div>

                        <!-- Status Section -->
                        <div id="status-section" class="bg-slate-800 border border-slate-700 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-slate-300 font-semibold">Estado de Validación</p>
                            </div>
                            <div id="status-list" class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 bg-slate-600 rounded-full"></span>
                                    <span class="text-sm text-slate-400">Esperando análisis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-sm text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-white font-semibold">Analizando rostro...</p>
            <p class="text-slate-400 text-sm mt-2">Por favor espera mientras procesamos</p>
        </div>
    </div>

    <script>
        let video, canvas, ctx;
        const UUID = '{{ $uuid }}';

        // Inicializar cámara
        async function initCamera() {
            try {
                // Validar que mediaDevices está disponible
                if (!navigator.mediaDevices) {
                    throw new Error('API de MediaDevices no disponible. Se requiere HTTPS o localhost para acceder a la cámara.');
                }

                if (!navigator.mediaDevices.getUserMedia) {
                    throw new Error('getUserMedia no está disponible en este navegador');
                }

                video = document.getElementById('video');
                canvas = document.getElementById('canvas');
                ctx = canvas.getContext('2d');

                // Obtener stream de cámara
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });

                video.srcObject = stream;
                video.muted = true; // Silenciar audio si hay

                // Esperar a que el video esté listo
                const videoReadyPromise = new Promise((resolve) => {
                    video.onloadedmetadata = () => {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;

                        // Ocultar placeholder y mostrar video
                        document.getElementById('video-placeholder').classList.add('hidden');
                        video.classList.remove('hidden');

                        // Actualizar información
                        document.getElementById('device-name').textContent = 'Cámara Web Frontal';
                        document.getElementById('device-status').textContent = 'Conectada';
                        document.getElementById('device-status').classList.remove('text-red-400');
                        document.getElementById('device-status').classList.add('text-green-400');
                        document.getElementById('device-resolution').textContent = `${video.videoWidth}x${video.videoHeight}`;

                        resolve();
                    };
                });

                // Reproducir el video
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(err => {
                        console.warn('⚠️ Error al reproducir video (puede ser normal):', err.message);
                    });
                }

                await videoReadyPromise;

            } catch (error) {
                console.error('❌ Error al acceder a la cámara:', error);

                let errorMessage = error.message;
                let solution = '';

                // Mensajes de error más específicos
                if (error.name === 'NotAllowedError') {
                    errorMessage = 'Permiso denegado';
                    solution = 'Por favor, permite el acceso a la cámara cuando el navegador lo solicite.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage = 'Cámara no encontrada';
                    solution = 'Asegúrate de que tu dispositivo tiene cámara conectada.';
                } else if (error.name === 'SecurityError') {
                    errorMessage = 'Error de seguridad - Se requiere HTTPS';
                    solution = 'Accede a través de HTTPS o localhost para usar la cámara.';
                }

                document.getElementById('device-status').textContent = 'Error: ' + errorMessage;
                document.getElementById('device-status').classList.add('text-red-400');

                document.getElementById('video-placeholder').innerHTML = `
                    <div class="flex flex-col items-center justify-center text-center px-4">
                        <svg class="w-24 h-24 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-400 font-semibold">Error: ${errorMessage}</p>
                        <p class="text-slate-400 text-sm mt-2">${solution || error.message}</p>
                        ${window.location.protocol === 'http:' && window.location.hostname !== 'localhost' ?
                            `<p class="text-yellow-400 text-xs mt-4 max-w-xs">💡 Intenta acceder a través de <strong>https://</strong> o <strong>localhost</strong></p>`
                            : ''}
                    </div>
                `;
            }
        }

        // Capturar foto y enviar para análisis
        async function captureFace() {
            if (!video || !video.srcObject) {
                alert('❌ La cámara no está disponible');
                return;
            }

            try {
                // Dibujar frame actual en canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convertir a base64
                const imageData = canvas.toDataURL('image/jpeg', 0.9);

                // Mostrar loading
                document.getElementById('loading-overlay').classList.remove('hidden');

                const response = await fetch(`/validate/${UUID}/analyze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ image: imageData })
                });

                const result = await response.json();
                document.getElementById('loading-overlay').classList.add('hidden');

                if (result.success) {
                    showResults(result.data);
                } else if (result.type === 'no_match') {
                    showNoMatch();
                } else {
                    alert('Error: ' + result.message);
                }

            } catch (error) {
                document.getElementById('loading-overlay').classList.add('hidden');
                alert('Error: ' + error.message);
            }
        }

        // Mostrar resultados
        function showResults(data) {
            document.getElementById('initial-state').classList.add('hidden');
            document.getElementById('no-match-state').classList.add('hidden');
            document.getElementById('results-state').classList.remove('hidden');

            document.getElementById('person-names').textContent = data.names;
            document.getElementById('person-document').textContent = data.document_number;
            document.getElementById('person-institution').textContent = data.institution;
            document.getElementById('person-date').textContent = data.created_at;
            document.getElementById('similarity-score').textContent = data.similarity + '%';
            document.getElementById('similarity-bar').style.width = data.similarity + '%';

            // Mostrar foto si existe
            if (data.photo_url) {
                const photoImg = document.getElementById('person-photo');
                photoImg.src = data.photo_url;
                photoImg.onerror = function() {
                    this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" class="w-48 h-48" fill="none" viewBox="0 0 24 24" stroke="currentColor"%3E%3Cpath stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/%3E%3C/svg%3E';
                    this.classList.add('opacity-50');
                };
                document.getElementById('photo-section').classList.remove('hidden');
            } else {
                document.getElementById('photo-section').classList.add('hidden');
            }

            // Actualizar estado
            document.getElementById('status-list').innerHTML = `
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
            `;
        }

        // Mostrar no encontrado
        function showNoMatch() {
            document.getElementById('initial-state').classList.add('hidden');
            document.getElementById('results-state').classList.add('hidden');
            document.getElementById('no-match-state').classList.remove('hidden');

            document.getElementById('status-list').innerHTML = `
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                    <span class="text-sm text-slate-300">Rostro detectado</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                    <span class="text-sm text-slate-300">Sin coincidencia</span>
                </div>
            `;
        }

        // Copiar documento al portapapeles
        function copyToClipboard() {
            const text = document.getElementById('person-document').textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Documento copiado al portapapeles');
            }).catch(err => {
                console.error('Error al copiar:', err);
                alert('⚠️ No se pudo copiar');
            });
        }

        // Event listeners
        document.getElementById('analyze-btn').addEventListener('click', captureFace);
        document.getElementById('cancel-btn').addEventListener('click', () => location.reload());
        document.getElementById('retry-btn').addEventListener('click', () => {
            document.getElementById('initial-state').classList.remove('hidden');
            document.getElementById('results-state').classList.add('hidden');
            document.getElementById('no-match-state').classList.add('hidden');
        });
        document.getElementById('no-match-retry-btn').addEventListener('click', () => {
            document.getElementById('initial-state').classList.remove('hidden');
            document.getElementById('no-match-state').classList.add('hidden');
        });
        document.getElementById('confirm-btn').addEventListener('click', () => {
            alert('✅ Validación confirmada');
        });

        // Inicializar al cargar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCamera);
        } else {
            initCamera();
        }
    </script>
</body>
</html>
