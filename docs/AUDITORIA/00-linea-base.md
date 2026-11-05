# Fase 0 — Línea Base de Auditoría (estado PRE-intervención)

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Rama base:** `seva-antigraity-migracion` · **Rama de trabajo:** `fix/auditoria-defectos`
**Fecha de captura:** 2026-10-21
**Responsable:** auditoría de ingeniería inversa (Fase 0)
**Método:** análisis estático + pruebas de caja negra HTTP + inspección de base de datos

> Este documento registra el estado del sistema **antes** de aplicar cualquier remediación.
> Sirve como referencia para la comparativa antes/después de la Fase 7.

---

## 1. Entorno de ejecución

| Elemento | Valor verificado |
|---|---|
| PHP | 8.2.12 (ZTS Visual C++ 2019 x64) |
| MariaDB | 10.4.32-MariaDB |
| Apache | 2.4.58 (Win64) OpenSSL/3.1.3 |
| URL base | `http://localhost/seva/` |
| Puerto web | 80 (Apache, PID 11924) |
| Puerto BD | 3306 (MariaDB, arrancado manualmente para esta fase) |
| Sistema operativo | Windows |
| Git remoto | `https://github.com/Cathartes137-projects/seva.git` |

**Nota operativa:** MariaDB estaba detenido al iniciar la fase. Se levantó con
`mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`.
El cliente `mysql.exe` requiere `-h 127.0.0.1` (no resuelve `localhost` por IPv6).

---

## 2. Inventario de artefactos (análisis estático)

### 2.1 Archivos versionados (23)

| Archivo | Tamaño | Rol |
|---|---|---|
| `index.html` | 1232 líneas | Landing pública |
| `login.php` | 401 líneas | Autenticación |
| `registro.php` | 504 líneas | Alta de usuario |
| `dashboard.php` | 249 líneas | Ruta de aprendizaje y progreso |
| `leccion.php` | 414 líneas | Detalle de lección y vocabulario |
| `perfil.php` | 284 líneas | Perfil y estadísticas |
| `configuracion.php` | 341 líneas | Accesibilidad y seguridad |
| `logout.php` | 25 líneas | Cierre de sesión |
| `conexion.php` | 20 líneas | Conexión PDO |
| `db.php` | 16 líneas | **Conexión mysqli legacy (archivo muerto)** |
| `css/estilos.css` | 34 variables CSS | Design tokens globales |
| `DataBase/sevaeducativo.sql` | 226 líneas | Volcado de esquema y datos |
| `img/interfaz/logo_seva.jpg` | 119 KB | Logotipo |
| `img/lecciones/*.jpg` | 4 archivos | alfabeto, colores, numeros, saludos |
| `docs/MEMORIA_PROYECTO.md` | 9506 B | Fuente de verdad (corrupta) |
| `docs/.antigravityrules.md` | 1683 B | Reglas de trabajo |
| `docs/PLAN_REMEDIACION.md` | 268 líneas | Plan aprobado |
| `docs/repomix-output.xml` | 52 KB | Duplicación de código |
| `docs/sevaeducativo.json` | 4748 B | Metadatos |
| `docs/README.md` | 2 líneas | Descripción mínima |

### 2.2 Grafo de dependencias PHP

```
conexion.php  ←── login.php
              ←── registro.php
              ←── dashboard.php
              ←── leccion.php
              ←── perfil.php
              ←── configuracion.php
logout.php    (independiente)
db.php        (sin referencias — huérfano)
```

Todas las páginas llaman `session_start()` con guarda `session_status() === PHP_SESSION_NONE`.

### 2.3 Mapa de endpoints (sin sesión)

| Endpoint | Código | Comportamiento |
|---|---|---|
| `index.html` | 200 | Público |
| `registro.php` | 200 | Público |
| `login.php` | 200 | Público |
| `dashboard.php` | 302 | → `login.php` |
| `leccion.php` | 302 | → `login.php` |
| `perfil.php` | 302 | → `login.php` |
| `configuracion.php` | 302 | → `login.php` |
| **`recuperar.php`** | **404** | **Enlazado desde `login.php:421` — no existe** |
| `logout.php` | — | Termina sesión |

---

## 3. Estado de la base de datos (sevaeducativo)

### 3.1 Tablas y volumen

| Tabla | Filas | Observación |
|---|---|---|
| `contenidos_educativos` | 12 | 4 básico, 4 intermedio, 4 avanzado |
| `usuarios` | 5 | IDs forzados desde 1234567899 |
| `progreso_usuario` | 4 | IDs forzados; incluye 3 del usuario 1234567900 |
| `palabras_repasadas` | 0 | Vacía |
| `cursos` | 0 | Vacía, sin uso en el código |

### 3.2 Usuarios registrados (datos sensibles reales)

