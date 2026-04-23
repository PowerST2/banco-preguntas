<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    protected $table = 'preguntas';

    protected $primaryKey = 'idpregunta';

    protected $fillable = [
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

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }

    public function preguntaSorteada(): HasOne
    {
        return $this->hasOne(PreguntaSorteada::class, 'id_pregunta', 'idpregunta');
    }
}
