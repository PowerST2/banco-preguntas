<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ExamenHistorico extends Model
{
    protected $table = 'examenes_historico';

    protected $fillable = [
        'examen_id',
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

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }
}
