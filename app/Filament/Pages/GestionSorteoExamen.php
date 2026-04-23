<?php

namespace App\Filament\Pages;

use App\Models\Asignatura;
use App\Models\Examen;
use App\Models\Pregunta;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GestionSorteoExamen extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Sorteo de Examen';

    protected static ?string $title = 'Gestión de sorteo de examen';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión de Exámenes';

    protected string $view = 'filament.pages.gestion-sorteo-examen';

    public ?int $examenId = null;

    public ?int $asignaturaId = null;

    public ?string $capitulo = null;

    public ?string $tema = null;

    public ?string $gradoDificultad = null;

    public int $cantidad = 1;

    /**
     * @var array<int, string>
     */
    public array $asignaturas = [];

    /**
     * @var array<int, string>
     */
    public array $examenes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sorteoTemporal = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $examenSorteado = [];

    public function mount(): void
    {
        $this->loadCatalogos();
        $this->refreshSorteoTemporal();
        $this->refreshExamenSorteado();
    }

    public function updatedExamenId(): void
    {
        $this->refreshExamenSorteado();
    }

    public function loadCatalogos(): void
    {
        $this->asignaturas = Asignatura::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();

        $this->examenes = Examen::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'proceso'])
            ->mapWithKeys(fn (Examen $examen): array => [
                $examen->id => $examen->nombre . ' · Proceso: ' . ($examen->proceso ?: '-'),
            ])
            ->all();
    }

    public function refreshSorteoTemporal(): void
    {
        $this->sorteoTemporal = DB::table('sorteo_temporal')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function refreshExamenSorteado(): void
    {
        if (! $this->examenId) {
            $this->examenSorteado = [];

            return;
        }

        $this->examenSorteado = DB::table('examen_sorteado as es')
            ->leftJoin('asignaturas as a', 'a.id', '=', 'es.asignatura_id')
            ->where('es.examen_id', $this->examenId)
            ->orderByDesc('es.created_at')
            ->select('es.*', 'a.nombre as asignatura_nombre')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function sortearPreguntas(): void
    {
        $validator = Validator::make([
            'asignatura_id' => $this->asignaturaId,
            'capitulo' => $this->capitulo,
            'grado_dificultad' => $this->gradoDificultad,
            'cantidad' => $this->cantidad,
        ], [
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'capitulo' => ['required', 'regex:/^[0-9]{2,}$/'],
            'grado_dificultad' => ['required', 'in:Facil,Normal,Dificil'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'asignatura_id' => 'asignatura',
            'capitulo' => 'capítulo',
            'grado_dificultad' => 'grado de dificultad',
        ], [
            'capitulo.regex' => 'El capítulo debe tener formato numérico como 01, 02, 10, 25, etc.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());

            Notification::make()
                ->title('Datos inválidos en el sorteo')
                ->body($validator->errors()->first())
                ->danger()
                ->send();

            return;
        }

        $query = Pregunta::query()
            ->with('asignatura')
            ->where('asignatura_id', $this->asignaturaId)
            ->where('capitulo', $this->capitulo)
            ->where('grado_dificultad', $this->gradoDificultad)
            ->when($this->tema, fn ($q) => $q->where('tema', $this->tema));

        $idsTemporales = DB::table('sorteo_temporal')->pluck('idpregunta')->all();

        if (! empty($idsTemporales)) {
            $query->whereNotIn('idpregunta', $idsTemporales);
        }

        $query->whereNotIn('idpregunta', DB::table('preguntas_sorteadas')->select('id_pregunta'));

        $preguntas = $query
            ->inRandomOrder()
            ->limit($this->cantidad)
            ->get();

        if ($preguntas->isEmpty()) {
            Notification::make()
                ->title('No hay preguntas disponibles para esos criterios')
                ->warning()
                ->send();

            return;
        }

        $now = now();

        foreach ($preguntas as $pregunta) {
            DB::table('sorteo_temporal')->insert([
                'idpregunta' => $pregunta->idpregunta,
                'asignatura' => $pregunta->asignatura?->nombre,
                'grado_dificultad' => $pregunta->grado_dificultad,
                'capitulo' => $pregunta->capitulo,
                'ruta' => $pregunta->ruta,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->refreshSorteoTemporal();

        Notification::make()
            ->title('Sorteo ejecutado')
            ->body('Se agregaron '.$preguntas->count().' preguntas a la tabla temporal.')
            ->success()
            ->send();
    }

    public function quitarDelSorteo(int $idPregunta): void
    {
        DB::table('sorteo_temporal')
            ->where('idpregunta', $idPregunta)
            ->delete();

        $this->refreshSorteoTemporal();

        Notification::make()
            ->title('Pregunta eliminada del sorteo temporal')
            ->success()
            ->send();
    }

    public function confirmarExamen(): void
    {
        Validator::make([
            'examen_id' => $this->examenId,
        ], [
            'examen_id' => ['required', 'exists:examenes,id'],
        ], [
            'examen_id' => 'examen',
        ])->validate();

        $preguntas = Pregunta::query()
            ->whereIn('idpregunta', DB::table('sorteo_temporal')->pluck('idpregunta')->all())
            ->get();

        if ($preguntas->isEmpty()) {
            Notification::make()
                ->title('No hay preguntas en sorteo temporal para confirmar')
                ->warning()
                ->send();

            return;
        }

        DB::transaction(function () use ($preguntas): void {
            $examen = Examen::query()->find($this->examenId);

            foreach ($preguntas as $pregunta) {
                DB::table('examen_sorteado')->updateOrInsert(
                    [
                        'examen_id' => $this->examenId,
                        'idpregunta' => $pregunta->idpregunta,
                    ],
                    [
                        'codificacion' => $pregunta->codificacion,
                        'asignatura_id' => $pregunta->asignatura_id,
                        'capitulo' => $pregunta->capitulo,
                        'tema' => $pregunta->tema,
                        'sub_tema' => $pregunta->sub_tema,
                        'grado_dificultad' => $pregunta->grado_dificultad,
                        'clave' => $pregunta->clave,
                        'proceso' => $examen?->proceso ?: $pregunta->proceso,
                        'ruta' => $pregunta->ruta,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::table('sorteo_temporal')->truncate();
        });

        $this->refreshSorteoTemporal();
        $this->refreshExamenSorteado();

        Notification::make()
            ->title('Preguntas confirmadas')
            ->body('Las preguntas pasaron a examen_sorteado y se limpió el sorteo temporal.')
            ->success()
            ->send();
    }

    public function refrescarYEnviarPreguntas(): void
    {
        Validator::make([
            'examen_id' => $this->examenId,
        ], [
            'examen_id' => ['required', 'exists:examenes,id'],
        ], [
            'examen_id' => 'examen',
        ])->validate();

        $registrosExamenSorteado = DB::table('examen_sorteado')
            ->where('examen_id', $this->examenId)
            ->get()
            ->all();

        if (empty($registrosExamenSorteado)) {
            Notification::make()
                ->title('No hay preguntas en examen_sorteado para enviar')
                ->warning()
                ->send();

            return;
        }

        $now = now();

        DB::transaction(function () use ($registrosExamenSorteado, $now): void {
            $ids = collect($registrosExamenSorteado)
                ->pluck('idpregunta')
                ->unique()
                ->values()
                ->all();

            $payloadSorteadas = collect($ids)
                ->map(fn ($idPregunta): array => [
                    'id_pregunta' => $idPregunta,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            $payloadHistorico = collect($registrosExamenSorteado)
                ->map(fn ($row): array => [
                    'examen_id' => $row->examen_id,
                    'idpregunta' => $row->idpregunta,
                    'codificacion' => $row->codificacion,
                    'asignatura_id' => $row->asignatura_id,
                    'capitulo' => $row->capitulo,
                    'tema' => $row->tema,
                    'sub_tema' => $row->sub_tema,
                    'grado_dificultad' => $row->grado_dificultad,
                    'clave' => $row->clave,
                    'proceso' => $row->proceso,
                    'ruta' => $row->ruta,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            DB::table('examenes_historico')->insert($payloadHistorico);

            DB::table('preguntas_sorteadas')->upsert(
                $payloadSorteadas,
                ['id_pregunta'],
                ['updated_at']
            );

            DB::table('examen_sorteado')
                ->where('examen_id', $this->examenId)
                ->delete();
            DB::table('sorteo_temporal')->truncate();
        });

        $this->refreshSorteoTemporal();
        $this->refreshExamenSorteado();

        Notification::make()
            ->title('Examen confirmado')
            ->body('Las preguntas del examen pasaron a preguntas_sorteadas y se registraron en examenes_historico.')
            ->success()
            ->send();
    }

    public function quitarDeExamenSorteado(int $idPregunta): void
    {
        if (! $this->examenId) {
            Notification::make()
                ->title('Selecciona un examen primero')
                ->warning()
                ->send();

            return;
        }

        DB::table('examen_sorteado')
            ->where('examen_id', $this->examenId)
            ->where('idpregunta', $idPregunta)
            ->delete();

        $this->refreshExamenSorteado();

        Notification::make()
            ->title('Pregunta removida de examen_sorteado')
            ->success()
            ->send();
    }

    public function getCantidadEnSorteoProperty(): int
    {
        return count($this->sorteoTemporal);
    }

    public function getCantidadEnExamenSorteadoProperty(): int
    {
        return count($this->examenSorteado);
    }
}
