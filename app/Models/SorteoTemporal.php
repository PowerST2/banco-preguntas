<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SorteoTemporal extends Model
{
    protected $table = 'sorteo_temporal';

    protected $primaryKey = 'idpregunta';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'idpregunta',
        'asignatura',
        'grado_dificultad',
        'capitulo',
        'ruta',
    ];
}
