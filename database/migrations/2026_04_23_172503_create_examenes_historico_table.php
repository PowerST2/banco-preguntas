<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('examenes_historico', function (Blueprint $table) {

            $table->id();

            $table->foreignId('examen_id')
                ->constrained('examenes')
                ->restrictOnDelete();

            $table->bigInteger('idpregunta');

            $table->string('codificacion',100)->nullable();

            $table->foreignId('asignatura_id')
                ->constrained('asignaturas')
                ->restrictOnDelete();

            $table->string('capitulo',150)->nullable();
            $table->string('tema',150)->nullable();
            $table->string('sub_tema',150)->nullable();

            $table->smallInteger('grado_dificultad')->nullable();

            $table->text('clave')->nullable();
            $table->text('proceso')->nullable();
            $table->text('ruta')->nullable();

            $table->index('examen_id','ix_examenes_historico_examen_id');
            $table->index('asignatura_id','ix_examenes_historico_asignatura_id');
            $table->index('idpregunta','ix_examenes_historico_idpregunta');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes_historico');
    }
};