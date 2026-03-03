<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VeriFace') }} — Reconocimiento Facial</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #06b6d4, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-light {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .scan-line {
            animation: scanLine 3s ease-in-out infinite;
        }
        @keyframes scanLine {
            0%, 100% { transform: translateY(-100%); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
        }
        .pulse-ring {
            animation: pulseRing 2s ease-out infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(0.8); opacity: 0.8; }
            50% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        .float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .grid-bg {
            background-image:
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        @media (prefers-color-scheme: dark) {
            .grid-bg {
                background-image:
                    linear-gradient(rgba(59, 130, 246, 0.07) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(59, 130, 246, 0.07) 1px, transparent 1px);
            }
        }
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }
        @keyframes particleFloat {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-10vh) translateX(50px); opacity: 0; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 overflow-x-hidden">

    {{-- Particles Background --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        @for ($i = 0; $i < 20; $i++)
            <div class="particle bg-blue-400/30 dark:bg-blue-400/20"
                 style="left: {{ rand(0, 100) }}%; animation-duration: {{ rand(8, 20) }}s; animation-delay: {{ rand(0, 10) }}s;"></div>
        @endfor
    </div>

    {{-- Grid Background --}}
    <div class="fixed inset-0 grid-bg pointer-events-none"></div>

    {{-- Navigation --}}
    <nav class="relative z-50 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-3 group">
                    <div class="relative w-10 h-10 flex items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl rotate-6 group-hover:rotate-12 transition-transform duration-300"></div>
                        <div class="relative bg-white dark:bg-slate-900 rounded-lg w-8 h-8 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Veri<span class="gradient-text">Face</span></span>
                        <span class="hidden sm:block text-[10px] font-medium text-slate-400 dark:text-slate-500 uppercase tracking-widest -mt-1">Biometric System</span>
                    </div>
                </a>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('filament.admin.auth.logout') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg border border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all duration-200"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar Sesión
                        </a>
                        <form id="logout-form" action="{{ route('filament.admin.auth.logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}"
                           class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-200 transform hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Iniciar Sesión
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 sm:pt-16 lg:pt-20 pb-16 lg:pb-24">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Left Content --}}
            <div class="text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/50 text-blue-700 dark:text-blue-300 text-xs font-semibold uppercase tracking-wider mb-6">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    Sistema Activo v1.0
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-slate-900 dark:text-white mb-6">
                    Reconocimiento
                    <span class="gradient-text block">Facial Avanzado</span>
                </h1>

                <p class="text-lg sm:text-xl text-slate-500 dark:text-slate-400 leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                    Backend de integración biométrica para sistemas de <strong class="text-slate-700 dark:text-slate-300">asistencia</strong>,
                    <strong class="text-slate-700 dark:text-slate-300">control de acceso</strong> y
                    <strong class="text-slate-700 dark:text-slate-300">verificación de identidad</strong> mediante inteligencia artificial.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start mb-10">
                    @auth
                        <a href="{{ url('/admin') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 text-base font-semibold rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-300 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Ir al Panel
                        </a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 text-base font-semibold rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-300 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Acceder al Sistema
                        </a>
                    @endauth

                    <a href="#features"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 text-base font-semibold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Más Información
                    </a>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 sm:gap-6 max-w-md mx-auto lg:mx-0">
                    <div class="text-center lg:text-left">
                        <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">99.7<span class="text-blue-500">%</span></div>
                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Precisión</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">&lt;1<span class="text-cyan-500">s</span></div>
                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Respuesta</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">24<span class="text-emerald-500">/7</span></div>
                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Disponible</div>
                    </div>
                </div>
            </div>

            {{-- Right: Face Scan Graphic --}}
            <div class="relative flex items-center justify-center">
                <div class="relative w-72 h-72 sm:w-80 sm:h-80 lg:w-96 lg:h-96 float">
                    {{-- Outer Ring --}}
                    <div class="absolute inset-0 rounded-full border-2 border-dashed border-blue-300/40 dark:border-blue-500/20 animate-spin" style="animation-duration: 20s;"></div>

                    {{-- Middle Ring --}}
                    <div class="absolute inset-4 sm:inset-6 rounded-full border border-cyan-400/30 dark:border-cyan-400/20 pulse-ring"></div>

                    {{-- Inner container --}}
                    <div class="absolute inset-8 sm:inset-10 lg:inset-12 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 border border-slate-200/60 dark:border-slate-700/60 shadow-2xl flex items-center justify-center overflow-hidden">
                        {{-- Face Scan SVG --}}
                        <svg class="w-28 h-28 sm:w-32 sm:h-32 lg:w-40 lg:h-40 text-slate-400 dark:text-slate-500" viewBox="0 0 120 120" fill="none">
                            {{-- Face outline --}}
                            <ellipse cx="60" cy="58" rx="28" ry="34" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 3"/>
                            {{-- Eyes --}}
                            <circle cx="48" cy="50" r="4" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="72" cy="50" r="4" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="48" cy="50" r="1.5" fill="currentColor"/>
                            <circle cx="72" cy="50" r="1.5" fill="currentColor"/>
                            {{-- Nose --}}
                            <path d="M57 56 L60 65 L63 56" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                            {{-- Mouth --}}
                            <path d="M50 72 Q60 79 70 72" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            {{-- Scan corners --}}
                            <path d="M20 35 L20 20 L35 20" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M85 20 L100 20 L100 35" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M100 85 L100 100 L85 100" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M35 100 L20 100 L20 85" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            {{-- Reference points --}}
                            <circle cx="48" cy="44" r="1" fill="#06b6d4" opacity="0.7"/>
                            <circle cx="72" cy="44" r="1" fill="#06b6d4" opacity="0.7"/>
                            <circle cx="60" cy="38" r="1" fill="#06b6d4" opacity="0.7"/>
                            <circle cx="44" cy="58" r="1" fill="#06b6d4" opacity="0.7"/>
                            <circle cx="76" cy="58" r="1" fill="#06b6d4" opacity="0.7"/>
                            <circle cx="60" cy="78" r="1" fill="#06b6d4" opacity="0.7"/>
                        </svg>

                        {{-- Scan Line --}}
                        <div class="absolute inset-x-4 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent scan-line shadow-lg shadow-cyan-400/50"></div>
                    </div>

                    {{-- Corner decorations --}}
                    <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-blue-500 rounded-tl-lg"></div>
                    <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-blue-500 rounded-tr-lg"></div>
                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-blue-500 rounded-bl-lg"></div>
                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-blue-500 rounded-br-lg"></div>

                    {{-- Status badge --}}
                    <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 glass-light dark:glass px-4 py-2 rounded-full flex items-center gap-2 shadow-lg">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">Escáner Listo</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="text-center mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Integraciones <span class="gradient-text">Disponibles</span>
            </h2>
            <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
                Módulos preparados para conectar con tus sistemas existentes mediante API REST.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            {{-- Feature 1: Asistencia --}}
            <div class="group relative p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 shadow-sm hover:shadow-xl hover:shadow-blue-500/5 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Control de Asistencia</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Registro automático de entrada y salida con verificación facial en tiempo real. Sin contacto, sin tarjetas.
                </p>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-blue-600 dark:text-blue-400">
                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                    API Endpoint disponible
                </div>
            </div>

            {{-- Feature 2: Control de Acceso --}}
            <div class="group relative p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-cyan-300 dark:hover:border-cyan-700 shadow-sm hover:shadow-xl hover:shadow-cyan-500/5 transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-cyan-50 dark:bg-cyan-950/50 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Control Biométrico</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Gestión de acceso a zonas restringidas con autenticación biométrica de múltiples factores.
                </p>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-cyan-600 dark:text-cyan-400">
                    <span class="w-1.5 h-1.5 bg-cyan-500 rounded-full"></span>
                    Integración modular
                </div>
            </div>

            {{-- Feature 3: Verificación --}}
            <div class="group relative p-6 sm:p-8 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-violet-300 dark:hover:border-violet-700 shadow-sm hover:shadow-xl hover:shadow-violet-500/5 transition-all duration-300 hover:-translate-y-1 sm:col-span-2 lg:col-span-1">
                <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Verificación de Identidad</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Validación facial comparada contra registros almacenados para procesos de autenticación segura.
                </p>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-violet-600 dark:text-violet-400">
                    <span class="w-1.5 h-1.5 bg-violet-500 rounded-full"></span>
                    Machine Learning activo
                </div>
            </div>
        </div>
    </section>

    {{-- Tech Stack Section --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="rounded-2xl bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-8 sm:p-10 lg:p-12">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">
                    Stack <span class="gradient-text">Tecnológico</span>
                </h2>
                <p class="text-slate-500 dark:text-slate-400">Construido con tecnologías probadas y escalables.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 lg:gap-8">
                <div class="flex flex-col items-center gap-3 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-200">
                    <div class="w-12 h-12 rounded-lg bg-red-50 dark:bg-red-950/30 flex items-center justify-center">
                        <svg class="w-7 h-7 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012a.27.27 0 01-.064-.027L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.009.024-.02.038-.027L4.89.119a.374.374 0 01.376 0L9.63 2.654c.013.007.024.018.037.027.013.009.024.017.033.027.013.014.016.024.027.038.008.015.018.022.023.033.01.018.014.037.02.058.004.01.011.02.014.032a.4.4 0 01.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.014.015-.022.023-.033.01-.015.02-.027.033-.045.012-.009.025-.018.037-.027.014-.008.024-.02.038-.027l4.364-2.535a.376.376 0 01.376 0l4.364 2.535c.015.007.024.02.038.027.013.01.024.018.036.027l.034.045c.008.011.015.02.023.033.01.019.017.038.024.059.003.01.009.022.013.032z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Laravel</span>
                </div>

                <div class="flex flex-col items-center gap-3 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-200">
                    <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center">
                        <svg class="w-7 h-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Filament</span>
                </div>

                <div class="flex flex-col items-center gap-3 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-200">
                    <div class="w-12 h-12 rounded-lg bg-sky-50 dark:bg-sky-950/30 flex items-center justify-center">
                        <svg class="w-7 h-7 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.001 4.8c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624C13.666 10.618 15.027 12 18.001 12c3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C16.337 6.182 14.976 4.8 12.001 4.8zm-6 7.2c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624 1.177 1.194 2.538 2.576 5.512 2.576 3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C10.337 13.382 8.976 12 6.001 12z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tailwind CSS</span>
                </div>

                <div class="flex flex-col items-center gap-3 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-200">
                    <div class="w-12 h-12 rounded-lg bg-green-50 dark:bg-green-950/30 flex items-center justify-center">
                        <svg class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">API REST</span>
                </div>
            </div>
        </div>
    </section>

    {{-- API Info Section --}}
    <section class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid lg:grid-cols-2 gap-8">
            {{-- API Preview --}}
            <div class="rounded-2xl bg-slate-900 dark:bg-slate-900/80 border border-slate-700 dark:border-slate-800 overflow-hidden shadow-2xl">
                <div class="flex items-center gap-2 px-4 py-3 bg-slate-800 dark:bg-slate-800/50 border-b border-slate-700">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="ml-2 text-xs text-slate-400 font-mono">api/v1/verify</span>
                </div>
                <div class="p-5 sm:p-6 overflow-x-auto">
                    <pre class="text-sm font-mono leading-relaxed"><code><span class="text-violet-400">POST</span> <span class="text-cyan-400">/api/v1/verify</span>

<span class="text-slate-500">// Request</span>
{
  <span class="text-emerald-400">"image"</span>: <span class="text-amber-300">"base64_encoded..."</span>,
  <span class="text-emerald-400">"user_id"</span>: <span class="text-orange-300">1042</span>,
  <span class="text-emerald-400">"threshold"</span>: <span class="text-orange-300">0.85</span>
}

<span class="text-slate-500">// Response 200 OK</span>
{
  <span class="text-emerald-400">"match"</span>: <span class="text-cyan-300">true</span>,
  <span class="text-emerald-400">"confidence"</span>: <span class="text-orange-300">0.974</span>,
  <span class="text-emerald-400">"timestamp"</span>: <span class="text-amber-300">"2026-03-03T10:30:00Z"</span>,
  <span class="text-emerald-400">"processing_ms"</span>: <span class="text-orange-300">342</span>
}</code></pre>
                </div>
            </div>

            {{-- Info Card --}}
            <div class="flex flex-col justify-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-4">
                    API REST <span class="gradient-text">Lista para Integrar</span>
                </h2>
                <p class="text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                    Endpoints documentados y seguros para integrar el reconocimiento facial en cualquier aplicación. Autenticación mediante tokens, respuestas JSON estandarizadas.
                </p>

                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Autenticación con Bearer Token (Sanctum)
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Registro y verificación facial en un solo endpoint
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Logs completos de cada verificación
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Rate limiting configurable por cliente
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Webhooks para eventos de verificación
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-slate-200 dark:border-slate-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">VeriFace</span>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    &copy; {{ date('Y') }} VeriFace — Sistema de Reconocimiento Facial. Todos los derechos reservados.
                </p>
                <div class="flex items-center gap-1 text-xs text-slate-400 dark:text-slate-600">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    Todos los sistemas operativos
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
