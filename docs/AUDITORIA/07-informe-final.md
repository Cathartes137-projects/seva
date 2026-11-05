# 07 — Informe final de auditoría (comparativa antes/después)

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Fase:** 7 (D20) · **Fecha:** 2026-10-21
**Rama:** `fix/auditoria-defectos`
**Referencia inicial:** `docs/AUDITORIA/00-linea-base.md` (Fase 0, estado PRE)
**Referencia final:** árbol de trabajo auditado + base `sevaeducativo` (MariaDB 10.4.32)

---

## 1. Resumen ejecutivo

SEVA pasó de un prototipo con **credenciales reales en texto plano, cero protección CSRF, sesiones sin flags, sin rate limiting y sin cabeceras de seguridad** a una aplicación con defensa en profundidad y documentación verificable.

| Indicador | Estado inicial (F0) | Estado final (F7) |
|---|---|---|
| Defectos del plan | 23 abiertos | **21 resueltos · 1 mitigado · 1 pendiente (documentación)** |
| Formularios con CSRF | 0 | **7/7** |
| Cabeceras de seguridad | 0 de 6 | **5 en HTTP local + HSTS en producción** |
| Rate limiting | No | **5 fallos → 15 min por IP + correo** |
| Recuperación de contraseña | `404` | **Completa, con tokens de un solo uso** |
| Tablas de base de datos | 5 | **8** |
| Pruebas documentadas | 0 | **38 (37 ✅ · 1 ⚠️)** |
| Documentos de auditoría | 1 (línea base) | **8** |
| Riesgo crítico abierto en código actual | Credenciales reales / Admin roto | **Ninguno** |

**Conclusión:** el sistema es apto para continuar a la Fase 8 (reparación de la MEMORIA y cierre/PR). Los riesgos que quedan son conocidos y acotados; el único hallazgo de accesibilidad nuevo es el contraste de la alerta de error (3,77:1).

---

## 2. Comparativa por dimensión

### 2.1 Repositorio y código

| Dimensión | F0 | F7 | Cambio |
|---|---:|---:|---|
| Archivos versionados | 23 | **44** (37 + 7 documentos nuevos) | +21 |
| Archivos PHP | 9 (incl. `db.php`) | **11** | +2 |
| Líneas PHP | 2.254 | **3.690** | +1.436 (+64 %) |
| Módulo de seguridad central | Inexistente | `seguridad.php` (440 líneas) | Nuevo |
| Archivo muerto con credenciales | `db.php` (16 líneas) | Eliminado | −1 |
| CSS | 34 variables (sin LOC inicial) | **1.335 líneas** con tokens | Ampliado |
| Pruebas automatizadas | 0 | 0 (38 pruebas manuales reproducibles) | — |

### 2.2 Seguridad

