# 04 — Matriz de trazabilidad MEMORIA ↔ Código ↔ Base de datos

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Fase:** 7 (D20) · **Fecha:** 2026-10-21
**Fuente funcional:** `docs/MEMORIA_PROYECTO.md`
**Fuente técnica:** código en `35747b6` + `sevaeducativo` (MariaDB 10.4.32)

> **Nota sobre la fuente:** la MEMORIA está parcialmente corrupta (D19: celdas vacías,
> texto de terminal y referencias sin nombre). Las filas se reconstruyeron por sección
> y se contrastaron con el código real. La corrección editorial corresponde a la Fase 8.

**Leyenda:** ✅ Implementado · ⚠️ Parcial · ❌ Ausente

---

## 1. Funcionalidad descrita en la MEMORIA

| ID | Capacidad | Evidencia en código | Evidencia en BD | Estado | Observación |
|---|---|---|---|---|---|
| F-01 | Registro de usuario (Flujo A) | `registro.php:16-57` | `usuarios` | ✅ | CSRF, honeypot, validación de nombre 3–100 y `password_hash` |
| F-02 | Inicio de sesión y control de acceso (Flujo B) | `login.php:26-76`, `seguridad.php:121` | `usuarios`, `intentos_login` | ✅ | Rate limit, rehash y regeneración de sesión |
| F-03 | Dashboard y cálculo de progreso (Flujo C) | `dashboard.php:20-40` | `contenidos_educativos`, `progreso_usuario` | ✅ | Porcentaje y nivel calculados en servidor |
| F-04 | Lecciones y vocabulario | `leccion.php:130-168`, `:322-370` | `contenidos_educativos`, `vocabulario_senas` | ✅ | Vocabulario propio por lección desde Fase 6 |
| F-05 | Marcar lección como completada | `leccion.php:15-35` | `progreso_usuario` (UPSERT) | ✅ | `ON DUPLICATE KEY UPDATE` + CSRF |
| F-06 | Perfil y estadísticas | `perfil.php:45-75` | `usuarios`, `progreso_usuario` | ✅ | Nivel calculado por lecciones completadas |
| F-07 | Configuración y cambio de contraseña | `configuracion.php:24-61` | `usuarios` | ✅ | Verifica la contraseña actual antes de cambiar |
| F-08 | Recuperación de contraseña | `recuperar.php`, `restablecer.php` | `recuperacion_password` | ✅ | Tokens de un solo uso, 30 min, anti-enumeración |
| F-09 | Cierre de sesión | `logout.php` | — | ✅ | Destruye sesión y cookie; sin `?salir` (D08) |
| F-10 | Catálogo de cursos | — | `cursos` | ❌ | Tabla creada, sin código ni interfaz |
| F-11 | Repetición espaciada de vocabulario | — | `palabras_repasadas` | ❌ | Tabla creada, sin código ni interfaz |
| F-12 | Landing pública | `index.html` | — | ✅ | Estática, sin sesión |

---

## 2. Modelo de datos

| ID | Capacidad | Evidencia en código | Evidencia en BD | Estado | Observación |
|---|---|---|---|---|---|
| D-01 | Esquema de 5 tablas documentado | — | 8 tablas reales | ⚠️ | MEMORIA desactualizada; faltan `intentos_login`, `recuperacion_password`, `vocabulario_senas` (D19, F8) |
| D-02 | Relaciones con FK y cascada | `progreso_usuario`, `vocabulario_senas`, `recuperacion_password` | 5 FKs verificadas, 0 huérfanos | ✅ | `ON DELETE CASCADE ON UPDATE CASCADE` |
| D-03 | IDs secuenciales 1..n | `seed_demo.sql` | Seed limpio: 1..12 / 1..2 | ✅ | La BD viva conserva IDs legacy; no se renumeró (D18) |
| D-04 | Datos de demostración ficticios | `seed_demo.sql` | 2 usuarios demo | ✅ | Correos `example.com` y bcrypt (D01) |
| D-05 | Integridad de índices | Consultas del código | 20 índices, 8 PK | ✅ | Únicos en email, token, progreso y vocabulario |

---

## 3. Decisiones UX y accesibilidad (MEMORIA §4)

