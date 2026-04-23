# Contexto del proyecto: Banco de Preguntas

## 1. Resumen funcional
Sistema Laravel + Filament para:

- Registrar preguntas por metadatos (sin guardar enunciado completo en BD).
- Gestionar exámenes (`nombre` + `proceso`).
- Sortear preguntas con filtros académicos.
- Confirmar preguntas a `examen_sorteado`.
- Confirmar examen final a `preguntas_sorteadas` y `examenes_historico`.
- Extraer preguntas usadas por examen (ZIP cliente o copia en servidor).

---

## 2. Stack

- Laravel 13
- Filament 4
- PostgreSQL
- PHP 8.4

---

## 3. Modelo de datos vigente

### Tablas base

#### `asignaturas`
- `id`, `nombre`, `timestamps`

#### `examenes`
- `id`, `nombre`, `proceso`, `timestamps`

#### `preguntas`
- `idpregunta` (PK)
- `codificacion` (**única en lógica de formulario/importación**)
- `asignatura_id`, `capitulo`, `tema`, `sub_tema`
- `grado_dificultad` (**texto:** `Facil|Normal|Dificil`)
- `clave`, `proceso`, `ruta`
- `timestamps`

> `ruta` apunta al directorio físico de la pregunta (archivos, imágenes, recursos).

### Tablas de flujo

#### `sorteo_temporal`
- staging del sorteo previo a confirmar preguntas.

#### `examen_sorteado`
- preguntas ya confirmadas para un examen.
- unicidad por (`examen_id`, `idpregunta`).

#### `preguntas_sorteadas`
- histórico simple de preguntas usadas (`id_pregunta` único).

#### `examenes_historico`
- snapshot detallado de preguntas usadas por examen/proceso al confirmar examen.

---

## 4. Lógica actual

### 4.1 Registro de preguntas

Validaciones clave:
- `codificacion` requerida y no duplicada.
- `capitulo` requerido (numérico de al menos 2 dígitos).
- `grado_dificultad` requerido (`Facil`, `Normal`, `Dificil`).

Importación masiva Excel:
- valida estructura de columnas esperadas.
- detecta duplicados de `codificacion` dentro del archivo y contra BD.
- convierte dificultad a texto estándar.

### 4.2 Sorteo y confirmación

En `Gestión de sorteo de examen`:

1. **Ejecutar sorteo**
   - filtra por asignatura/capítulo/dificultad/(tema opcional).
   - excluye siempre `preguntas_sorteadas`.
   - llena `sorteo_temporal`.

2. **Confirmar preguntas** (botón verde)
   - mueve de `sorteo_temporal` a `examen_sorteado` del examen elegido.
   - limpia `sorteo_temporal`.

3. **Confirmar examen** (botón amarillo)
   - toma `examen_sorteado` del examen elegido.
   - guarda en `examenes_historico`.
   - guarda IDs en `preguntas_sorteadas`.
   - limpia `examen_sorteado` del examen y limpia `sorteo_temporal`.

### 4.3 Histórico de exámenes

Vista dedicada:
- panel izquierdo: exámenes históricos (nombre + proceso).
- panel derecho: detalle de preguntas del examen seleccionado.

### 4.4 Extracción de preguntas por examen

En detalle de histórico:

- **Descargar ZIP (Windows/Cliente)**
  - genera zip agrupado por asignatura.
  - descarga al navegador del usuario.

- **Extraer a carpeta SORTEO**
  - copia en servidor a carpeta predeterminada:
    - `/home/vboxuser/Desktop/SORTEO`
    - fallback: `storage/app/SORTEO`
  - copia agrupando por asignatura.

---

## 5. Vistas/recursos principales

- `/admin/preguntas`
  - columnas y filtros: asignatura, dificultad, estado sorteada/no sorteada.
  - carga masiva Excel.

- `/admin/gestion-sorteo-examen`
  - sorteo, confirmación de preguntas, gestión de examen_sorteado, confirmar examen.

- `/admin/historial-examenes`
  - detalle histórico y extracción (ZIP/servidor).

---

## 6. Reglas operativas

1. `ruta` debe apuntar a carpetas reales accesibles por el servidor para extracción directa.
2. Para usuarios Windows cliente, preferir **Descargar ZIP**.
3. `preguntas_sorteadas` bloquea reutilización futura en sorteo.
4. Dificultad siempre se persiste en texto: `Facil`, `Normal`, `Dificil`.
