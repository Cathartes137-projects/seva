# SEVA — Aprende Lengua de Señas

Plataforma educativa en PHP + MySQL para aprender Lengua de Señas.

## Instalación (XAMPP)
1. Copia la carpeta `seva-main` en `htdocs`.
2. Importa `seva_educativo.sql` en phpMyAdmin (crea la base `seva_educativo`).
3. Abre `http://localhost/seva-main/`.

## Qué incluye
- Registro e inicio de sesión (contraseñas con hash).
- Panel de lecciones con progreso guardado en la base de datos (`progreso_usuario`).
- **Lección 1 · Alfabeto dactilológico**: 27 letras con imagen, descripción y movimiento (`data/alfabeto.json`).
- **Lección 2 · Saludos y presentaciones**: 24 señas con imagen y descripción (`data/guia.json`).
- **Práctica** (`practica.php`): quiz de opción múltiple, juego de memoria y visor del alfabeto.
- Las demás lecciones muestran una plantilla de ejemplo hasta que se carguen sus contenidos.

## Agregar más contenido
Añade imágenes en `img/` y registros en un JSON dentro de `data/`, con los campos
`palabra` (o `letra`), `archivo`, `con_movimiento` y `descripcion`.

## Créditos
Ilustraciones del abecedario: Andres Marulanda. Conserva este crédito si publicas el material.