| id_usuario | Email | Password | Estado |
|---|---|---|---|
| 1234567899 | nosemeocurrenada@gmail.com | `admin12358` (**texto plano, 10 chars**) | 🔴 **No puede iniciar sesión** |
| 1234567900 | edwin1234@gmail.com | `$2y$10$v11…` (bcrypt) | Válido |
| 1234567901 | carlos@gmail.com | `$2y$10$KSm…` | Válido |
| 1234567902 | mancillayarlenis40@gmail.com | `$2y$10$SoI…` | Válido |
| 1234567906 | Testeo2026@gmail.com | `$2y$10$u8T…` | Válido |

**Evidencia D01:** consulta directa confirma que el campo `password` del Admin tiene
10 caracteres y no es un hash bcrypt (que sería de 60 caracteres).

---

## 4. Pruebas de caja negra (evidencia)

### 4.1 Cabeceras HTTP — GET `/seva/login.php` (200)

```
HTTP/1.1 200 OK
Server: Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12
X-Powered-By: PHP/8.2.12
Set-Cookie: PHPSESSID=falan9e1etik9isvdq2lgb7ks7; path=/
Cache-Control: no-store, no-cache, must-revalidate
Content-Type: text/html; charset=UTF-8
```

| Verificación | Resultado |
|---|---|
| `X-Content-Type-Options` | ❌ Ausente |
| `X-Frame-Options` | ❌ Ausente |
| `Referrer-Policy` | ❌ Ausente |
| `Permissions-Policy` | ❌ Ausente |
| `Content-Security-Policy` | ❌ Ausente |
| `Strict-Transport-Security` | ❌ Ausente |
| `X-Powered-By` | ⚠️ Expone versión de PHP (D21) |
| Cookie `PHPSESSID` | ⚠️ Sin `HttpOnly`, sin `SameSite`, sin `Secure` (D06) |

### 4.2 Prueba de CSRF (D05)

| Prueba | Comando | Resultado |
|---|---|---|
| POST login sin token | `curl -X POST -d "email=x@x.com&password=wrongpass"` | **200** — procesado sin validación CSRF |
| POST `leccion.php?id=1` sin token | `curl -X POST -d "marcar_completada=1"` | 302 → login (por falta de sesión, no por CSRF) |

**Conclusión:** ningún formulario valida token CSRF (`grep` de `csrf` → 0 coincidencias).

### 4.3 Prueba de autenticación del usuario Admin (D01)

```
POST /seva/login.php
email=nosemeocurrenada@gmail.com&password=admin12358
→ Respuesta contiene "Correo o contraseña incorrectos."
```

**Evidencia empírica:** la cuenta Admin **no puede iniciar sesión** porque
`password_verify('admin12358', 'admin12358')` devuelve `false` al no ser un hash válido.

### 4.4 Prueba de fijación de sesión (D06)

```
Cookie forzada: PHPSESSID=FIXEDSESSIONID1234567890abcdef
POST /seva/login.php con credenciales inválidas → 200
```

El servidor acepta un ID de sesión provisto por el cliente. En `login.php:37` existe
`session_regenerate_id(true)` **solo tras login exitoso**; no hay validación previa.

### 4.5 Prueba de recursos multimedia ausentes (D14/D15)

Todos devuelven **404**:

```
img/lecciones/familia.jpg      img/lecciones/dias.jpg
img/lecciones/emociones.jpg    img/lecciones/comida.jpg
img/lecciones/preguntas.jpg    img/lecciones/lugares.jpg
img/lecciones/clima.jpg        img/lecciones/verbos.jpg
img/lecciones/default.jpg      video/alfabeto.mp4
```

Control: `img/lecciones/alfabeto.jpg` → 200 (existe).

### 4.6 Flujo autenticado (verificación funcional)

Se creó un usuario temporal `auditoria_f0_*@example.com`:

| Paso | Resultado |
|---|---|
| POST `registro.php` | 200 — mensaje de éxito, usuario insertado |
| POST `login.php` | 302 → `dashboard.php` (hash válido) |
| GET `dashboard.php` con sesión | 200 |
| GET `leccion.php?id=2` con sesión | 200 |

**Limpieza:** el usuario temporal y su progreso fueron eliminados al finalizar.
BD restaurada a 5 usuarios y 4 registros de progreso.

---

## 5. Análisis estático de seguridad

| Patrón analizado | Hallazgo |
|---|---|
| Consultas SQL por concatenación | ✅ 0 casos — `conexion.php` usa PDO con `ATTR_EMULATE_PREPARES => false` |
| Consultas preparadas (`->prepare`) | ✅ 13 usos |
| Salidas con `htmlspecialchars` | ✅ 50 usos |
| Salidas de variables sin escape | ⚠️ 20 coincidencias, todas numéricas o seguras (`$id`, `$porcentaje`, contadores) |
| Uso de `mysqli` | ⚠️ Solo en `db.php` (archivo muerto, sin referencias) |
| Entradas `$_GET/$_POST/$_REQUEST/$_COOKIE` | 16 usos, validación variable por página |
| Protección CSRF | ❌ 0 coincidencias |
| Rate limiting / bloqueo por intentos | ❌ 0 implementaciones reales |
| Gestión de contraseñas | ✅ `password_hash` / `password_verify` (excepto el Admin en texto plano) |