| ID | Capacidad | Evidencia en código | Estado | Observación |
|---|---|---|---|---|
| U-01 | Objetivos táctiles ≥ 44×44 px (Fitts) | 23 `min-height:44px` en CSS/páginas | ✅ | Botones, inputs y enlaces principales |
| U-02 | Fragmentación por niveles (Miller/Hick) | Filtros Básico/Intermedio/Avanzado | ⚠️ | Cumple lo esencial; no hay *skeletons* de carga |
| U-03 | Progreso visible (Zeigarnik) | Barra y porcentaje en `dashboard.php` | ✅ | Se actualiza con las lecciones completadas |
| U-04 | Navegación por teclado y foco visible | 11 reglas `:focus-visible` | ✅ | Contorno de 3 px |
| U-05 | Movimiento reducido | 3 bloques `prefers-reduced-motion` + preferencia en `localStorage` | ✅ | Se aplica antes de pintar el DOM |
| U-06 | Contenedor multimedia 16:9 | `.video-lse` con `aspect-ratio` | ✅ | Placeholder si el clip no existe |
| U-07 | Subtítulos WebVTT | — | ❌ | Hay transcripción textual, pero ningún `<track kind="captions">` |
| U-08 | Contraste WCAG AA | Tokens de color | ⚠️ | Texto y botones conformes; alerta de error 3,77:1 (mínimo 4,5:1) |
| U-09 | Filtros accesibles | `role="group"` + `aria-pressed` | ✅ | Sin patrón tablist incompleto (D17) |
| U-10 | Idioma, landmarks y títulos | `lang="es"`, `<main>`, `<title>` único | ✅ | 8/8 páginas HTML |
| U-11 | Estados anunciados a lectores de pantalla | `role="status"`, `aria-live` | ✅ | Contador de resultados y avisos de éxito |

---

## 4. Seguridad y endurecimiento (MEMORIA §5.2)

| ID | Capacidad | Evidencia en código | Estado | Observación |
|---|---|---|---|---|
| S-01 | HTTPS y HSTS | `.htaccess:67-79` | ✅ | Redirección y HSTS condicionados a producción; en local no se activan |
| S-02 | Rehash de contraseñas | `seguridad.php:220-243` | ✅ | Probado: coste 04 → 10 tras login |
| S-03 | Cabeceras de seguridad y CSP | `.htaccess:36-60` | ✅ | CSP con `unsafe-inline` como deuda documentada |
| S-04 | Rate limiting | `seguridad.php:245-363` | ✅ | 5 fallos → 15 min, por IP + correo |
| S-05 | Protección anti-bots | `honeypot_campo()` en login, registro y recuperación | ✅ | Respuesta neutra sin tocar la BD |
| S-06 | Validación y saneamiento de entradas | `trim`, casteo, `filter_var`, `mb_strlen` | ✅ | Validación página por página |
| S-07 | Gestión de secretos | `.gitignore`, `DB_*`, seed sanitizado | ⚠️ | Secreto fuera del árbol actual, pero permanece en el historial (D04) |
| S-08 | Cifrado en reposo / RLS | — | ❌ | Fuera de alcance del plan; sin roles de usuario |
| S-09 | CSRF en formularios | 7 formularios con `csrf_campo()` | ✅ | POST sin token rechazado |
| S-10 | Sesión endurecida | `seguridad.php:49-85` | ✅ | `HttpOnly`, `SameSite=Lax`, `strict_mode`, regeneración |
| S-11 | Recuperación sin enumeración | `recuperar.php:60-140` | ✅ | Respuesta idéntica con y sin cuenta |
| S-12 | Tokens de un solo uso | `restablecer.php:15-115` | ✅ | Hash SHA-256, 30 min, uso único, transacción |

---

## 5. Resumen de clasificación

| Estado | Cantidad | Porcentaje |
|---|---:|---:|
| ✅ Implementado | 32 | 80 % |
| ⚠️ Parcial | 4 | 10 % |
| ❌ Ausente | 4 | 10 % |
| **Total** | **40** | **100 %** |

**Lectura**

- Las 4 ausencias son **tablas sin interfaz** (`cursos`, `palabras_repasadas`), **subtítulos WebVTT** y **cifrado/RLS**, ninguno incluido en el alcance de las fases 1–8.
- Las 4 parciales corresponden a la MEMORIA desactualizada (D19, se corrige en Fase 8), *skeletons*, contraste de la alerta de error y gestión de secretos en historial.
- Toda capacidad marcada como implementada tiene evidencia dinámica en `06-pruebas.md`.

---

## 6. Reproducción

```powershell
cd C:\xampp\htdocs\seva
# Código citado
Select-String -Path *.php -Pattern 'seguridad_validar_csrf|password_needs_rehash|aria-pressed'
# Modelo de datos
mysql -u root sevaeducativo -e "SHOW TABLES; SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='sevaeducativo' AND REFERENCED_TABLE_NAME IS NOT NULL;"
# Conteo de evidencias
(Select-String -Path *.php,css\*.css -Pattern ':focus-visible' -AllMatches | ForEach-Object { $_.Matches.Count } | Measure-Object -Sum).Sum
```
