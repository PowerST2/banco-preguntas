<x-filament-panels::page>
    <style>
        .hx-layout { display:grid; grid-template-columns: 1fr; gap:1rem; }
        @media (min-width: 1200px) { .hx-layout { grid-template-columns: 360px 1fr; } }
        .hx-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem; box-shadow:0 1px 3px rgba(0,0,0,.06); }
        .dark .hx-card { background:rgb(24 24 27 / 1); border-color:rgba(255,255,255,.12); }
        .hx-title { font-size:.95rem; font-weight:700; margin-bottom:.15rem; }
        .hx-sub { font-size:.8rem; color:#6b7280; margin-bottom:.8rem; }
        .hx-table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:10px; }
        .dark .hx-table-wrap { border-color:rgba(255,255,255,.12); }
        .hx-table { width:100%; border-collapse:collapse; font-size:.87rem; }
        .hx-table th, .hx-table td { padding:.6rem .7rem; border-bottom:1px solid #f1f5f9; }
        .dark .hx-table th, .dark .hx-table td { border-bottom-color:rgba(255,255,255,.08); }
        .hx-table th { text-transform:uppercase; font-size:.72rem; letter-spacing:.03em; color:#6b7280; text-align:left; }
        .hx-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.2rem .55rem; font-size:.73rem; font-weight:700; background:#ecfeff; color:#155e75; }
    </style>

    <div class="hx-layout">
        <section class="hx-card">
            <div class="hx-title">Exámenes en histórico</div>
            <p class="hx-sub">Se muestra solo nombre y proceso. Haz clic en "Ver" para revisar el detalle.</p>

            <div class="hx-table-wrap">
                <table class="hx-table">
                    <thead>
                        <tr>
                            <th>Examen</th>
                            <th>Proceso</th>
                            <th style="text-align:right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->resumenExamenes as $row)
                            <tr>
                                <td>{{ $row['nombre'] }}</td>
                                <td>{{ $row['proceso'] ?: '-' }}</td>
                                <td style="text-align:right;">
                                    <x-filament::button size="xs" wire:click="verDetalleExamen({{ $row['id'] }})">
                                        Ver
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center; padding:1rem; color:#6b7280;">Sin registros en histórico.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="hx-card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap;">
                <div class="hx-title">Detalle de examen</div>

                <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                    <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="descargarPreguntasZip">
                        Descargar ZIP (Windows/Cliente)
                    </x-filament::button>

                    <x-filament::button color="warning" icon="heroicon-o-folder-open" wire:click="extraerPreguntas">
                        Extraer a carpeta SORTEO
                    </x-filament::button>
                </div>
            </div>

            @if ($this->infoExamenSeleccionado)
                <div class="hx-sub" style="margin-top:.4rem;">La extracción en servidor guarda por defecto en: /home/vboxuser/Desktop/SORTEO (o storage/app/SORTEO si no existe Desktop).</div>

                <div style="margin:.6rem 0 .9rem; display:flex; flex-wrap:wrap; gap:.5rem;">
                    <span class="hx-badge">Examen: {{ $this->infoExamenSeleccionado['nombre'] }}</span>
                    <span class="hx-badge">Proceso: {{ $this->infoExamenSeleccionado['proceso'] ?: '-' }}</span>
                    <span class="hx-badge">Total: {{ count($this->detallePreguntas) }}</span>
                </div>

                <div class="hx-table-wrap">
                    <table class="hx-table">
                        <thead>
                            <tr>
                                <th>ID pregunta</th>
                                <th>Codificación</th>
                                <th>Asignatura</th>
                                <th>Capítulo</th>
                                <th>Dificultad</th>
                                <th>Ruta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->detallePreguntas as $row)
                                <tr>
                                    <td>{{ $row['idpregunta'] }}</td>
                                    <td>{{ $row['codificacion'] ?: '-' }}</td>
                                    <td>{{ $row['asignatura_nombre'] ?: '-' }}</td>
                                    <td>{{ $row['capitulo'] ?: '-' }}</td>
                                    <td>{{ $row['grado_dificultad'] ?: '-' }}</td>
                                    <td>{{ $row['ruta'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:1rem; color:#6b7280;">Este examen no tiene preguntas históricas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="hx-sub" style="margin-top:.75rem;">Selecciona un examen del panel izquierdo para ver el detalle.</div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
