<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = [
        'nombre',
        'proceso',
    ];

    public function preguntasSorteadas(): HasMany
    {
        return $this->hasMany(ExamenSorteado::class, 'examen_id');
    }
}