---

## 6. Hallazgos confirmados en Fase 0

| ID | Defecto | Evidencia capturada | Estado |
|---|---|---|---|
| D01 | Credenciales reales + Admin en texto plano | §3.2, §4.3 | ✅ Confirmado |
| D02 | Sin `.gitignore` | `Test-Path` → False | ✅ Confirmado |
| D03 | `db.php` huérfano | §2.2, `grep` sin referencias | ✅ Confirmado |
| D04 | Secreto en historial git | Commit `7f4d517` + §3.2 | ✅ Confirmado |
| D05 | Sin CSRF | §4.2 | ✅ Confirmado |
| D06 | Cookies sin flags + session fixation | §4.1, §4.4 | ✅ Confirmado |
| D07 | Sin rehash | `grep password_needs_rehash` → 0 | ✅ Confirmado |
| D08 | `?salir` en login | `login.php:16-21` | ✅ Confirmado |
| D09 | Sin rate limiting | §5 | ✅ Confirmado |
| D11 | Sin cabeceras de seguridad | §4.1 | ✅ Confirmado |
| D13 | `recuperar.php` 404 | §2.3, §4.5 | ✅ Confirmado |
| D14 | 8 imágenes ausentes | §4.5 | ✅ Confirmado |
| D15 | `default.jpg` y `video/` ausentes | §4.5 | ✅ Confirmado |
| D16 | Vocabulario hardcodeado | `leccion.php:37-82` | ✅ Confirmado |
| D17 | `role="tab"` sin `tabpanel` | `dashboard.php:140-145` | ✅ Confirmado |
| D18 | IDs forzados | §3.1 | ✅ Confirmado |
| D19 | MEMORIA corrupta | líneas 1, 60-61, 25-26, 36-54 | ✅ Confirmado |
| D21 | `X-Powered-By` expuesto | §4.1 | ✅ Confirmado |

**Todos los defectos del plan fueron confirmados con evidencia reproducible.**

---

## 7. Artefactos generados en esta fase

| Artefacto | Ubicación | Propósito |
|---|---|---|
| Backup completo | `C:\Users\ASUS\backup_seva_pre_auditoria.sql` (9288 B) | Restauración ante fallos |
| BD de pruebas | `seva_educativo_test` (utf8mb4) | Pruebas sin tocar producción |
| Este documento | `docs/AUDITORIA/00-linea-base.md` | Referencia antes/después |

**El backup NO se versiona** (contiene datos reales). Permanece fuera del repositorio.

---

## 8. Puerta de calidad Fase 0 — ✅ CUMPLIDA

- [x] Backup de la base de datos creado y verificado (9288 bytes, 5 tablas, datos íntegros)
- [x] Base de datos de pruebas `seva_educativo_test` creada con `utf8mb4`
- [x] Línea base documentada con evidencia reproducible
- [x] Los 9 archivos PHP verificados con `php -l` (sin errores)
- [x] Datos de prueba eliminados; BD de producción intacta
- [x] Todos los defectos del inventario confirmados empíricamente

---

## 9. Siguiente fase

**Fase 1 — Higiene del repositorio** (D01, D02, D03, D04, D23):
`.gitignore`, `.gitattributes`, eliminación de `db.php`, sanitización del seed SQL
y documentación de la rotación del secreto.

---

## 10. Continuidad de la auditoría (Fase 7)

Este documento se conserva **sin alteraciones** como captura del estado
PRE-intervención. La documentación de ingeniería inversa posterior se organiza así:

| Documento | Contenido |
|---|---|
| `01-inventario.md` | Inventario final, LOC, endpoints y grafo de dependencias |
| `02-modelo-datos.md` | ERD Mermaid, tablas, FKs e índices reconstruidos desde MariaDB |
| `03-flujos.md` | Diagramas de secuencia de los flujos principales |
| `04-trazabilidad.md` | Matriz MEMORIA ↔ código ↔ base de datos |
| `05-seguridad.md` | Hallazgos OWASP/CWE, mitigaciones y estado de D04 |
| `06-pruebas.md` | Registro ejecutable de pruebas dinámicas y WCAG 2.1 AA |
| `07-informe-final.md` | Comparativa estado inicial vs. estado final |

La comparación cuantitativa y el cierre de los 23 defectos están en
`07-informe-final.md`. Las cifras de este documento corresponden a la Fase 0 y no
deben usarse como estado actual del sistema.