| Control | F0 | F7 |
|---|---|---|
| CSRF | ❌ 0 coincidencias | ✅ Token en 7 POST; rechazo verificado |
| Cookies de sesión | ❌ Sin `HttpOnly`/`SameSite`/`Secure` | ✅ `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS |
| Fijación de sesión | ❌ ID aceptado del cliente | ✅ `session_regenerate_id(true)`; ID viejo rechazado |
| Rate limiting | ❌ Ausente | ✅ 5 fallos → 15 min (`intentos_login`) |
| Honeypot | ❌ Ausente | ✅ Login, registro y recuperación |
| Rehash de contraseñas | ❌ Ausente | ✅ Coste 04 → 10 verificado |
| Cabeceras | ❌ 0/6 | ✅ `nosniff`, `DENY`, `Referrer-Policy`, `Permissions-Policy`, CSP, HSTS condicional |
| `X-Powered-By` | ⚠️ Expuesto | ✅ Eliminado |
| Errores PHP | ⚠️ Visibles | ✅ `display_errors=Off` + `log_errors=On` |
| SQLi | ⚠️ Sin verificación | ✅ PDO preparado; sin bypass ni trazas |
| XSS | ⚠️ Sin verificación | ✅ Almacenado y reflejado escapados |
| Recuperación | ❌ `recuperar.php` 404 | ✅ Tokens 32 B, SHA-256, 30 min, un solo uso, anti-enumeración |
| Secretos | ❌ Dump con datos reales y `admin12358` | ⚠️ Fuera del árbol actual; historial no reescrito (D04) |

### 2.3 Datos y contenido

| Dimensión | F0 | F7 |
|---|---|---|
| Tablas | 5 | **8** (`intentos_login`, `recuperacion_password`, `vocabulario_senas`) |
| Índices | No medidos | **20** (8 PK + 12 secundarios) |
| Claves foráneas | No medidas | **5**, 0 huérfanos |
| Usuarios | 5 reales, 1 en texto plano | 5 reales (BD viva) · seed con 2 ficticios bcrypt |
| Vocabulario | Hardcodeado en la vista | **50 señas** en 12 lecciones |
| Imágenes de lección | 8 ausentes → 404 | **0 404** con `default.svg` |
| Video | Carpeta ausente | Carpeta documentada + placeholder accesible |
| IDs | Forzados `1234567899…` | Seed `1..n`; BD viva sin renumerar (D18) |

### 2.4 Accesibilidad

| Criterio | F0 | F7 |
|---|---|---|
| Idioma y viewport | Sin verificar | ✅ 8/8 páginas |
| Texto alternativo | Sin verificar | ✅ Plantilla con `alt` |
| Etiquetas de formulario | Sin verificar | ✅ 15 `label` + 35 `aria-label` |
| Foco visible | Sin verificar | ✅ 11 reglas `:focus-visible` |
| Movimiento reducido | Sin verificar | ✅ 3 bloques + preferencia |
| Objetivos táctiles | Sin verificar | ✅ 23 `min-height:44px` |
| Contraste | Sin verificar | ⚠️ 6 combinaciones ✅; alerta de error 3,77:1 |
| ARIA de filtros | `role="tab"` incompleto | ✅ `role="group"` + `aria-pressed` |
| Subtítulos WebVTT | No | ❌ Solo transcripción textual (N-03) |

### 2.5 Documentación

| Documento | F0 | F7 |
|---|---|---|
| Línea base | ✅ | ✅ Conservada + nota de continuidad |
| Inventario | ❌ | ✅ `01-inventario.md` |
| Modelo de datos | ❌ | ✅ `02-modelo-datos.md` (ERD Mermaid) |
| Flujos | ❌ | ✅ `03-flujos.md` (9 diagramas de secuencia) |
| Trazabilidad | ❌ | ✅ `04-trazabilidad.md` (40 filas: 32 ✅ / 4 ⚠️ / 4 ❌) |
| Seguridad | ❌ | ✅ `05-seguridad.md` (OWASP/CWE + D04) |
| Pruebas | ❌ | ✅ `06-pruebas.md` (38 casos) |
| Informe final | ❌ | ✅ Este documento |
| MEMORIA | ⚠️ Corrupta (D19) | ⚠️ Sin cambios (Fase 8) |

---

## 3. Estado de los 23 defectos

| ID | Defecto | Fase | Estado | Evidencia |
|---|---|---|---|---|
| D01 | Credenciales reales y Admin en texto plano | F1 | ✅ Resuelto | Seed ficticio bcrypt; dump eliminado |
| D02 | Sin `.gitignore` | F1 | ✅ Resuelto | `.gitignore` versionado |
| D03 | `db.php` muerto con credenciales | F1 | ✅ Resuelto | Archivo eliminado; 0 referencias |
| D04 | Secreto en historial git | F1 | ⚠️ Mitigado | Fuera del árbol; historial no reescrito (decisión) |
| D05 | Sin CSRF | F2 | ✅ Resuelto | 7/7 POST con token; negativos rechazados |
| D06 | Cookies sin flags / fijación | F2 | ✅ Resuelto | Flags + regeneración; S-01/S-02 |
| D07 | Sin rehash | F2 | ✅ Resuelto | `$2y$04$` → `$2y$10$` |
| D08 | Logout por `?salir` | F2 | ✅ Resuelto | `logout.php`; sesión destruida |
| D09 | Sin rate limiting | F3 | ✅ Resuelto | 5 fallos → 15 min |
| D10 | Sin honeypot | F3 | ✅ Resuelto | Campo trampa en 3 flujos |
| D11 | Sin cabeceras | F4 | ✅ Resuelto | 5 cabeceras + CSP en local; HSTS condicional |
| D12 | Sin HTTPS/HSTS | F4 | ✅ Resuelto | Condicional a producción |
| D13 | `recuperar.php` 404 | F5 | ✅ Resuelto | Flujo completo + tokens |
| D14 | 8 imágenes ausentes | F5 | ✅ Resuelto | `default.svg`; 0 404 |
| D15 | `default.jpg` y `video/` ausentes | F5 | ✅ Resuelto | SVG + carpeta documentada |
| D16 | Vocabulario hardcodeado | F6 | ✅ Resuelto | 50 señas desde `vocabulario_senas` |
| D17 | `role="tab"` sin panel | F6 | ✅ Resuelto | `role="group"` + `aria-pressed` |
| D18 | IDs forzados | F6 | ✅ Resuelto | Seed `1..n` verificado en BD desechable |
| D19 | MEMORIA corrupta | F8 | ⏳ Pendiente | Única tarea abierta del plan |
| D20 | Sin auditoría documentada | F7 | ✅ Resuelto | Esta carpeta (8 documentos) |
| D21 | Errores y versión expuestos | F4 | ✅ Resuelto | `display_errors=Off`; sin `X-Powered-By` |
| D22 | Validación solo cliente | F6 | ✅ Resuelto | `mb_strlen` 3–100 en servidor |
| D23 | CRLF/LF | F1 | ✅ Resuelto | `.gitattributes` con `eol=lf` |

**Totales:** 21 ✅ · 1 ⚠️ · 1 ⏳.

---

## 4. Hallazgos nuevos y riesgos residuales

| ID | Hallazgo | Sev. | Estado | Recomendación |
|---|---|---|---|---|
| N-01 | CSP permite `'unsafe-inline'` | 🟡 Medio | Aceptado | Extraer JS/CSS inline y usar nonces |
| N-02 | Contraste de alerta de error 3,77:1 | 🟢 Bajo | **Abierto** | Oscurecer a `#B4233A` o aumentar tamaño/peso |
| N-03 | Sin subtítulos WebVTT | 🟢 Bajo | Abierto | Añadir `<track kind="captions">` con los clips |
| N-04 | Registro sin rate limit | 🟢 Bajo | Abierto | Reutilizar `intentos_login` por IP |
| N-05 | Cambio de contraseña no invalida otras sesiones | 🟢 Bajo | Abierto | Forzar re-login o *session version* |
| N-06 | MariaDB 10.4 sin soporte (2024-06) | 🟡 Medio | Abierto | Actualizar a 10.11 LTS / 11.x |
| N-07 | Sin cifrado en reposo ni roles | 🟡 Medio | Abierto | Cifrado de disco y mínimo privilegio |
| N-08 | Sin monitoreo centralizado | 🟢 Bajo | Abierto | Recolector de logs y alertas |
| N-09 | Recuperación sin SMTP | 🟢 Bajo | Aceptado | Integrar correo antes de producción |
| N-10 | BD viva con datos reales e IDs legacy | 🟡 Medio | Operativo | Plan de migración/anonimización |

