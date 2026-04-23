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

    public ?int $gradoDificultad = null;

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

    public function mount(): void
    {
        $this->loadCatalogos();
        $this->refreshSorteoTemporal();
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
            'grado_dificultad' => ['required', 'integer', 'in:1,2,3'],
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
        });

        Notification::make()
            ->title('Examen confirmado')
            ->body('Las preguntas del sorteo temporal fueron guardadas en examen_sorteado.')
            ->success()
            ->send();
    }

    public function refrescarYEnviarPreguntas(): void
    {
        $ids = DB::table('examen_sorteado')
            ->select('idpregunta')
            ->distinct()
            ->pluck('idpregunta')
            ->all();

        if (empty($ids)) {
            Notification::make()
                ->title('No hay preguntas en examen_sorteado para enviar')
                ->warning()
                ->send();

            return;
        }

        $now = now();

        DB::transaction(function () use ($ids, $now): void {
            $payload = collect($ids)
                ->map(fn ($idPregunta): array => [
                    'id_pregunta' => $idPregunta,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            DB::table('preguntas_sorteadas')->upsert(
                $payload,
                ['id_pregunta'],
                ['updated_at']
            );

            DB::table('examen_sorteado')->truncate();
            DB::table('sorteo_temporal')->truncate();
        });

        $this->refreshSorteoTemporal();

        Notification::make()
            ->title('Proceso completado')
            ->body('Se enviaron las preguntas a preguntas_sorteadas y se limpiaron las tablas temporales.')
            ->success()
            ->send();
    }

    public function getCantidadEnSorteoProperty(): int
    {
        return count($this->sorteoTemporal);
    }
}
