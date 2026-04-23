<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    protected $table = 'asignaturas';

    protected $fillable = [
        'nombre',
    ];

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'asignatura_id');
    }
}
