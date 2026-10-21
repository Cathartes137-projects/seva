# Base de datos SEVA

Scripts versionados del esquema y de los datos de demostración.

## Archivos

| Archivo | Contenido | Uso |
|---|---|---|
| `schema.sql` | Estructura de las 5 tablas (InnoDB, utf8mb4) | Siempre |
| `seed_demo.sql` | Datos ficticios de demostración | Solo desarrollo |

> **Importante:** `seed_demo.sql` contiene únicamente datos ficticios
> (correos en `example.com` y contraseñas hasheadas con bcrypt).
> Nunca incluir volcados con datos reales en el repositorio.

## Instalación

```bash
# 1. Crear la base de datos
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE seva_educativo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. Importar la estructura
mysql -u root -h 127.0.0.1 seva_educativo < DataBase/schema.sql

# 3. (Opcional) Cargar datos de demostración
mysql -u root -h 127.0.0.1 seva_educativo < DataBase/seed_demo.sql
```

En Windows con XAMPP, usa la ruta completa del cliente:
`C:\xampp\mysql\bin\mysql.exe`

## Credenciales de demostración

| Correo | Contraseña |
|---|---|
| `admin@example.com` | `Seva.Admin.2026` |
| `demo@example.com` | `Seva.Demo.2026` |

Cambiar obligatoriamente en cualquier despliegue real.

## Tablas

- `usuarios` — cuentas de la plataforma
- `contenidos_educativos` — lecciones de la ruta de aprendizaje
- `progreso_usuario` — lecciones completadas por usuario
- `palabras_repasadas` — repetición espaciada de vocabulario
- `cursos` — catálogo de cursos (sin uso actual en el código)

## Respaldos

Los volcados con datos reales quedan fuera del repositorio (ver `.gitignore`):

```bash
mysqldump -u root -h 127.0.0.1 --databases seva_educativo > backup_seva.sql
```
