<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sorteo_temporal', function (Blueprint $table) {

            $table->bigInteger('idpregunta');

            $table->string('asignatura',150)->nullable();
            $table->string('grado_dificultad',20)->nullable();
            $table->string('capitulo',150)->nullable();

            $table->text('ruta')->nullable();

            $table->index('idpregunta','ix_sorteo_temporal_idpregunta');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorteo_temporal');
    }
};