<x-filament-panels::page>
    <style>
        .gs-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 1200px) { .gs-layout { grid-template-columns: 360px 1fr; } }
        .gs-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }
        .dark .gs-card { background: rgb(24 24 27 / 1); border-color: rgba(255, 255, 255, .12); }
        .gs-title { font-size: .95rem; font-weight: 700; margin-bottom: .2rem; }
        .gs-sub { font-size: .78rem; color: #6b7280; margin-bottom: .8rem; }
        .gs-fields { display: grid; grid-template-columns: 1fr; gap: .75rem; }
        .gs-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .gs-label { display: block; margin-bottom: .3rem; font-size: .82rem; font-weight: 600; }
        .gs-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: .9rem;
            padding: .52rem .65rem;
            background: #fff;
        }
        .dark .gs-input { background: rgba(255, 255, 255, .05); border-color: rgba(255, 255, 255, .12); }
        .gs-actions { display: grid; grid-template-columns: 1fr; gap: .5rem; }
        .gs-inline { display: flex; align-items: center; gap: .5rem; font-size: .86rem; }
        .gs-table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 12px; }
        .dark .gs-table-wrap { border-color: rgba(255, 255, 255, .12); }
        .gs-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .gs-table th, .gs-table td { padding: .65rem .75rem; border-bottom: 1px solid #f1f5f9; }
        .dark .gs-table th, .dark .gs-table td { border-bottom-color: rgba(255, 255, 255, .08); }
        .gs-table th { font-size: .72rem; text-transform: uppercase; color: #6b7280; letter-spacing: .03em; text-align: left; }
        .gs-chip {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            background: #ecfeff;
            color: #0f766e;
        }
        .gs-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .8rem; gap: .75rem; }
        .gs-muted { color: #6b7280; font-size: .82rem; }
    </style>

    <div class="gs-layout">
        <div style="display: grid; gap: 1rem; align-content: start;">
            <section class="gs-card">
                <div class="gs-title">1) Configuración de examen</div>
                <p class="gs-sub">Selecciona el examen/proceso al que confirmarás el sorteo.</p>

                <div class="gs-fields">
                    <label>
                        <span class="gs-label">Examen</span>
                        <select wire:model="examenId" class="gs-input">
                            <option value="">Selecciona examen...</option>
                            @foreach ($this->examenes as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="gs-actions">
                        <x-filament::button wire:click="confirmarExamen" color="success" class="w-full" icon="heroicon-o-check-circle">
                            Confirmar preguntas
                        </x-filament::button>

                        <x-filament::button wire:click="refrescarYEnviarPreguntas" color="warning" class="w-full" icon="heroicon-o-arrow-path">
                            Confirmar examen
                        </x-filament::button>
                    </div>
                </div>
            </section>

            <section class="gs-card">
                <div class="gs-title">2) Sorteo de preguntas</div>
                <p class="gs-sub">Filtra por clasificación y define cantidad. Capítulo y dificultad son obligatorios.</p>

                <div class="gs-fields">
                    <label>
                        <span class="gs-label">Asignatura</span>
                        <select wire:model="asignaturaId" class="gs-input">
                            <option value="">Selecciona asignatura...</option>
                            @foreach ($this->asignaturas as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('asignatura_id')
                            <div style="margin-top:.35rem; font-size:.78rem; color:#dc2626;">{{ $message }}</div>
                        @enderror
                    </label>

                    <div class="gs-row">
                        <label>
                            <span class="gs-label">Capítulo <span style="color:#dc2626">*</span></span>
                            <input type="text" wire:model="capitulo" class="gs-input" placeholder="Ej: 01, 02, 10, 25..." required />
                            @error('capitulo')
                                <div style="margin-top:.35rem; font-size:.78rem; color:#dc2626;">{{ $message }}</div>
                            @enderror
                        </label>

                        <label>
                            <span class="gs-label">Dificultad <span style="color:#dc2626">*</span></span>
                            <select wire:model="gradoDificultad" class="gs-input" required>
                                <option value="">Selecciona...</option>
                                <option value="Facil">Facil</option>
                                <option value="Normal">Normal</option>
                                <option value="Dificil">Dificil</option>
                            </select>
                            @error('grado_dificultad')
                                <div style="margin-top:.35rem; font-size:.78rem; color:#dc2626;">{{ $message }}</div>
                            @enderror
                        </label>
                    </div>

                    <label>
                        <span class="gs-label">Tema (opcional)</span>
                        <input type="text" wire:model="tema" class="gs-input" />
                    </label>

                    <label>
                        <span class="gs-label">Cantidad de preguntas</span>
                        <input type="number" min="1" wire:model="cantidad" class="gs-input" />
                        @error('cantidad')
                            <div style="margin-top:.35rem; font-size:.78rem; color:#dc2626;">{{ $message }}</div>
                        @enderror
                    </label>

                    <x-filament::button wire:click="sortearPreguntas" color="primary" class="w-full" icon="heroicon-o-sparkles">
                        Ejecutar sorteo
                    </x-filament::button>
                </div>
            </section>
        </div>

        <div style="display:grid; gap: 1rem;">
            <section class="gs-card">
                <div class="gs-header">
                    <div>
                        <div class="gs-title">3) Preguntas sorteadas (temporal)</div>
                        <div class="gs-muted">Revisa y elimina preguntas antes de confirmar el examen.</div>
                    </div>

                    <span class="gs-chip">Total: {{ $this->cantidadEnSorteo }}</span>
                </div>

                <div class="gs-table-wrap">
                    <table class="gs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Asignatura</th>
                                <th>Dificultad</th>
                                <th>Capítulo</th>
                                <th>Ruta</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->sorteoTemporal as $row)
                                <tr>
                                    <td>{{ $row['idpregunta'] }}</td>
                                    <td>{{ $row['asignatura'] ?? '-' }}</td>
                                    <td>{{ $row['grado_dificultad'] ?? '-' }}</td>
                                    <td>{{ $row['capitulo'] ?? '-' }}</td>
                                    <td>{{ $row['ruta'] ?? '-' }}</td>
                                    <td style="text-align:right;">
                                        <x-filament::button color="danger" size="xs" wire:click="quitarDelSorteo({{ $row['idpregunta'] }})">
                                            Quitar
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 1.25rem; color: #6b7280;">
                                        Aún no hay preguntas sorteadas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="gs-card">
                <div class="gs-header">
                    <div>
                        <div class="gs-title">4) Examen sorteado (editable antes de finalizar)</div>
                        <div class="gs-muted">Solo muestra las preguntas ya enviadas a examen_sorteado. Puedes quitar antes de confirmar examen.</div>
                    </div>

                    <span class="gs-chip">Total: {{ $this->cantidadEnExamenSorteado }}</span>
                </div>

                <div class="gs-table-wrap">
                    <table class="gs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Codificación</th>
                                <th>Asignatura</th>
                                <th>Dificultad</th>
                                <th>Capítulo</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->examenSorteado as $row)
                                <tr>
                                    <td>{{ $row['idpregunta'] }}</td>
                                    <td>{{ $row['codificacion'] ?? '-' }}</td>
                                    <td>{{ $row['asignatura_nombre'] ?? '-' }}</td>
                                    <td>{{ $row['grado_dificultad'] ?? '-' }}</td>
                                    <td>{{ $row['capitulo'] ?? '-' }}</td>
                                    <td style="text-align:right;">
                                        <x-filament::button color="danger" size="xs" wire:click="quitarDeExamenSorteado({{ $row['idpregunta'] }})">
                                            Quitar
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 1.25rem; color: #6b7280;">
                                        Aún no hay preguntas en examen_sorteado para el examen seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
