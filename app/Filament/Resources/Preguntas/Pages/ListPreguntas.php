<?php

namespace App\Filament\Resources\Preguntas\Pages;

use App\Filament\Resources\Preguntas\PreguntaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class ListPreguntas extends ListRecords
{
    protected static string $resource = PreguntaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importarExcel')
                ->label('Carga masiva (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    FileUpload::make('archivo')
                        ->label('Archivo Excel')
                        ->disk('local')
                        ->directory('imports/preguntas')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $archivo = $data['archivo'] ?? null;

                    if (! is_string($archivo) || $archivo === '') {
                        Notification::make()
                            ->title('Archivo inválido')
                            ->danger()
                            ->send();

                        return;
                    }

                    $rutaAbsoluta = Storage::disk('local')->path($archivo);

                    if (! file_exists($rutaAbsoluta)) {
                        Notification::make()
                            ->title('No se encontró el archivo para importar')
                            ->danger()
                            ->send();

                        return;
                    }

                    $columnasEsperadas = [
                        'idpregunta',
                        'codificacion',
                        'asignatura_id',
                        'capitulo',
                        'tema',
                        'sub_tema',
                        'grado_dificultad',
                        'clave',
                        'proceso',
                        'ruta',
                    ];

                    $errores = [];
                    $filasParaUpsert = [];
                    $codificacionesEnArchivo = [];
                    $now = now();

                    try {
                        $reader = new Reader();
                        $reader->open($rutaAbsoluta);

                        $primeraHoja = null;

                        foreach ($reader->getSheetIterator() as $sheet) {
                            $primeraHoja = $sheet;
                            break;
                        }

                        if (! $primeraHoja) {
                            Notification::make()
                                ->title('El Excel no tiene hojas')
                                ->danger()
                                ->send();

                            $reader->close();

                            return;
                        }

                        $headers = [];
                        $fila = 0;

                        foreach ($primeraHoja->getRowIterator() as $row) {
                            $fila++;
                            $valores = $row->toArray();

                            if ($fila === 1) {
                                $headers = array_map(
                                    fn ($h): string => strtolower(trim((string) $h)),
                                    $valores,
                                );

                                $faltantes = array_diff($columnasEsperadas, $headers);

                                if (! empty($faltantes)) {
                                    Notification::make()
                                        ->title('Estructura inválida del Excel')
                                        ->body('Faltan columnas: '.implode(', ', $faltantes))
                                        ->danger()
                                        ->send();

                                    $reader->close();

                                    return;
                                }

                                continue;
                            }

                            $mapa = [];

                            foreach ($headers as $index => $header) {
                                $mapa[$header] = $valores[$index] ?? null;
                            }

                            $esFilaVacia = collect($mapa)
                                ->every(fn ($valor): bool => $valor === null || trim((string) $valor) === '');

                            if ($esFilaVacia) {
                                continue;
                            }

                            $idPregunta = $this->toIntOrNull($mapa['idpregunta'] ?? null);
                            $codificacion = $this->toNullableString($mapa['codificacion'] ?? null);
                            $asignaturaId = $this->toIntOrNull($mapa['asignatura_id'] ?? null);
                            $capitulo = trim((string) ($mapa['capitulo'] ?? ''));
                            $dificultad = $this->parseDificultad($mapa['grado_dificultad'] ?? null);

                            if (! $idPregunta) {
                                $errores[] = "Fila {$fila}: idpregunta inválido.";
                                continue;
                            }

                            if (! $codificacion) {
                                $errores[] = "Fila {$fila}: codificacion es obligatoria.";
                                continue;
                            }

                            $claveCod = mb_strtolower($codificacion);

                            if (isset($codificacionesEnArchivo[$claveCod]) && $codificacionesEnArchivo[$claveCod] !== $idPregunta) {
                                $errores[] = "Fila {$fila}: codificacion duplicada dentro del archivo ({$codificacion}).";
                                continue;
                            }

                            $codificacionesEnArchivo[$claveCod] = $idPregunta;

                            $existeCodificacionEnOtro = DB::table('preguntas')
                                ->whereRaw('LOWER(codificacion) = ?', [$claveCod])
                                ->where('idpregunta', '!=', $idPregunta)
                                ->exists();

                            if ($existeCodificacionEnOtro) {
                                $errores[] = "Fila {$fila}: codificacion ya existe en otra pregunta ({$codificacion}).";
                                continue;
                            }

                            if (! $asignaturaId) {
                                $errores[] = "Fila {$fila}: asignatura_id inválido.";
                                continue;
                            }

                            if ($capitulo === '' || ! preg_match('/^[0-9]{2,}$/', $capitulo)) {
                                $errores[] = "Fila {$fila}: capitulo inválido (debe ser numérico tipo 01, 02, 10...).";
                                continue;
                            }

                            if (! $dificultad) {
                                $errores[] = "Fila {$fila}: grado_dificultad inválido (Facil, Normal o Dificil).";
                                continue;
                            }

                            $filasParaUpsert[] = [
                                'idpregunta' => $idPregunta,
                                'codificacion' => $codificacion,
                                'asignatura_id' => $asignaturaId,
                                'capitulo' => $capitulo,
                                'tema' => $this->toNullableString($mapa['tema'] ?? null),
                                'sub_tema' => $this->toNullableString($mapa['sub_tema'] ?? null),
                                'grado_dificultad' => $dificultad,
                                'clave' => $this->toNullableString($mapa['clave'] ?? null),
                                'proceso' => $this->toNullableString($mapa['proceso'] ?? null),
                                'ruta' => $this->toNullableString($mapa['ruta'] ?? null),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        $reader->close();

                        if (empty($filasParaUpsert)) {
                            Notification::make()
                                ->title('No se importaron filas')
                                ->body(empty($errores) ? 'El archivo no contiene registros válidos.' : $errores[0])
                                ->warning()
                                ->send();

                            return;
                        }

                        DB::table('preguntas')->upsert(
                            $filasParaUpsert,
                            ['idpregunta'],
                            ['codificacion', 'asignatura_id', 'capitulo', 'tema', 'sub_tema', 'grado_dificultad', 'clave', 'proceso', 'ruta', 'updated_at'],
                        );

                        $mensaje = 'Registros procesados: '.count($filasParaUpsert).'.';

                        if (! empty($errores)) {
                            $mensaje .= ' Errores: '.count($errores).'. Ejemplo: '.$errores[0];
                        }

                        Notification::make()
                            ->title('Importación finalizada')
                            ->body($mensaje)
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Error al importar Excel')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($archivo);
                    }
                }),
            CreateAction::make(),
        ];
    }

    private function parseDificultad(mixed $valor): ?string
    {
        if (is_int($valor) || is_float($valor)) {
            $n = (int) $valor;

            return match ($n) {
                1 => 'Facil',
                2 => 'Normal',
                3 => 'Dificil',
                default => null,
            };
        }

        $texto = strtolower(trim((string) $valor));

        return match ($texto) {
            '1', 'facil' => 'Facil',
            '2', 'normal' => 'Normal',
            '3', 'dificil' => 'Dificil',
            default => null,
        };
    }

    private function toIntOrNull(mixed $valor): ?int
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (int) $valor;
        }

        return ctype_digit(trim((string) $valor)) ? (int) trim((string) $valor) : null;
    }

    private function toNullableString(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
