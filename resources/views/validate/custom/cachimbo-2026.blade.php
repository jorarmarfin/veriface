<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro Biometrico - {{ $institution->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .board-glow {
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.35), 0 0 28px rgba(14, 165, 233, 0.22);
        }

        .scan-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.92), transparent);
            animation: scan 2.4s linear infinite;
        }

        @keyframes scan {
            0% { top: 6%; opacity: 0.25; }
            50% { opacity: 0.9; }
            100% { top: 92%; opacity: 0.25; }
        }
    </style>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,_#12345a_0%,_#08172a_40%,_#050b15_100%)] text-slate-100">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-4 sm:px-6 lg:px-8">
        <header class="mb-4 flex flex-col justify-between gap-3 rounded-xl border border-cyan-900/55 bg-slate-950/65 px-4 py-3 backdrop-blur sm:flex-row sm:items-center">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Veriface</p>
                <h1 class="text-xl font-bold text-white">{{ $institution->name }}</h1>
                <p class="text-sm text-slate-300">{{ $institution->event ?: 'Registro de identidad biometrica' }}</p>
            </div>
            <div class="rounded-lg border border-cyan-500/25 bg-slate-900/80 px-3 py-2 text-right">
                <p class="text-xs uppercase tracking-wide text-cyan-200">Validaciones disponibles</p>
                <p class="text-base font-semibold text-white">
                    @if($institution->validations_remaining === null)
                        Ilimitadas
                    @else
                        {{ number_format($institution->validations_remaining) }} restantes
                    @endif
                </p>
            </div>
        </header>

        <section class="grid flex-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <div class="relative h-full min-h-[420px] overflow-hidden rounded-2xl border border-cyan-900/65 bg-slate-950/80 board-glow">
                    <div class="scan-line"></div>
                    <div id="camera-wrapper" class="relative h-full w-full">
                        <video id="video" autoplay playsinline class="h-full w-full object-cover hidden"></video>
                        <canvas id="canvas" class="hidden"></canvas>
                        <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 text-center">
                            <svg class="h-16 w-16 animate-pulse text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p id="camera-message" class="text-sm text-slate-300">Iniciando camara...</p>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 border-t border-cyan-900/60 bg-slate-950/90 px-4 py-3 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Estado de camara</span>
                            <span id="camera-status" class="font-semibold text-cyan-300">Conectando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="flex h-full flex-col rounded-2xl border border-cyan-900/60 bg-slate-950/75 p-5 board-glow">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Panel de acceso</p>
                            <h2 id="headline" class="mt-2 text-4xl font-extrabold leading-tight text-cyan-200">Identidad en proceso</h2>
                        </div>
                        <div id="status-icon" class="mt-1 flex h-14 w-14 items-center justify-center rounded-full border border-cyan-400/60 bg-cyan-500/15 text-cyan-300"></div>
                    </div>

                    <div class="mb-5 rounded-xl border border-cyan-800/50 bg-slate-900/70 p-4">
                        <p id="primary-message" class="text-2xl font-bold text-white">Listo para validar</p>
                        <p id="secondary-message" class="mt-1 text-sm text-slate-300">Ubica el rostro en pantalla y presiona "Validar".</p>
                    </div>

                    <div class="mb-5">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-slate-300">Nivel de coincidencia</span>
                            <span id="similarity-text" class="font-semibold text-cyan-300">0%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-800">
                            <div id="similarity-bar" class="h-full w-0 rounded-full bg-gradient-to-r from-cyan-400 via-emerald-400 to-lime-300 transition-all duration-500"></div>
                        </div>
                    </div>

                    <div class="mb-6 space-y-4 rounded-xl border border-cyan-900/55 bg-slate-900/55 p-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Nombre completo</p>
                            <p id="person-name" class="text-3xl font-black text-white">-</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Documento</p>
                                <p id="person-document" class="text-lg font-semibold text-cyan-200">-</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Institucion</p>
                                <p class="text-lg font-semibold text-cyan-200">{{ $institution->name }}</p>
                            </div>
                        </div>
                        <p id="welcome-line" class="text-4xl font-black leading-tight text-cyan-300">
                            {{ $institution->event ? 'Bienvenido ' . $institution->event : 'Bienvenido 2026' }}
                        </p>
                    </div>

                    <div class="mt-auto grid gap-3 sm:grid-cols-2">
                        <button id="analyze-btn" class="rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-5 py-3 text-base font-bold text-white transition hover:from-cyan-400 hover:to-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                            Validar
                        </button>
                        <button id="reset-btn" class="rounded-lg border border-slate-600 bg-slate-800/80 px-5 py-3 text-base font-semibold text-slate-100 transition hover:bg-slate-700">
                            Reiniciar
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="loading-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-sm">
        <div class="rounded-xl border border-cyan-700/70 bg-slate-900 px-8 py-6 text-center shadow-2xl">
            <div class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-2 border-cyan-400 border-t-transparent"></div>
            <p class="font-semibold text-white">Validando identidad...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const UUID = '{{ $uuid }}';
        const INITIAL_REMAINING = @json($institution->validations_remaining);
        const DEFAULT_WELCOME = @json($institution->event ? 'Bienvenido ' . $institution->event : 'Bienvenido 2026');

        let video = null;
        let canvas = null;
        let ctx = null;
        let analyzeInProgress = false;

        const icons = {
            ready: '<svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            success: '<svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"></path></svg>',
            fail: '<svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>',
        };

        function showMessage({ title, text = '', icon = 'info', confirmButtonText = 'Aceptar' }) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title,
                    text,
                    icon,
                    confirmButtonText,
                    background: '#020617',
                    color: '#e2e8f0',
                    confirmButtonColor: '#0891b2',
                });
            }

            alert(text ? `${title}\n${text}` : title);
            return Promise.resolve();
        }

        function setStatusView(state, payload = {}) {
            const headline = document.getElementById('headline');
            const primary = document.getElementById('primary-message');
            const secondary = document.getElementById('secondary-message');
            const icon = document.getElementById('status-icon');
            const similarityText = document.getElementById('similarity-text');
            const similarityBar = document.getElementById('similarity-bar');
            const personName = document.getElementById('person-name');
            const personDocument = document.getElementById('person-document');
            const welcomeLine = document.getElementById('welcome-line');

            icon.className = 'mt-1 flex h-14 w-14 items-center justify-center rounded-full border';
            icon.innerHTML = icons[state] || icons.ready;

            if (state === 'success') {
                headline.textContent = 'Identidad verificada';
                headline.className = 'mt-2 text-4xl font-extrabold leading-tight text-emerald-300';
                primary.textContent = 'Acceso autorizado';
                secondary.textContent = payload.message || 'Coincidencia encontrada en el registro.';
                icon.classList.add('border-emerald-400/70', 'bg-emerald-500/20', 'text-emerald-300');
                personName.textContent = payload.names || '-';
                personDocument.textContent = payload.document_number || '-';
                similarityText.textContent = `${payload.similarity ?? 0}%`;
                similarityBar.style.width = `${payload.similarity ?? 0}%`;
                similarityBar.className = 'h-full rounded-full bg-gradient-to-r from-emerald-400 to-lime-300 transition-all duration-500';
                welcomeLine.textContent = payload.event ? `Bienvenido ${payload.event}` : 'Bienvenido';
                return;
            }

            if (state === 'fail') {
                headline.textContent = 'Identidad no verificada';
                headline.className = 'mt-2 text-4xl font-extrabold leading-tight text-rose-300';
                primary.textContent = 'Acceso denegado';
                secondary.textContent = payload.message || 'No se encontro coincidencia en los registros.';
                icon.classList.add('border-rose-400/70', 'bg-rose-500/20', 'text-rose-300');
                personName.textContent = 'No identificado';
                personDocument.textContent = '-';
                similarityText.textContent = '0%';
                similarityBar.style.width = '0%';
                similarityBar.className = 'h-full rounded-full bg-gradient-to-r from-rose-400 to-orange-300 transition-all duration-500';
                welcomeLine.textContent = DEFAULT_WELCOME;
                return;
            }

            headline.textContent = 'Identidad en proceso';
            headline.className = 'mt-2 text-4xl font-extrabold leading-tight text-cyan-200';
            primary.textContent = 'Listo para validar';
            secondary.textContent = 'Ubica el rostro en pantalla y presiona "Validar".';
            icon.classList.add('border-cyan-400/70', 'bg-cyan-500/20', 'text-cyan-300');
            personName.textContent = '-';
            personDocument.textContent = '-';
            similarityText.textContent = '0%';
            similarityBar.style.width = '0%';
            similarityBar.className = 'h-full rounded-full bg-gradient-to-r from-cyan-400 via-emerald-400 to-lime-300 transition-all duration-500';
            welcomeLine.textContent = DEFAULT_WELCOME;
        }

        async function getCameraStreamWithFallback() {
            const constraintsList = [
                {
                    video: {
                        facingMode: { ideal: 'user' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                    audio: false,
                },
                {
                    video: { facingMode: { ideal: 'user' } },
                    audio: false,
                },
                {
                    video: true,
                    audio: false,
                },
            ];

            let lastError = null;
            for (const constraints of constraintsList) {
                try {
                    return await navigator.mediaDevices.getUserMedia(constraints);
                } catch (error) {
                    lastError = error;
                }
            }

            throw lastError || new Error('No se pudo iniciar la camara');
        }

        async function initCamera() {
            const cameraStatus = document.getElementById('camera-status');
            const cameraMessage = document.getElementById('camera-message');
            const cameraPlaceholder = document.getElementById('camera-placeholder');

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('El navegador no permite usar camara en este contexto.');
                }

                video = document.getElementById('video');
                canvas = document.getElementById('canvas');
                ctx = canvas.getContext('2d');

                const stream = await getCameraStreamWithFallback();
                video.srcObject = stream;
                await video.play();

                await new Promise((resolve) => {
                    if (video.readyState >= HTMLMediaElement.HAVE_METADATA) {
                        resolve();
                        return;
                    }

                    video.onloadedmetadata = () => resolve();
                });

                canvas.width = video.videoWidth || 1280;
                canvas.height = video.videoHeight || 720;
                video.classList.remove('hidden');
                cameraPlaceholder.classList.add('hidden');
                cameraStatus.textContent = 'En linea';
                cameraStatus.classList.remove('text-red-300');
                cameraStatus.classList.add('text-cyan-300');
            } catch (error) {
                cameraStatus.textContent = 'Error de camara';
                cameraStatus.classList.remove('text-cyan-300');
                cameraStatus.classList.add('text-red-300');
                cameraMessage.textContent = error?.message || 'No se pudo inicializar la camara.';
            }
        }

        function setAnalyzeLoading(isLoading) {
            const analyzeBtn = document.getElementById('analyze-btn');
            analyzeBtn.disabled = isLoading;
        }

        async function captureAndAnalyze() {
            if (analyzeInProgress) {
                return;
            }

            if (!video || !video.srcObject || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
                await showMessage({
                    title: 'Camara no disponible',
                    text: 'No se puede capturar imagen en este momento.',
                    icon: 'warning',
                });
                return;
            }

            analyzeInProgress = true;
            setAnalyzeLoading(true);
            document.getElementById('loading-overlay').classList.remove('hidden');
            setStatusView('ready');
            document.getElementById('primary-message').textContent = 'Validando identidad...';

            try {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = canvas.toDataURL('image/jpeg', 0.95);

                const response = await fetch(`/validate/${UUID}/analyze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ image: imageData }),
                });

                const result = await response.json();

                if (result.success) {
                    setStatusView('success', {
                        ...result.data,
                        message: result.message,
                    });
                    return;
                }

                if (result.type === 'quota_exceeded' || result.type === 'inactive') {
                    await showMessage({
                        title: 'Validacion no disponible',
                        text: result.message || 'La institucion no puede validar en este momento.',
                        icon: 'warning',
                    });
                    window.location.reload();
                    return;
                }

                if (result.type === 'no_match') {
                    setStatusView('fail', {
                        message: result.message,
                    });
                    return;
                }

                await showMessage({
                    title: 'Error',
                    text: result.message || 'Ocurrio un error en el analisis.',
                    icon: 'error',
                });
            } catch (error) {
                await showMessage({
                    title: 'Error de validacion',
                    text: error?.message || 'Error inesperado',
                    icon: 'error',
                });
            } finally {
                document.getElementById('loading-overlay').classList.add('hidden');
                analyzeInProgress = false;
                setAnalyzeLoading(false);
            }
        }

        function resetView() {
            setStatusView('ready');
        }

        document.getElementById('analyze-btn').addEventListener('click', captureAndAnalyze);
        document.getElementById('reset-btn').addEventListener('click', resetView);

        if (INITIAL_REMAINING !== null && Number(INITIAL_REMAINING) <= 0) {
            document.getElementById('analyze-btn').disabled = true;
            document.getElementById('camera-status').textContent = 'Sin cuota disponible';
            document.getElementById('camera-status').classList.remove('text-cyan-300');
            document.getElementById('camera-status').classList.add('text-red-300');
        }

        setStatusView('ready');
        initCamera();
    </script>
</body>
</html>