**N-02 es el único hallazgo que impide declarar WCAG 2.1 AA al 100 %.** Se documenta en `05-seguridad.md` y `06-pruebas.md`; no se corrigió porque F7 es una fase de auditoría, no de cambio de código.

---

## 5. Puerta de calidad F7

| Criterio del plan | Cumplimiento |
|---|---|
| 8 documentos de auditoría | ✅ `00`–`07` |
| Evidencia y comandos reproducibles | ✅ En cada documento |
| Diagramas Mermaid (ERD + flujos) | ✅ 1 ERD + 8 secuencias |
| Matriz de trazabilidad | ✅ 40 filas |
| Hallazgos OWASP/CWE | ✅ 23 defectos + 10 riesgos |
| Rotación de D04 documentada | ✅ `05-seguridad.md §4` |
| Pruebas dinámicas | ✅ 38 casos |
| Comparativa antes/después | ✅ Este documento |
| Sin cambios de código/seed/credenciales | ✅ Solo `docs/AUDITORIA/*` y `docs/PLAN_REMEDIACION.md` |
| Datos de prueba eliminados | ✅ 0 usuarios, 0 intentos, 0 tokens |

**Resultado:** puerta F7 **cumplida**. El cierre se registra con el commit de la fase y la etiqueta `fase-7`.

