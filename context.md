# Contexto del proyecto: Banco de Preguntas

## 1. Resumen funcional
Este proyecto implementa un sistema para:

- Registrar preguntas por metadatos (sin guardar el enunciado completo en base de datos).
- Crear exámenes/procesos.
- Sortear preguntas por criterios de clasificación.
- Revisar y ajustar manualmente el sorteo temporal.
- Confirmar un examen sorteado.
- Registrar preguntas utilizadas históricamente para evitar reutilización.

La aplicación está construida con Laravel + Filament (panel administrativo).

---

## 2. Stack tecnológico

- Backend: Laravel 13
- Panel admin: Filament 4
- Base de datos: PostgreSQL
- PHP: 8.3+

---

## 3. Modelo de datos principal

### Tablas base

#### `asignaturas`
- `id`
- `nombre` (único)
- `timestamps`

#### `examenes`
- `id`
- `nombre` (único)
- `proceso` (nombre del proceso)
- `timestamps`

#### `preguntas`
- `idpregunta` (PK)
- `codificacion`
- `asignatura_id` (FK)
- `capitulo`
- `tema`
- `sub_tema`
- `grado_dificultad` (1,2,3)
- `clave`
- `proceso`
- `ruta`
- `timestamps`

> Nota: el contenido completo de la pregunta no se guarda en BD. La referencia principal es `ruta`, donde están los archivos físicos de la pregunta.

### Tablas de flujo de sorteo

#### `sorteo_temporal`
Tabla temporal de trabajo para selección previa a confirmar examen.

- `idpregunta`
- `asignatura`
- `grado_dificultad`
- `capitulo`
- `ruta`
- `timestamps`

#### `examen_sorteado`
Persistencia intermedia de preguntas confirmadas para un examen.

- `id`
- `examen_id` (FK)
- `idpregunta`
- `codificacion`
- `asignatura_id` (FK)
- `capitulo`
- `tema`
- `sub_tema`
- `grado_dificultad`
- `clave`
- `proceso`
- `ruta`
- `timestamps`

Incluye índices y unicidad por (`examen_id`, `idpregunta`).

#### `preguntas_sorteadas`
Histórico simple de preguntas ya usadas.

- `id`
- `id_pregunta` (único)
- `timestamps`

---

## 4. Lógica de negocio implementada

## 4.1 Registro de preguntas
Se registran metadatos:

- Asignatura
- Capítulo
- Tema/Subtema
- Dificultad
- Ruta
- Datos de apoyo (`codificacion`, `clave`, etc.)

Validaciones clave:

- `capitulo` obligatorio, formato numérico de al menos 2 dígitos (ej. 01, 02, 10, 25, ...)
- `grado_dificultad` obligatorio
- dificultad visible como: Facil / Normal / Dificil (sin tildes)

## 4.2 Creación de exámenes
Cada examen tiene:

- `nombre` (identificador del examen)
- `proceso` (nombre del proceso asociado)

## 4.3 Sorteo de preguntas
La pantalla de gestión de sorteo permite seleccionar:

- Asignatura (obligatorio)
- Capítulo (obligatorio)
- Grado de dificultad (obligatorio)
- Tema (opcional)
- Cantidad de preguntas (obligatorio)

Reglas de selección:

1. Consulta `preguntas` por filtros.
2. Excluye preguntas ya presentes en `sorteo_temporal`.
3. Excluye SIEMPRE preguntas ya usadas de `preguntas_sorteadas`.
4. Selecciona aleatoriamente según cantidad.
5. Inserta resultado en `sorteo_temporal`.

## 4.4 Revisión manual
En la misma pantalla, el usuario puede:

- Visualizar listado de `sorteo_temporal`.
- Quitar preguntas puntuales antes de confirmar.

## 4.5 Confirmar examen
Al confirmar:

- Se toma el contenido de `sorteo_temporal`.
- Se guarda en `examen_sorteado` asociado al `examen_id`.
- El campo `proceso` se completa con el proceso del examen (si existe), con fallback al proceso de la pregunta.

## 4.6 Refrescar y enviar preguntas
Al ejecutar cierre:

1. Toma `idpregunta` de `examen_sorteado`.
2. Inserta/actualiza en `preguntas_sorteadas` (`id_pregunta`).
3. Vacía `examen_sorteado`.
4. Vacía `sorteo_temporal`.

Resultado: se conserva trazabilidad de preguntas usadas para evitar reutilización en sorteos futuros.

---

## 5. Interfaz de administración (Filament)

### Recursos principales

- Asignaturas (CRUD)
- Exámenes (CRUD con `nombre` + `proceso`)
- Preguntas (CRUD con metadatos)

### Página custom

- Gestión de sorteo de examen:
  - configura examen
  - ejecuta sorteo
  - revisa lista temporal
  - confirma examen
  - envía y limpia temporales

Además, la pantalla incluye alertas de validación visibles (notificación + error bajo campo) para casos como capítulo mal digitado.

---

## 6. Reglas importantes para operación

1. El sistema depende de una estructura de carpetas de preguntas accesible por `ruta`.
2. `preguntas_sorteadas` es la fuente para bloquear reutilización en sorteos.
3. Capítulo es texto numérico (no entero), para soportar formato con cero inicial.
4. Dificultad operativa:
   - 1 = Facil
   - 2 = Normal
   - 3 = Dificil

---

## 7. Ruta de panel

Panel administrativo disponible en:

- `/admin`

Página de sorteo:

- `/admin/gestion-sorteo-examen`

---

## 8. Estado actual

El flujo end-to-end de gestión de preguntas y generación de examen está funcional:

- registro de catálogo y preguntas
- sorteo controlado
- revisión manual
- confirmación
- persistencia histórica de usadas

Pendientes futuros recomendados (opcionales):

- reportes por examen/proceso
- exportación (PDF/Excel) del examen sorteado
- auditoría de usuario/fecha por acción
- reglas avanzadas de balance por tema/subtema
