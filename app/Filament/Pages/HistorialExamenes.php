<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class HistorialExamenes extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Histórico de Exámenes';

    protected static ?string $title = 'Histórico de exámenes';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión de Exámenes';

    protected string $view = 'filament.pages.historial-examenes';

    public ?int $examenSeleccionadoId = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $resumenExamenes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $detallePreguntas = [];

    public ?array $infoExamenSeleccionado = null;

    public function mount(): void
    {
        $this->cargarResumenExamenes();
    }

    public function cargarResumenExamenes(): void
    {
        $this->resumenExamenes = DB::table('examenes_historico as eh')
            ->join('examenes as e', 'e.id', '=', 'eh.examen_id')
            ->select('e.id', 'e.nombre', 'e.proceso', DB::raw('count(*) as total_preguntas'))
            ->groupBy('e.id', 'e.nombre', 'e.proceso')
            ->orderByDesc('e.id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function verDetalleExamen(int $examenId): void
    {
        $this->examenSeleccionadoId = $examenId;

        $examen = DB::table('examenes')
            ->where('id', $examenId)
            ->select('id', 'nombre', 'proceso')
            ->first();

        $this->infoExamenSeleccionado = $examen ? (array) $examen : null;

        $this->detallePreguntas = DB::table('examenes_historico as eh')
            ->leftJoin('asignaturas as a', 'a.id', '=', 'eh.asignatura_id')
            ->where('eh.examen_id', $examenId)
            ->select('eh.*', 'a.nombre as asignatura_nombre')
            ->orderBy('eh.id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function extraerPreguntas(): void
    {
        if (! $this->examenSeleccionadoId) {
            Notification::make()
                ->title('Selecciona un examen')
                ->warning()
                ->send();

            return;
        }

        if (empty($this->detallePreguntas)) {
            Notification::make()
                ->title('No hay preguntas para extraer')
                ->warning()
                ->send();

            return;
        }

        $destinoBase = $this->obtenerDirectorioSorteoPredeterminado();

        try {
            if (! File::exists($destinoBase)) {
                if (! @mkdir($destinoBase, 0775, true) && ! is_dir($destinoBase)) {
                    throw new RuntimeException('No se pudo crear el directorio destino: '.$destinoBase);
                }
            }

            if (! is_dir($destinoBase)) {
                throw new RuntimeException('La ruta destino no es una carpeta válida: '.$destinoBase);
            }

            if (! is_writable($destinoBase)) {
                throw new RuntimeException('Sin permisos de escritura en destino: '.$destinoBase.'. Usa el botón "Descargar ZIP" o da permisos de escritura al servidor web sobre esa carpeta.');
            }

            $ok = 0;
            $errores = [];

            foreach ($this->detallePreguntas as $pregunta) {
                $asignatura = trim((string) ($pregunta['asignatura_nombre'] ?? 'Sin_Asignatura'));
                $nombreAsignatura = Str::of($asignatura)->slug('_')->toString() ?: 'sin_asignatura';

                $origenRaw = trim((string) ($pregunta['ruta'] ?? ''));
                $origen = $this->resolverRutaOrigen($origenRaw);

                if ($origen === '' || ! File::isDirectory($origen)) {
                    $errores[] = 'ID '.$pregunta['idpregunta'].': ruta no encontrada o no es carpeta ('.$origenRaw.').';
                    continue;
                }

                $nombreCarpeta = basename($origen);
                $destinoAsignatura = $destinoBase.DIRECTORY_SEPARATOR.$nombreAsignatura;
                $destinoFinal = $destinoAsignatura.DIRECTORY_SEPARATOR.$nombreCarpeta;

                if (File::exists($destinoFinal)) {
                    $destinoFinal .= '_p'.$pregunta['idpregunta'];
                }

                if (! is_dir($destinoAsignatura) && ! @mkdir($destinoAsignatura, 0775, true) && ! is_dir($destinoAsignatura)) {
                    throw new RuntimeException('No se pudo crear carpeta por asignatura: '.$destinoAsignatura);
                }

                $this->copiarDirectorioRecursivo($origen, $destinoFinal);
                $ok++;
            }

            $msg = "Extracción finalizada. Carpetas copiadas: {$ok}.";

            if (! empty($errores)) {
                $msg .= ' Errores: '.count($errores).'. Ejemplo: '.$errores[0];
            }

            Notification::make()
                ->title('Extracción de preguntas completada')
                ->body($msg.' Carpeta destino: '.$destinoBase)
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Error al extraer preguntas')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function obtenerDirectorioSorteoPredeterminado(): string
    {
        // Linux del entorno actual
        if (DIRECTORY_SEPARATOR === '/') {
            if (is_dir('/home/vboxuser/Desktop')) {
                return '/home/vboxuser/Desktop/SORTEO';
            }

            return storage_path('app/SORTEO');
        }

        // Windows
        $userProfile = getenv('USERPROFILE') ?: 'C:\\Users\\Public';

        return rtrim((string) $userProfile, '\\')."\\Desktop\\SORTEO";
    }

    public function descargarPreguntasZip(): ?BinaryFileResponse
    {
        if (! $this->examenSeleccionadoId) {
            Notification::make()
                ->title('Selecciona un examen')
                ->warning()
                ->send();

            return null;
        }

        if (empty($this->detallePreguntas)) {
            Notification::make()
                ->title('No hay preguntas para descargar')
                ->warning()
                ->send();

            return null;
        }

        $tmpDir = storage_path('app/tmp-extracciones');

        if (! File::exists($tmpDir)) {
            File::makeDirectory($tmpDir, 0775, true);
        }

        $nombreZip = 'examen_'.($this->infoExamenSeleccionado['nombre'] ?? $this->examenSeleccionadoId).'_'.now()->format('Ymd_His').'.zip';
        $nombreZip = Str::of($nombreZip)->slug('_')->append('.zip')->toString();
        $zipPath = $tmpDir.DIRECTORY_SEPARATOR.$nombreZip;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()
                ->title('No se pudo crear el ZIP')
                ->danger()
                ->send();

            return null;
        }

        $errores = [];
        $copiadas = 0;
        $destinosUsados = [];

        foreach ($this->detallePreguntas as $pregunta) {
            $asignatura = trim((string) ($pregunta['asignatura_nombre'] ?? 'Sin_Asignatura'));
            $nombreAsignatura = Str::of($asignatura)->slug('_')->toString() ?: 'sin_asignatura';

            $origenRaw = trim((string) ($pregunta['ruta'] ?? ''));
            $origen = $this->resolverRutaOrigen($origenRaw);

            if ($origen === '' || ! File::isDirectory($origen)) {
                $errores[] = 'ID '.$pregunta['idpregunta'].': ruta no encontrada o no es carpeta ('.$origenRaw.').';
                continue;
            }

            $nombreCarpeta = basename($origen);
            $destinoBase = $nombreAsignatura.'/'.$nombreCarpeta;
            $destino = $destinoBase;

            if (isset($destinosUsados[$destino])) {
                $destino = $destinoBase.'_p'.$pregunta['idpregunta'];
            }

            $destinosUsados[$destino] = true;

            $this->agregarDirectorioAZip($zip, $origen, $destino);
            $copiadas++;
        }

        if (! empty($errores)) {
            $zip->addFromString('_errores_extraccion.txt', implode(PHP_EOL, $errores));
        }

        $zip->close();

        if ($copiadas === 0) {
            @unlink($zipPath);

            Notification::make()
                ->title('No se pudo generar el ZIP')
                ->body($errores[0] ?? 'No se encontraron carpetas válidas para extraer.')
                ->danger()
                ->send();

            return null;
        }

        return response()->download($zipPath, $nombreZip)->deleteFileAfterSend(true);
    }

    private function normalizarRutaDestino(string $ruta): string
    {
        $ruta = trim($ruta, " \t\n\r\0\x0B\"'");

        if ($ruta === '') {
            return $ruta;
        }

        $ruta = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta);

        // Si corre en Linux/WSL y recibe ruta Windows (C:\...), intenta mapear a /mnt/c/...
        if (DIRECTORY_SEPARATOR === '/' && preg_match('~^([A-Za-z]):(?:\\\\|/)(.*)$~', $ruta, $m)) {
            $drive = strtolower($m[1]);
            $resto = str_replace('\\', '/', (string) $m[2]);

            return '/mnt/'.$drive.'/'.$resto;
        }

        return $ruta;
    }

    private function resolverRutaOrigen(string $ruta): string
    {
        $ruta = trim($ruta, " \t\n\r\0\x0B\"'");

        if ($ruta === '') {
            return '';
        }

        $candidatas = [];

        // 1) Tal cual
        $candidatas[] = $ruta;

        // 2) Normalizada al separador del SO
        $candidatas[] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta);

        // 3) Linux/WSL + ruta Windows (C:\... -> /mnt/c/...)
        if (DIRECTORY_SEPARATOR === '/' && preg_match('~^([A-Za-z]):(?:\\\\|/)(.*)$~', $ruta, $m)) {
            $drive = strtolower($m[1]);
            $resto = str_replace('\\', '/', (string) $m[2]);
            $candidatas[] = '/mnt/'.$drive.'/'.$resto;
        }

        foreach (array_unique($candidatas) as $candidata) {
            if (File::isDirectory($candidata)) {
                return $candidata;
            }
        }

        return '';
    }

    private function copiarDirectorioRecursivo(string $origen, string $destino): void
    {
        if (! is_dir($destino) && ! @mkdir($destino, 0775, true) && ! is_dir($destino)) {
            throw new RuntimeException('No se pudo crear carpeta destino: '.$destino);
        }

        foreach (File::allFiles($origen) as $file) {
            $rel = Str::after($file->getPathname(), $origen.DIRECTORY_SEPARATOR);
            $destinoArchivo = $destino.DIRECTORY_SEPARATOR.$rel;
            $destinoDir = dirname($destinoArchivo);

            if (! is_dir($destinoDir) && ! @mkdir($destinoDir, 0775, true) && ! is_dir($destinoDir)) {
                throw new RuntimeException('No se pudo crear subcarpeta destino: '.$destinoDir);
            }

            if (! @copy($file->getPathname(), $destinoArchivo)) {
                throw new RuntimeException('No se pudo copiar archivo a: '.$destinoArchivo);
            }
        }
    }

    private function agregarDirectorioAZip(ZipArchive $zip, string $origen, string $destinoBaseZip): void
    {
        $destinoBaseZip = trim(str_replace('\\', '/', $destinoBaseZip), '/');

        foreach (File::allFiles($origen) as $file) {
            $rel = Str::after($file->getPathname(), $origen.DIRECTORY_SEPARATOR);
            $rel = str_replace('\\', '/', $rel);
            $entry = $destinoBaseZip.'/'.$rel;
            $zip->addFile($file->getPathname(), $entry);
        }
    }
}
