@php
    $resp     = $lastLog?->response ?? [];
    $data     = $resp['data'] ?? [];
    $isMatch  = $lastLog?->matched ?? false;
    $hasLog   = $lastLog !== null;

    $names       = $data['names'] ?? null;
    $docNumber   = $lastLog?->document_number ?? ($data['document_number'] ?? null);
    $similarity  = $lastLog ? (float) $lastLog->similarity : 0;
    $photoUrl    = $data['photo_url'] ?? null;
    $event       = $data['event'] ?? $institution->event ?? null;
    $resolvedBy  = $data['resolved_by'] ?? null;
    $createdAt   = $data['created_at'] ?? null;
    $validatedAt = $lastLog?->validated_at;

    $statusColor = $isMatch ? '#00ff88' : ($hasLog ? '#ff2d55' : '#00e5ff');
    $simWidth    = min(100, $similarity);
@endphp

<div wire:poll.5s style="background:#030d1a;min-height:100vh;position:relative;overflow:hidden;font-family:'Courier New',monospace;color:#fff;">

    <style>
        @keyframes hud-scan {
            0%   { top: -4px; opacity: .9; }
            48%  { opacity: .5; }
            50%  { top: calc(100% + 4px); opacity: .9; }
            50.01% { top: -4px; }
            100% { top: -4px; opacity: .9; }
        }
        @keyframes hud-blink {
            0%,100% { opacity:1; }
            50%      { opacity:0; }
        }
        @keyframes hud-pulse {
            0%,100% { opacity:1; r:1.4; }
            50%      { opacity:.3; r:.7; }
        }
        @keyframes hud-bar {
            0%,100% { opacity:.3; transform:scaleY(.5); }
            50%      { opacity:1; transform:scaleY(1); }
        }
        @keyframes hud-fadein {
            from { opacity:0; transform:translateY(6px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes hud-corner-glow {
            0%,100% { box-shadow:0 0 6px #00e5ff60; }
            50%      { box-shadow:0 0 18px #00e5ffcc; }
        }
        @keyframes hud-rotate {
            from { transform:rotate(0deg); }
            to   { transform:rotate(360deg); }
        }
        .hud-scan-line {
            position:absolute;left:0;right:0;height:2px;
            background:linear-gradient(90deg,transparent,#00e5ff,#00e5ffaa,transparent);
            animation: hud-scan 3.5s linear infinite;
            pointer-events:none;z-index:10;
        }
        .hud-dot-pulse { animation: hud-pulse 2s ease-in-out infinite; }
        .hud-blink { animation: hud-blink 1.4s ease-in-out infinite; }
        .hud-fadein { animation: hud-fadein .5s ease both; }
        .hud-bar-block { animation: hud-bar 1.2s ease-in-out infinite; transform-origin: bottom; }
        .hud-field { animation: hud-fadein .4s ease both; }
    </style>

    {{-- ─── GRID DE FONDO ────────────────────────────────── --}}
    <div style="position:absolute;inset:0;pointer-events:none;
        background-image:linear-gradient(rgba(0,229,255,.04) 1px,transparent 1px),
                         linear-gradient(90deg,rgba(0,229,255,.04) 1px,transparent 1px);
        background-size:44px 44px;">
    </div>

    {{-- Viñeta esquinas --}}
    <div style="position:absolute;inset:0;pointer-events:none;
        background:radial-gradient(ellipse at center,transparent 55%,#030d1a 100%);"></div>

    <div style="position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;padding:1.5rem;">

        {{-- ══════════ HEADER ══════════ --}}
        <header style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">

            {{-- Marca izquierda --}}
            <div style="display:flex;align-items:center;gap:1rem;">
                {{-- Ícono HUD --}}
                <div style="width:48px;height:48px;border:1px solid #00e5ff;border-radius:4px;
                    display:flex;align-items:center;justify-content:center;
                    background:rgba(0,229,255,.05);
                    box-shadow:0 0 12px #00e5ff40,inset 0 0 8px rgba(0,229,255,.05);">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00e5ff" stroke-width="1.5">
                        <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2z"/>
                        <path d="M3 20c0-4 4-7 9-7s9 3 9 7"/>
                        <path d="M2 8h2M20 8h2M12 2v2M12 20v2"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:.75rem;letter-spacing:.25em;color:#00e5ff;font-weight:700;text-transform:uppercase;">
                        Reconocimiento Biométrico
                    </div>
                    <div style="font-size:.6rem;letter-spacing:.2em;color:#00e5ff60;text-transform:uppercase;margin-top:2px;">
                        Sistema de Identificación Inteligente
                    </div>
                </div>
            </div>

            {{-- Institución derecha --}}
            <div style="text-align:right;">
                <div style="font-size:1rem;font-weight:700;color:#fff;letter-spacing:.05em;">
                    {{ strtoupper($institution->name) }}
                </div>
                @if($institution->event)
                <div style="font-size:.6rem;letter-spacing:.2em;color:#00e5ff80;text-transform:uppercase;margin-top:2px;">
                    {{ $institution->event }}
                </div>
                @endif
                <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;margin-top:6px;">
                    <div class="hud-blink" style="width:8px;height:8px;border-radius:50%;background:#00ff88;
                        box-shadow:0 0 6px #00ff88;"></div>
                    <span style="font-size:.6rem;letter-spacing:.2em;color:#00ff88;text-transform:uppercase;">En línea</span>
                    <span style="font-size:.6rem;color:#00e5ff40;margin-left:8px;">
                        POLL 5s
                    </span>
                </div>
            </div>
        </header>

        {{-- Línea separadora --}}
        <div style="height:1px;background:linear-gradient(90deg,transparent,#00e5ff,#00e5ff60,transparent);margin-bottom:1.5rem;"></div>

        {{-- ══════════ CONTENIDO PRINCIPAL ══════════ --}}
        <div style="flex:1;display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">

            {{-- ─── PANEL IZQUIERDO: FOTO ─── --}}
            <div style="display:flex;flex-direction:column;align-items:center;gap:1rem;">

                <div style="font-size:.6rem;letter-spacing:.25em;color:#00e5ff80;text-transform:uppercase;
                    display:flex;align-items:center;gap:8px;align-self:flex-start;">
                    <div style="width:3px;height:14px;background:#00e5ff;"></div>
                    Análisis Facial
                </div>

                {{-- MARCO DE FOTO --}}
                <div style="position:relative;width:280px;height:360px;">

                    {{-- Borde con glow --}}
                    <div style="position:absolute;inset:0;border:1px solid #00e5ff;
                        box-shadow:0 0 0 1px #00e5ff40,0 0 20px #00e5ff30,inset 0 0 20px rgba(0,229,255,.04);
                        pointer-events:none;z-index:5;"></div>

                    {{-- Esquinas decorativas --}}
                    <div style="position:absolute;top:0;left:0;width:20px;height:20px;
                        border-top:2px solid #00e5ff;border-left:2px solid #00e5ff;z-index:6;
                        box-shadow:-2px -2px 8px #00e5ff60;"></div>
                    <div style="position:absolute;top:0;right:0;width:20px;height:20px;
                        border-top:2px solid #00e5ff;border-right:2px solid #00e5ff;z-index:6;
                        box-shadow:2px -2px 8px #00e5ff60;"></div>
                    <div style="position:absolute;bottom:0;left:0;width:20px;height:20px;
                        border-bottom:2px solid #00e5ff;border-left:2px solid #00e5ff;z-index:6;
                        box-shadow:-2px 2px 8px #00e5ff60;"></div>
                    <div style="position:absolute;bottom:0;right:0;width:20px;height:20px;
                        border-bottom:2px solid #00e5ff;border-right:2px solid #00e5ff;z-index:6;
                        box-shadow:2px 2px 8px #00e5ff60;"></div>

                    {{-- FOTO o PLACEHOLDER --}}
                    @if($isMatch && $photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto identificada"
                            style="width:100%;height:100%;object-fit:cover;display:block;" />
                    @else
                        <div style="width:100%;height:100%;background:linear-gradient(160deg,#0a1628,#050f1e);
                            display:flex;align-items:center;justify-content:center;">
                            <svg width="80" height="100" viewBox="0 0 80 100" fill="none" style="opacity:.18;">
                                <ellipse cx="40" cy="32" rx="22" ry="26" stroke="#00e5ff" stroke-width="1.5"/>
                                <path d="M5 98c0-22 15-35 35-35s35 13 35 35" stroke="#00e5ff" stroke-width="1.5"/>
                            </svg>
                        </div>
                    @endif

                    {{-- OVERLAY SVG: MALLA FACIAL --}}
                    @if($hasLog)
                    <svg viewBox="0 0 100 128" preserveAspectRatio="none"
                        style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:3;
                            {{ $isMatch ? 'opacity:.8' : 'opacity:.35' }}">

                        {{-- Líneas de la malla (primero, detrás de los puntos) --}}
                        <g stroke="#00e5ff" stroke-width=".35" fill="none" opacity=".45">
                            {{-- Contorno superior --}}
                            <polyline points="14,24 50,7 86,24"/>
                            {{-- Contorno cara --}}
                            <polyline points="14,24 13,50 17,76 30,90 50,95 70,90 83,76 87,50 86,24"/>
                            {{-- Cejas --}}
                            <polyline points="22,31 30,26 40,25"/>
                            <polyline points="60,25 70,26 78,31"/>
                            {{-- Ojos --}}
                            <polyline points="22,38 32,36 41,38"/>
                            <polyline points="59,38 68,36 78,38"/>
                            {{-- Nariz --}}
                            <line x1="50" y1="44" x2="50" y2="57"/>
                            <polyline points="50,57 43,59 50,57 57,59"/>
                            {{-- Boca --}}
                            <polyline points="37,70 50,67 63,70"/>
                            <polyline points="37,70 50,74 63,70"/>
                            {{-- Conexiones ceja→nariz --}}
                            <polyline points="40,25 50,44 60,25" opacity=".3"/>
                            {{-- Ojo→mandíbula --}}
                            <line x1="22" y1="38" x2="13" y2="50" opacity=".3"/>
                            <line x1="78" y1="38" x2="87" y2="50" opacity=".3"/>
                            {{-- Nariz→boca --}}
                            <line x1="43" y1="59" x2="37" y2="70" opacity=".3"/>
                            <line x1="57" y1="59" x2="63" y2="70" opacity=".3"/>
                        </g>

                        {{-- Puntos estáticos --}}
                        <g fill="#00e5ff">
                            {{-- Forehead / temple --}}
                            <circle cx="50" cy="7"  r="1"/>
                            <circle cx="14" cy="24" r=".9"/>
                            <circle cx="86" cy="24" r=".9"/>
                            {{-- Cejas --}}
                            <circle cx="22" cy="31" r=".8"/>
                            <circle cx="30" cy="26" r=".8"/>
                            <circle cx="40" cy="25" r=".8"/>
                            <circle cx="60" cy="25" r=".8"/>
                            <circle cx="70" cy="26" r=".8"/>
                            <circle cx="78" cy="31" r=".8"/>
                            {{-- Ojos esquinas --}}
                            <circle cx="22" cy="38" r=".9"/>
                            <circle cx="41" cy="38" r=".9"/>
                            <circle cx="59" cy="38" r=".9"/>
                            <circle cx="78" cy="38" r=".9"/>
                            {{-- Nariz --}}
                            <circle cx="50" cy="44" r=".9"/>
                            <circle cx="43" cy="59" r=".9"/>
                            <circle cx="57" cy="59" r=".9"/>
                            {{-- Boca esquinas --}}
                            <circle cx="37" cy="70" r=".9"/>
                            <circle cx="63" cy="70" r=".9"/>
                            {{-- Mandíbula --}}
                            <circle cx="13" cy="50" r=".8"/>
                            <circle cx="87" cy="50" r=".8"/>
                            <circle cx="17" cy="76" r=".8"/>
                            <circle cx="83" cy="76" r=".8"/>
                            {{-- Mentón --}}
                            <circle cx="30" cy="90" r=".8"/>
                            <circle cx="70" cy="90" r=".8"/>
                            <circle cx="50" cy="95" r=".9"/>
                        </g>

                        {{-- Puntos pulsantes (key points) --}}
                        <circle cx="32" cy="36" r="1.4" fill="#00e5ff" class="hud-dot-pulse"/>
                        <circle cx="68" cy="36" r="1.4" fill="#00e5ff" class="hud-dot-pulse" style="animation-delay:.6s"/>
                        <circle cx="50" cy="57" r="1.3" fill="#00ffcc" class="hud-dot-pulse" style="animation-delay:1s"/>
                        <circle cx="50" cy="67" r="1.3" fill="#00ffcc" class="hud-dot-pulse" style="animation-delay:1.4s"/>
                        <circle cx="50" cy="95" r="1.3" fill="#00e5ff" class="hud-dot-pulse" style="animation-delay:.3s"/>

                        {{-- Cruz retícula en centro de ojos --}}
                        <g stroke="#00ffcc" stroke-width=".4" opacity=".6">
                            <line x1="29" y1="36" x2="35" y2="36"/>
                            <line x1="32" y1="33" x2="32" y2="39"/>
                            <line x1="65" y1="36" x2="71" y2="36"/>
                            <line x1="68" y1="33" x2="68" y2="39"/>
                        </g>
                    </svg>
                    @endif

                    {{-- Línea de escaneo --}}
                    <div class="hud-scan-line"></div>

                    {{-- Estado en esquina --}}
                    <div style="position:absolute;top:8px;left:8px;font-size:.55rem;letter-spacing:.2em;
                        text-transform:uppercase;z-index:7;
                        color:{{ $isMatch ? '#00ff88' : ($hasLog ? '#ff2d55' : '#00e5ff80') }};">
                        {{ $isMatch ? 'Identificado' : ($hasLog ? 'No coincide' : 'En espera') }}
                    </div>

                    {{-- ID del log en esquina inferior --}}
                    @if($hasLog)
                    <div style="position:absolute;bottom:8px;right:8px;font-size:.5rem;letter-spacing:.12em;
                        color:#00e5ff40;z-index:7;text-transform:uppercase;">
                        LOG #{{ $lastLog->id }}
                    </div>
                    @endif
                </div>

                {{-- BARRA DE CONFIANZA --}}
                <div style="width:280px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:.6rem;letter-spacing:.2em;color:#00e5ff60;text-transform:uppercase;">
                            Confidencia
                        </span>
                        <span style="font-size:.75rem;letter-spacing:.1em;font-weight:700;
                            color:{{ $isMatch ? '#00ff88' : '#ff2d55' }};">
                            {{ $hasLog ? number_format($similarity, 1) . '%' : '—' }}
                        </span>
                    </div>
                    <div style="height:4px;background:#0a1628;border-radius:9px;overflow:hidden;
                        border:1px solid #00e5ff20;">
                        <div style="height:100%;border-radius:9px;transition:width 1s ease;
                            width:{{ $simWidth }}%;
                            background:linear-gradient(90deg,#00e5ff,{{ $isMatch ? '#00ff88' : '#ff2d55' }});
                            box-shadow:0 0 8px {{ $isMatch ? '#00ff8880' : '#ff2d5580' }};"></div>
                    </div>
                </div>

                {{-- BLOQUES DE ESCANEO --}}
                <div style="width:280px;">
                    <div style="display:flex;gap:3px;height:12px;margin-bottom:4px;align-items:flex-end;">
                        @for($i = 0; $i < 16; $i++)
                        <div class="hud-bar-block" style="flex:1;border-radius:2px;
                            background:rgba(0,229,255,{{ $i < 11 ? '.7' : '.15' }});
                            animation-delay:{{ $i * 0.08 }}s;"></div>
                        @endfor
                    </div>
                    <div style="font-size:.55rem;letter-spacing:.25em;color:#00e5ff50;text-transform:uppercase;">
                        Escaneando...
                    </div>
                </div>

            </div>

            {{-- ─── PANEL DERECHO: IDENTIDAD ─── --}}
            <div style="display:flex;flex-direction:column;gap:1.25rem;">

                {{-- ── BANNER DE ESTADO ── --}}
                @if($isMatch)
                <div class="hud-fadein" style="border:1px solid #00ff88;border-radius:6px;padding:1rem 1.25rem;
                    background:rgba(0,255,136,.04);
                    box-shadow:0 0 24px rgba(0,255,136,.12),inset 0 0 12px rgba(0,255,136,.03);
                    display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:50%;border:2px solid #00ff88;
                            display:flex;align-items:center;justify-content:center;
                            box-shadow:0 0 12px #00ff8860,inset 0 0 8px rgba(0,255,136,.1);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                stroke="#00ff88" stroke-width="2.5" stroke-linecap="round">
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:.75rem;font-weight:700;letter-spacing:.2em;
                                color:#00ff88;text-transform:uppercase;">Identidad Confirmada</div>
                            <div style="font-size:.6rem;letter-spacing:.2em;color:#00ff8870;
                                text-transform:uppercase;margin-top:2px;">Coincidencia Encontrada</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:2.5rem;font-weight:900;color:#00ff88;line-height:1;
                            text-shadow:0 0 20px #00ff88;">
                            {{ number_format($similarity, 0) }}<span style="font-size:1rem;">%</span>
                        </div>
                        <div style="font-size:.55rem;letter-spacing:.2em;color:#00ff8870;
                            text-transform:uppercase;margin-top:2px;">Similaridad</div>
                    </div>
                </div>

                @elseif($hasLog)
                <div class="hud-fadein" style="border:1px solid #ff2d55;border-radius:6px;padding:1rem 1.25rem;
                    background:rgba(255,45,85,.04);
                    box-shadow:0 0 24px rgba(255,45,85,.12);
                    display:flex;align-items:center;gap:12px;">
                    <div style="width:44px;height:44px;border-radius:50%;border:2px solid #ff2d55;
                        display:flex;align-items:center;justify-content:center;
                        box-shadow:0 0 12px #ff2d5560;flex-shrink:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="#ff2d55" stroke-width="2.5" stroke-linecap="round">
                            <path d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:.75rem;font-weight:700;letter-spacing:.2em;
                            color:#ff2d55;text-transform:uppercase;">No Identificado</div>
                        <div style="font-size:.6rem;letter-spacing:.15em;color:#ff2d5570;
                            text-transform:uppercase;margin-top:2px;">
                            {{ $resp['message'] ?? 'Sin coincidencia en la base de datos' }}
                        </div>
                    </div>
                </div>

                @else
                <div style="border:1px solid #00e5ff30;border-radius:6px;padding:1rem 1.25rem;
                    background:rgba(0,229,255,.02);
                    display:flex;align-items:center;gap:12px;">
                    <div class="hud-blink" style="width:44px;height:44px;border-radius:50%;
                        border:1px solid #00e5ff40;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="#00e5ff60" stroke-width="1.5" stroke-linecap="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 3"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:.75rem;font-weight:700;letter-spacing:.2em;
                            color:#00e5ff60;text-transform:uppercase;">En Espera</div>
                        <div style="font-size:.6rem;letter-spacing:.15em;color:#00e5ff30;
                            text-transform:uppercase;margin-top:2px;">Aguardando validaciones...</div>
                    </div>
                </div>
                @endif

                {{-- ── CAMPOS DE DATOS ── --}}
                <div style="display:flex;flex-direction:column;">

                    @php
                    $fields = [
                        [
                            'icon'  => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                            'label' => 'Nombre',
                            'value' => $names,
                            'big'   => true,
                        ],
                        [
                            'icon'  => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 10h2M16 14h2M6 10h6M6 14h4"/>',
                            'label' => 'Documento',
                            'value' => $docNumber,
                            'big'   => true,
                            'mono'  => true,
                        ],
                        [
                            'icon'  => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>',
                            'label' => 'Institución',
                            'value' => $institution->name,
                            'big'   => false,
                        ],
                        [
                            'icon'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
                            'label' => 'Evento',
                            'value' => $event,
                            'big'   => false,
                        ],
                        [
                            'icon'  => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                            'label' => 'Fecha de Registro',
                            'value' => $createdAt,
                            'big'   => false,
                            'mono'  => true,
                        ],
                        [
                            'icon'  => '<path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.1-1.1m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                            'label' => 'Resuelto Por',
                            'value' => $resolvedBy,
                            'big'   => false,
                            'mono'  => true,
                        ],
                        [
                            'icon'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
                            'label' => 'Última Validación',
                            'value' => $validatedAt?->diffForHumans(),
                            'big'   => false,
                        ],
                    ];
                    @endphp

                    @foreach($fields as $i => $field)
                        @if($field['value'])
                        <div class="hud-field"
                            style="display:flex;align-items:center;gap:14px;padding:.85rem 0;
                                border-bottom:1px solid #ffffff08;
                                animation-delay:{{ $i * 0.05 }}s;">
                            <div style="width:34px;height:34px;border-radius:4px;flex-shrink:0;
                                border:1px solid #00e5ff25;
                                background:rgba(0,229,255,.04);
                                display:flex;align-items:center;justify-content:center;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="#00e5ff" stroke-width="1.5" stroke-linecap="round">
                                    {!! $field['icon'] !!}
                                </svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.55rem;letter-spacing:.22em;color:#00e5ff70;
                                    text-transform:uppercase;margin-bottom:3px;">
                                    {{ $field['label'] }}
                                </div>
                                <div style="color:#fff;font-weight:{{ ($field['big'] ?? false) ? '600' : '400' }};
                                    font-size:{{ ($field['big'] ?? false) ? '1.15rem' : '.9rem' }};
                                    font-family:{{ ($field['mono'] ?? false) ? '\'Courier New\',monospace' : 'inherit' }};
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $field['value'] }}
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach

                </div>

            </div>
        </div>

        {{-- ══════════ FOOTER: ESTADÍSTICAS ══════════ --}}
        <div style="margin-top:1.5rem;">
            <div style="height:1px;background:linear-gradient(90deg,transparent,#00e5ff40,transparent);
                margin-bottom:1.25rem;"></div>

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;text-align:center;">

                @php
                $statItems = [
                    ['label' => 'Total',    'value' => number_format($stats['total']),        'color' => '#fff'],
                    ['label' => 'Exitosas', 'value' => number_format($stats['matched']),      'color' => '#00ff88'],
                    ['label' => 'Fallidas', 'value' => number_format($stats['failed']),       'color' => '#ff2d55'],
                    ['label' => 'Éxito',    'value' => $stats['success_rate'] . '%',          'color' => '#00e5ff'],
                    ['label' => 'Hoy',      'value' => number_format($stats['today']),        'color' => '#fbbf24'],
                ];
                @endphp

                @foreach($statItems as $j => $s)
                <div style="padding:.75rem;border:1px solid #00e5ff15;border-radius:6px;
                    background:rgba(0,229,255,.02);position:relative;overflow:hidden;">
                    {{-- decoración arriba --}}
                    <div style="position:absolute;top:0;left:25%;right:25%;height:1px;
                        background:{{ $s['color'] }};opacity:.4;"></div>
                    <div style="font-size:1.75rem;font-weight:900;color:{{ $s['color'] }};
                        line-height:1;text-shadow:0 0 12px {{ $s['color'] }}60;">
                        {{ $s['value'] }}
                    </div>
                    <div style="font-size:.55rem;letter-spacing:.22em;color:#00e5ff50;
                        text-transform:uppercase;margin-top:6px;">
                        {{ $s['label'] }}
                    </div>
                </div>
                @endforeach

            </div>

            {{-- Pie de página --}}
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem;">
                <div style="font-size:.55rem;letter-spacing:.2em;color:#00e5ff25;text-transform:uppercase;">
                    Sistema de Validación Biométrica · © {{ date('Y') }}
                </div>
                <div style="font-size:.55rem;letter-spacing:.15em;color:#00e5ff25;text-transform:uppercase;">
                    Actualización automática cada 5 seg
                </div>
            </div>
        </div>

    </div>
</div>
