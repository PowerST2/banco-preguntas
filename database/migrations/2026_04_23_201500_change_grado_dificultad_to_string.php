<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE preguntas ALTER COLUMN grado_dificultad TYPE VARCHAR(20) USING (CASE WHEN grado_dificultad::text = '1' THEN 'Facil' WHEN grado_dificultad::text = '2' THEN 'Normal' WHEN grado_dificultad::text = '3' THEN 'Dificil' ELSE NULLIF(grado_dificultad::text, '') END)");

        DB::statement("ALTER TABLE sorteo_temporal ALTER COLUMN grado_dificultad TYPE VARCHAR(20) USING (CASE WHEN grado_dificultad::text = '1' THEN 'Facil' WHEN grado_dificultad::text = '2' THEN 'Normal' WHEN grado_dificultad::text = '3' THEN 'Dificil' ELSE NULLIF(grado_dificultad::text, '') END)");

        DB::statement("ALTER TABLE examen_sorteado ALTER COLUMN grado_dificultad TYPE VARCHAR(20) USING (CASE WHEN grado_dificultad::text = '1' THEN 'Facil' WHEN grado_dificultad::text = '2' THEN 'Normal' WHEN grado_dificultad::text = '3' THEN 'Dificil' ELSE NULLIF(grado_dificultad::text, '') END)");

        DB::statement("ALTER TABLE examenes_historico ALTER COLUMN grado_dificultad TYPE VARCHAR(20) USING (CASE WHEN grado_dificultad::text = '1' THEN 'Facil' WHEN grado_dificultad::text = '2' THEN 'Normal' WHEN grado_dificultad::text = '3' THEN 'Dificil' ELSE NULLIF(grado_dificultad::text, '') END)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE preguntas ALTER COLUMN grado_dificultad TYPE SMALLINT USING (CASE WHEN lower(grado_dificultad) = 'facil' THEN 1 WHEN lower(grado_dificultad) = 'normal' THEN 2 WHEN lower(grado_dificultad) = 'dificil' THEN 3 ELSE NULL END)");

        DB::statement("ALTER TABLE sorteo_temporal ALTER COLUMN grado_dificultad TYPE SMALLINT USING (CASE WHEN lower(grado_dificultad) = 'facil' THEN 1 WHEN lower(grado_dificultad) = 'normal' THEN 2 WHEN lower(grado_dificultad) = 'dificil' THEN 3 ELSE NULL END)");

        DB::statement("ALTER TABLE examen_sorteado ALTER COLUMN grado_dificultad TYPE SMALLINT USING (CASE WHEN lower(grado_dificultad) = 'facil' THEN 1 WHEN lower(grado_dificultad) = 'normal' THEN 2 WHEN lower(grado_dificultad) = 'dificil' THEN 3 ELSE NULL END)");

        DB::statement("ALTER TABLE examenes_historico ALTER COLUMN grado_dificultad TYPE SMALLINT USING (CASE WHEN lower(grado_dificultad) = 'facil' THEN 1 WHEN lower(grado_dificultad) = 'normal' THEN 2 WHEN lower(grado_dificultad) = 'dificil' THEN 3 ELSE NULL END)");
    }
};
