# Banco de Preguntas

Sistema para la gestión estructurada de preguntas y generación de exámenes por sorteo controlado, con historial de preguntas ya usadas.

---

## Descripción

**Banco de Preguntas** es una aplicación web administrativa construida con Laravel y Filament que permite:

- Registrar preguntas por metadatos (sin almacenar el enunciado completo en la base de datos).
- Gestionar exámenes por proceso.
- Sortear preguntas de forma aleatoria según criterios académicos.
- Revisar manualmente el sorteo antes de confirmarlo.
- Registrar las preguntas usadas para evitar su reutilización en futuros exámenes.

---

## Tecnologías

| Componente | Versión |
|---|---|
| PHP | 8.3+ |
| Laravel | 13 |
| Filament | 4 |
| PostgreSQL | — |

---

## Instalación

### Requisitos previos

- PHP 8.3 o superior con extensiones: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- Composer
- PostgreSQL
- Node.js y npm (para compilar assets de Filament)

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/PowerST2/banco-preguntas.git
cd banco-preguntas

# 2. Instalar dependencias de PHP
composer install

# 3. Instalar dependencias de Node.js
npm install && npm run build

# 4. Copiar el archivo de entorno
cp .env.example .env

# 5. Generar la clave de aplicación
php artisan key:generate

# 6. Configurar la base de datos en .env y ejecutar migraciones
php artisan migrate

# 7. Crear el usuario administrador de Filament
php artisan make:filament-user

# 8. Levantar el servidor de desarrollo
php artisan serve
```

---

## Variables de entorno

```env
APP_NAME="Banco de Preguntas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=banco_preguntas
DB_USERNAME=postgres
DB_PASSWORD=secret
```

---

## Módulos funcionales

### 1. Registro de preguntas

Cada pregunta almacena únicamente metadatos; el enunciado completo reside en archivos externos referenciados por `ruta`.

| Campo | Descripción |
|---|---|
| `asignatura` | Materia a la que pertenece la pregunta |
| `capitulo` | Número de capítulo (formato `01`, `02`, `10`, `25`, …) |
| `tema` / `subtema` | Tema y subtema dentro del capítulo |
| `dificultad` | `Facil`, `Normal` o `Dificil` |
| `ruta` | Ruta al archivo externo que contiene el enunciado |
| `codificacion` | Metadata adicional de codificación |
| `clave` | Clave identificadora de la pregunta |
| `proceso` | Proceso al que pertenece la pregunta |

### 2. Registro de exámenes

Cada examen requiere:

- **nombre**: nombre descriptivo del examen.
- **proceso**: proceso académico asociado.

### 3. Sorteo de preguntas

El sistema aplica los siguientes filtros al generar un sorteo:

| Filtro | Obligatorio |
|---|---|
| Asignatura | ✅ |
| Capítulo | ✅ |
| Dificultad | ✅ |
| Tema | ⬜ |
| Cantidad | ✅ |

**Reglas del sorteo:**

- Excluye preguntas presentes en `sorteo_temporal`.
- Excluye siempre preguntas registradas en `preguntas_sorteadas`.
- Realiza una selección aleatoria hasta completar la cantidad indicada.

### 4. Revisión del sorteo temporal

Antes de confirmar el examen, el administrador puede visualizar las preguntas en `sorteo_temporal` y eliminar individualmente cualquiera que no sea adecuada.

### 5. Confirmación del examen

Al confirmar:

1. Las preguntas de `sorteo_temporal` se copian a `examen_sorteado`.
2. Se asocian al examen seleccionado mediante `examen_id`.

### 6. Cierre de proceso (Refrescar y enviar)

Al cerrar un proceso:

1. Los `idpregunta` de `examen_sorteado` se insertan en `preguntas_sorteadas` como `id_pregunta`.
2. Se limpia la tabla `examen_sorteado`.
3. Se limpia la tabla `sorteo_temporal`.

---

## Estructura de base de datos

```
asignaturas          — Catálogo de asignaturas
preguntas            — Metadatos de cada pregunta
examenes             — Exámenes registrados por proceso
sorteo_temporal      — Preguntas sorteadas pendientes de confirmación
examen_sorteado      — Preguntas confirmadas para un examen específico
preguntas_sorteadas  — Historial de preguntas ya utilizadas
```

---

## Panel administrativo

| Ruta | Descripción |
|---|---|
| `/admin` | Panel principal de administración (Filament) |
| `/admin/gestion-sorteo-examen` | Gestión del sorteo y confirmación de exámenes |

---

## Flujo completo

```
Alta de catálogos y preguntas
        ↓
Crear examen (nombre + proceso)
        ↓
Sorteo de preguntas (filtros + cantidad)
        ↓
Revisión manual del sorteo temporal
        ↓
Confirmar examen → examen_sorteado
        ↓
Cierre de proceso → preguntas_sorteadas
                  → limpieza de tablas temporales
```

---

## Troubleshooting

| Problema | Solución |
|---|---|
| Error de conexión a PostgreSQL | Verificar credenciales y que el servicio esté activo |
| Filament no carga estilos | Ejecutar `npm run build` y limpiar caché con `php artisan optimize:clear` |
| No aparecen preguntas en el sorteo | Confirmar que no estén todas en `preguntas_sorteadas` o `sorteo_temporal` |
| Migraciones fallan | Verificar que la base de datos `banco_preguntas` exista en PostgreSQL |

---

## Licencia

Este proyecto es de uso interno institucional.