---

## 6. Recomendaciones para la Fase 8 y posteriores

### Fase 8 (cierre del plan)

1. Reparar `docs/MEMORIA_PROYECTO.md` (D19): eliminar `ez<<<<`, purgar `uid=1000(sandbox)…`, restaurar citas vacías y actualizar el esquema a 8 tablas.
2. Crear `docs/AUDITORIA/README.md` como índice de los 8 documentos.
3. Abrir el PR `fix/auditoria-defectos → seva-antigraity-migracion` con el checklist y la evidencia de `06-pruebas.md`.

### Backlog posterior (fuera del plan de 8 fases)

| Prioridad | Acción |
|---|---|
| Alta | Corregir el contraste de la alerta de error (N-02) y validar con axe/Lighthouse |
| Alta | Integrar SMTP real en la recuperación (N-09) antes de producción |
| Media | Extraer JS inline y endurecer la CSP (N-01) |
| Media | Rate limit en registro (N-04) e invalidación de sesiones al cambiar contraseña (N-05) |
| Media | Actualizar MariaDB (N-06) y revisar cifrado/roles (N-07) |
| Baja | Subtítulos WebVTT (N-03), monitoreo centralizado (N-08) y migración de datos vivos (N-10) |

### Operación

- Rotar cualquier credencial que haya reutilizado `admin12358` (D04).
- Mantener el repositorio privado y activar *secret scanning*.
- Definir `DB_USER`/`DB_PASS` por entorno; no usar `root` sin contraseña en producción.
- Programar copias de seguridad y una prueba de restauración.

---

## 7. Índice de evidencia

| Documento | Aporta |
|---|---|
| `00-linea-base.md` | Estado PRE, métricas y pruebas iniciales |
| `01-inventario.md` | 37 archivos, LOC, endpoints, grafo de dependencias |
| `02-modelo-datos.md` | ERD, 8 tablas, 5 FKs, 20 índices, volúmenes |
| `03-flujos.md` | Registro, login, dashboard, lección, perfil, configuración, recuperación, logout |
| `04-trazabilidad.md` | MEMORIA ↔ código ↔ BD |
| `05-seguridad.md` | OWASP/CWE, D01–D23, D04, N-01…N-10 |
| `06-pruebas.md` | 38 casos con comandos y resultados |
| `07-informe-final.md` | Esta comparativa |

---

## 8. Conclusión

La remediación cumplió su objetivo: los defectos críticos y altos del plan están cerrados, las pruebas demuestran que los controles funcionan y el sistema cuenta por primera vez con una auditoría de ingeniería inversa completa y reproducible.

Queda una tarea documental (D19) para la Fase 8 y un conjunto de riesgos residuales que no bloquean el cierre, pero que deben abordarse antes de un despliegue público: contraste AA, SMTP, CSP sin inline, ciclo de vida de MariaDB y migración de los datos vivos.
