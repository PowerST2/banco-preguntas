<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntaSorteada extends Model
{
    protected $table = 'preguntas_sorteadas';

    protected $fillable = [
        'id_pregunta',
    ];
}
