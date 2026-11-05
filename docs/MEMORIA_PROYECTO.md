# Documento Técnico de Migración - Proyecto SEVA (Sistema Educativo de Lengua de Señas)

**Versión:** 2.0
**Última actualización:** 2026-10-21
**Estado:** reparado y actualizado en la Fase 8 (D19)
**Documentación complementaria:** `docs/AUDITORIA/README.md`

> Este documento fue reconstruido durante la Fase 8 a partir del original, que
> contenía caracteres corruptos al inicio, salida de terminal en §3.2 y citas
> vacías en tablas, campos, funciones y esquemas. Las afirmaciones técnicas se
> contrastaron con el código y con la base de datos `sevaeducativo`.

---

## 1. Objetivos del Proyecto

### 1.1 Propósito General
El proyecto **SEVA** (**Sistema Educativo de Lengua de Señas**) es una plataforma web educativa diseñada para facilitar el aprendizaje interactivo, continuo y accesible de la Lengua de Señas. Su propósito fundamental es eliminar las barreras de comunicación que enfrentan las personas con discapacidad auditiva (sordera total o hipoacusia en distintos grados), promoviendo la equidad digital y la inclusión social y académica.

### 1.2 Principios Pedagógicos y Marcos Inclusivos
* **Diseño Universal para el Aprendizaje (DUA)**: Estructuración del contenido en múltiples formatos visuales (videos en lengua de señas, imágenes dactilológicas y texto descriptivo) para adaptarse a diversos estilos de aprendizaje.
* **Sistema Hipermedia Adaptativo (SHA)**: Personalización del recorrido educativo mediante la evaluación continua y el seguimiento de progreso individual.
* **Repetición Espaciada**: Retención a largo plazo mediante un algoritmo de repaso programado basado en la frecuencia de uso de cada término o seña.

> **Alcance actual:** la plataforma implementa el recorrido por lecciones, el
> progreso individual y el vocabulario por lección. La tabla
> `palabras_repasadas` está preparada para la repetición espaciada, pero el
> algoritmo y su interfaz aún no se han desarrollado (ver §5.2).

### 1.3 Perfiles de Usuario Objetivo
* **Estudiantes con Discapacidad Auditiva**: Usuarios con sordera o hipoacusia que requieren formación técnica o aprendizaje de LSE de forma autónoma mediante estímulos visuales y táctiles.
* **Estudiantes Oyentes, Familiares y Comunidad**: Personas sin discapacidad auditiva interesadas en aprender lengua de señas para comunicarse de manera fluida e inclusiva.
* **Docentes y Educadores**: Profesionales en educación que crean o monitorean actividades, evaluando el desempeño de los estudiantes.
* **Administrativos / Soporte**: Personal encargado de la gestión general del sistema y actualización de contenidos.

> **Alcance actual:** la interfaz implementa el perfil de **estudiante**. Los
> perfiles docentes y administrativos son objetivos de evolución; todavía no
> existe un sistema de roles ni un panel de administración.

---

## 2. Arquitectura y Stack Tecnológico

### 2.1 Componentes del Stack
* **Servidor Web**: Apache 2.4+ / Nginx con soporte para reescritura de URLs y cabeceras de seguridad (`mod_headers` y `mod_rewrite` en Apache).
* **Backend**: **PHP 8.2** utilizando exclusivamente **PDO** (PHP Data Objects) con la codificación **`utf8mb4`**, consultas preparadas y `PDO::ATTR_EMULATE_PREPARES = false`. La antigua conexión MySQLi (`db.php`) fue eliminada en la Fase 1.
* **Base de Datos**: **MariaDB 10.4+ / MySQL 8.0+** con el esquema relacional **`sevaeducativo`** (8 tablas InnoDB, 5 claves foráneas y 20 índices).
* **Frontend**: HTML5 semántico, CSS3 estructurado mediante variables CSS para la personalización de temas (*Design Tokens*) e interacciones ligeras en JavaScript Vanilla.
* **Seguridad**: módulo central **`seguridad.php`** (sesión, CSRF, rate limiting, rehash y honeypot) y **`.htaccess`** (cabeceras, CSP, HTTPS/HSTS condicional y bloqueo de archivos sensibles).
* **Dependencias**: sin framework ni Composer; el despliegue solo requiere PHP, Apache y MariaDB.

La estructura de archivos, el grafo de dependencias y el mapa de endpoints se
detallan en `docs/AUDITORIA/01-inventario.md`.

---

## 3. Flujos de Datos y Modelo Relacional de la Base de Datos

### 3.1 Flujos de Datos Principales

#### Flujo A: Registro de Usuario (`registro.php`)
1. El usuario envía sus datos mediante método POST: `nombre`, `email`, `password`, `confirmar`, el token `csrf_token` y el campo trampa `seva_web`.
2. El servidor valida el token CSRF, descarta bots mediante el honeypot, comprueba la presencia de campos obligatorios, el formato del correo mediante `filter_var(..., FILTER_VALIDATE_EMAIL)`, una longitud mínima de contraseña (>= 8 caracteres), el nombre entre 3 y 100 caracteres (`mb_strlen`) y la coincidencia entre contraseñas.
3. Se verifica en la base de datos si el correo electrónico ya existe en la tabla `usuarios` mediante `SELECT id_usuario FROM usuarios WHERE email = ?`.
4. Si no existe, la contraseña se hashea utilizando `password_hash($password, PASSWORD_DEFAULT)`.
5. Se inserta el registro en la tabla `usuarios` con `INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)` y se retorna una confirmación de éxito. No se envía correo de verificación.

#### Flujo B: Inicio de Sesión y Control de Acceso (`login.php`)
1. El usuario ingresa sus credenciales (`email` y `password`), el token `csrf_token` y el campo trampa `seva_web`.
2. Se ejecuta una consulta preparada sobre la tabla `usuarios`: `SELECT id_usuario, nombre, email, password FROM usuarios WHERE email = ?`.
3. Antes de verificar la contraseña, se aplica el control de intentos (`seguridad_login_bloqueo`): 5 fallos por combinación de IP y correo bloquean el acceso durante 15 minutos. Los contadores se guardan en `intentos_login`.
4. Se verifica la contraseña almacenada con `password_verify($password, $hash)`. Si el hash es heredado, `seguridad_rehash_password()` lo actualiza a `PASSWORD_DEFAULT`.
5. Si es válida, se regenera el ID de sesión con `session_regenerate_id(true)` para prevenir la fijación de sesión y se inicializan las variables de sesión (`usuario_id`, `nombre`, `email`). Redirección automática a `dashboard.php`.
6. Si las credenciales fallan, se incrementa el contador en `intentos_login` y se muestra un mensaje genérico: «Correo o contraseña incorrectos».

#### Flujo C: Dashboard y Cálculo de Progreso (`dashboard.php`)
1. Se valida la existencia de `usuario_id` en la sesión. Si no existe, redirige a `login.php`.
2. Se consulta la lista completa de lecciones en `contenidos_educativos` ordenada por `orden ASC, id ASC`.
3. Se calcula el total de lecciones existentes (`count($lecciones)`) y el número de lecciones marcadas como completadas por el usuario actual en la tabla `progreso_usuario` (`SELECT COUNT(*) ... WHERE usuario_id = ? AND completado = 1`).
4. Se calcula dinámicamente el porcentaje de progreso: `round(($completadas / $total) * 100)`, con 0 si no hay lecciones.
5. Se extraen los `contenido_id` de las lecciones completadas para mostrar la insignia visual «Completada» en la interfaz.
6. El filtrado por texto y por nivel (Básico, Intermedio, Avanzado) se realiza en el navegador con botones `aria-pressed`.

#### Flujos complementarios
* **Lección (`leccion.php`)**: carga el contenido por `id` (casteado a entero), consulta `vocabulario_senas` y el progreso; el POST `marcar_completada` exige CSRF y hace `INSERT ... ON DUPLICATE KEY UPDATE` en `progreso_usuario`.
* **Perfil (`perfil.php`)**: muestra datos y estadísticas; el POST `actualizar_nombre` exige CSRF y valida el nombre entre 3 y 100 caracteres.
* **Configuración (`configuracion.php`)**: cambio de contraseña con verificación de la actual y CSRF; las preferencias de tamaño de fuente y movimiento reducido se guardan en `localStorage`.
* **Recuperación (`recuperar.php`, `restablecer.php`)**: solicitud con respuesta idéntica exista o no la cuenta; token de 32 bytes, hash SHA-256, vigencia de 30 minutos y un solo uso.
* **Cierre de sesión (`logout.php`)**: destruye la sesión y la cookie; ya no se usa `login.php?salir`.

Los diagramas de secuencia completos están en `docs/AUDITORIA/03-flujos.md`.

### 3.2 Esquema Relacional de la Base de Datos (`sevaeducativo`)

El esquema actualizado consta de **8 tablas relacionales** administradas con el motor **InnoDB** y codificación **`utf8mb4_general_ci`**:

1. **`usuarios`**: `id_usuario` (INT, PK, AUTO_INCREMENT), `nombre` (VARCHAR 100), `email` (VARCHAR 150, UNIQUE), `password` (VARCHAR 255, hash bcrypt), `fecha_registro` (TIMESTAMP).
2. **`contenidos_educativos`**: `id` (INT, PK, AUTO_INCREMENT), `titulo` (VARCHAR 150), `descripcion` (TEXT), `ruta_imagen` (VARCHAR 255), `ruta_video` (VARCHAR 255), `nivel` (ENUM: `basico`, `intermedio`, `avanzado`), `orden` (INT), `fecha_creacion` (TIMESTAMP).
3. **`progreso_usuario`**: `id` (INT, PK, AUTO_INCREMENT), `usuario_id` (INT, FK -> `usuarios.id_usuario`), `contenido_id` (INT, FK -> `contenidos_educativos.id`), `completado` (TINYINT 1), `fecha_completado` (TIMESTAMP). UNIQUE KEY en (`usuario_id`, `contenido_id`). Restricciones: `ON DELETE CASCADE ON UPDATE CASCADE`.
4. **`palabras_repasadas`**: `id_palabra` (INT, PK, AUTO_INCREMENT), `id_usuario` (INT, FK -> `usuarios.id_usuario`), `palabra` (VARCHAR 100), `frecuencia` (INT), `repeticion_espaciada` (DATE). Restricciones: `ON DELETE CASCADE ON UPDATE CASCADE`.
5. **`cursos`**: `id_curso` (INT, PK, AUTO_INCREMENT), `nombre_curso` (VARCHAR 100), `idioma_origen` (VARCHAR 50), `idioma_destino` (VARCHAR 50). Catálogo sin uso en la interfaz actual.
6. **`intentos_login`**: `id` (INT, PK, AUTO_INCREMENT), `ip` (VARCHAR 45), `email` (VARCHAR 150), `intentos` (INT), `bloqueado_hasta` (DATETIME), `actualizado` (TIMESTAMP ON UPDATE CURRENT_TIMESTAMP). UNIQUE KEY en (`ip`, `email`) e índice `idx_actualizado`. Sin FK a `usuarios` para poder registrar correos inexistentes.
7. **`recuperacion_password`**: `id` (INT, PK, AUTO_INCREMENT), `id_usuario` (INT, FK -> `usuarios.id_usuario`), `token_hash` (CHAR 64, UNIQUE, SHA-256), `expira` (DATETIME), `usado` (TINYINT 1), `creado_en` (TIMESTAMP). Índices `idx_id_usuario` y `idx_expira`; `ON DELETE CASCADE`.
8. **`vocabulario_senas`**: `id` (INT, PK, AUTO_INCREMENT), `contenido_id` (INT, FK -> `contenidos_educativos.id`), `palabra` (VARCHAR 100), `descripcion` (TEXT), `orden` (INT). UNIQUE KEY en (`contenido_id`, `palabra`) e índice (`contenido_id`, `orden`); `ON DELETE CASCADE`.

**Relaciones:** 5 claves foráneas, todas con `ON DELETE CASCADE ON UPDATE CASCADE`, y 0 registros huérfanos.

**Índices:** 20 en total (8 claves primarias + 12 secundarios; 5 de ellos únicos).

**Volumen de referencia:** 12 lecciones, 50 señas de vocabulario, 2 usuarios demo en el seed y 0 filas en `cursos`, `palabras_repasadas`, `intentos_login` y `recuperacion_password` en una instalación limpia.

El detalle de columnas, índices y volúmenes verificados está en `docs/AUDITORIA/02-modelo-datos.md`.

---

## 4. Decisiones Técnicas y de Diseño UX/UI / Accesibilidad

### 4.1 Principios de UX/UI y Leyes Cognitivas
El diseño de interfaces en SEVA se inspira en el patrón **Lingvano + M-Pesa** (lecciones breves basadas en video + navegación lineal con mínima carga de texto):

1. **Ley de Fitts**: Los elementos interactivos (botones primarios, botones secundarios y accesos de formulario) poseen dimensiones táctiles mínimas de **44×44 px**, facilitando la interacción en dispositivos móviles.
2. **Ley de Miller**: La información se fragmenta en trozos manejables (*chunks*) estructurados en niveles (Básico, Intermedio, Avanzado).
3. **Ley de Hick**: Simplificación de opciones disponibles por pantalla mediante filtros y una ruta de aprendizaje progresiva.
4. **Ley de Tesler (Conservación de la Complejidad)**: Preservación de campos indispensables en formularios de registro y login, reduciendo pasos innecesarios.
5. **Efecto Zeigarnik**: La presencia de la barra de progreso visual y el cálculo dinámico del porcentaje en el Dashboard motiva al estudiante a culminar las lecciones pendientes.
6. **Ley de Pregnanz y Ley de Proximidad**: Estructuración mediante rejillas adaptativas (`CSS Grid`), tarjetas delimitadas (`.tarjeta-leccion`) y separación clara por contraste y color.
7. **Ley de Jakob**: Adopción de flujos estandarizados de autenticación y navegación web reconocibles inmediatamente por el usuario.

### 4.2 Accesibilidad W3C y Pautas WCAG 2.1 AA
* **Navegación por Teclado**: Implementación de la pseudoclase `:focus-visible` con un contorno prominente (3 px sólido) para asegurar la visibilidad del foco.
* **Soporte para Movimiento Reducido**: Incorporación de la regla `@media (prefers-reduced-motion: reduce)` y de la preferencia `seva_reducir_movimiento` para desactivar animaciones en usuarios sensibles.
* **Contenedores Multimedia Adaptados**: Clase `.video-lse` con relación de aspecto 16:9, etiqueta de contexto `.etiqueta-lse` y transcripción visible en `.subtitulos`.
  * **Deuda pendiente:** no se han incorporado pistas WebVTT (`<track kind="captions">`) porque el repositorio aún no contiene clips; la transcripción textual es la alternativa actual (N-03).
* **Formularios Accesibles**: Etiquetas `<label for>`, `aria-label` en controles sin etiqueta visible, mensajes de error textuales y objetivos táctiles de 44 px.
* **Feedback Multimodal**: Uso combinado de iconos vectoriales, contrastes accesibles y mensajes de texto directo; los cambios de estado se anuncian con `role="status"` y `aria-live="polite"`.
* **Idioma y Estructura**: `lang="es"` en las 8 páginas PHP y en la landing (`index.html`), un `<title>` único por página y landmarks (`<main>`, `<nav>`, `<header>`).
* **Contraste**: la mayoría de combinaciones supera 4,5:1; la alerta de error (`#D6465B` sobre `#FDECEF`) queda en **3,77:1** y requiere ajuste (N-02).

La matriz de verificación WCAG y los ratios calculados están en `docs/AUDITORIA/06-pruebas.md`.

---

## 5. Estado de los Defectos y Pendientes

### 5.1 Estado de los defectos D01–D23

Los 23 defectos del plan quedan **cerrados**: 22 resueltos y D04 mitigado por la decisión expresa de no reescribir el historial de Git.

| ID | Defecto | Fase | Estado | Evidencia |
|---|---|---|---|---|
| D01 | Credenciales reales y Admin en texto plano en el dump | F1 | ✅ Resuelto | Dump eliminado; seed con datos ficticios y bcrypt |
| D02 | Sin `.gitignore` | F1 | ✅ Resuelto | `.gitignore` versionado con secretos, dumps y logs |
| D03 | `db.php` muerto con credenciales | F1 | ✅ Resuelto | Archivo eliminado; 0 referencias |
| D04 | Secreto en historial Git | F1 | ⚠️ Mitigado | Fuera del árbol actual; contraseña demo rotada; historial no reescrito |
| D05 | Sin CSRF en formularios | F2 | ✅ Resuelto | Token en los 7 POST; rechazo verificado |
| D06 | Cookies sin flags y fijación de sesión | F2 | ✅ Resuelto | `HttpOnly`, `SameSite=Lax`, `strict_mode` y regeneración |
| D07 | Sin rehash de hashes heredados | F2 | ✅ Resuelto | `password_needs_rehash`; probado `$2y$04$` → `$2y$10$` |
| D08 | Logout por `?salir` | F2 | ✅ Resuelto | `logout.php` dedicado; sesión destruida |
| D09 | Sin rate limiting | F3 | ✅ Resuelto | 5 fallos → 15 min por IP + correo |
| D10 | Sin honeypot | F3 | ✅ Resuelto | Campo `seva_web` en login, registro y recuperación |
| D11 | Sin cabeceras de seguridad | F4 | ✅ Resuelto | 5 cabeceras + CSP en local; HSTS condicional en producción |
| D12 | Sin HTTPS/HSTS | F4 | ✅ Resuelto | Redirección y HSTS condicionados a producción |
| D13 | `recuperar.php` inexistente (404) | F5 | ✅ Resuelto | Flujo completo con tokens de un solo uso |
| D14 | 8 imágenes de lección ausentes | F5 | ✅ Resuelto | `img/lecciones/default.svg`; 0 respuestas 404 |
| D15 | `default.jpg` y `video/` ausentes | F5 | ✅ Resuelto | Placeholder SVG y carpeta `video/` documentada |
| D16 | Vocabulario hardcodeado | F6 | ✅ Resuelto | Tabla `vocabulario_senas` con 50 señas |
| D17 | `role="tab"` sin `tabpanel` | F6 | ✅ Resuelto | `role="group"` + `aria-pressed` |
| D18 | IDs forzados y AUTO_INCREMENT desalineado | F6 | ✅ Resuelto | Seed `1..n` verificado en base desechable |
| D19 | MEMORIA corrupta | F8 | ✅ Resuelto | Este documento reparado y actualizado |
| D20 | Sin auditoría documentada | F7 | ✅ Resuelto | `docs/AUDITORIA/` con 8 documentos |
| D21 | Errores PHP y versión expuestos | F4 | ✅ Resuelto | `display_errors=Off`; `X-Powered-By` eliminado |
| D22 | Validación de nombre solo en cliente | F6 | ✅ Resuelto | `mb_strlen` 3–100 en servidor |
| D23 | Conflictos CRLF/LF | F1 | ✅ Resuelto | `.gitattributes` con `eol=lf` |

### 5.2 Pendientes futuros (post-plan)

**Accesibilidad y UX**
- Corregir el contraste de la alerta de error a ≥ 4,5:1 (N-02).
- Añadir subtítulos WebVTT cuando existan clips (N-03).
- Implementar *skeletons* de carga para lecciones y videos.
- Completar los Design Tokens y evaluar el modo oscuro.
- Ejecutar auditorías con Lighthouse/WAVE y pruebas manuales con teclado, zoom 200 % y lector de pantalla.

**Seguridad y operación**
- Extraer el JavaScript/CSS inline y endurecer la CSP sin `'unsafe-inline'` (N-01).
- Añadir rate limiting al registro de cuentas (N-04).
- Invalidar otras sesiones al cambiar la contraseña (N-05).
- Integrar SMTP real para la recuperación antes de producción (N-09).
- Actualizar MariaDB 10.4 (sin soporte desde 2024-06) a 10.11 LTS o 11.x (N-06).
- Evaluar cifrado en reposo y mínimo privilegio/roles (N-07).
- Centralizar logs y alertas de seguridad (N-08).
- Planificar la migración y anonimización de los datos vivos (N-10).
- Mantener el repositorio privado y activar *secret scanning* por D04.

**Producto**
- Desarrollar la repetición espaciada sobre `palabras_repasadas`.
- Definir roles de docente/administrador y un panel de gestión de contenidos.
- Implementar la verificación de correo en el registro.

---

## 6. Guía de Ejecución y Despliegue

### 6.1 Prerrequisitos del Servidor
* **Servidor Web**: Apache 2.4+ con `mod_headers` y `mod_rewrite` (o Nginx con reglas equivalentes).
* **Lenguaje de Programación**: PHP 8.2 o superior con las extensiones `pdo_mysql`, `mbstring`, `openssl` y `fileinfo` habilitadas.
* **Base de Datos**: MariaDB 10.4+ o MySQL 8.0+ con codificación `utf8mb4`.
* **Almacenamiento**: permisos de escritura para logs de PHP y lectura para `img/` y `video/`.

### 6.2 Pasos de Instalación Local (Entorno de Desarrollo)
1. **Clonar el repositorio**: Configurar el directorio del proyecto en el servidor web.
2. **Configurar la Base de Datos**: Crear la base de datos **`sevaeducativo`** en MariaDB/MySQL e importar `DataBase/schema.sql` y, opcionalmente, `DataBase/seed_demo.sql`. Las migraciones incrementales están en `DataBase/migrations/`.
3. **Configurar la Conexión en PHP (`conexion.php`)**: Definir `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` por variables de entorno. Usar PDO con `PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC` y `PDO::ATTR_EMULATE_PREPARES = false`.
4. **Estructura de Carpetas Multimedia**: Asegurar la presencia de `img/lecciones/` (con `default.svg`) y `video/`.
5. **Iniciar el Servidor de Pruebas**: Arrancar Apache en XAMPP y abrir `http://localhost/seva/`, o ejecutar `php -S localhost:8000 -t .` desde la raíz del proyecto.
6. **Credenciales demo** (solo con `seed_demo.sql`):
   * `admin@example.com` / `Seva.Admin.2026`
   * `demo@example.com` / `Seva.Demo.2026`

### 6.3 Verificación Rápida
* `php -l` sobre todos los archivos PHP (0 errores).
* `curl -I http://localhost/seva/login.php` para comprobar cabeceras y cookie.
* Iniciar sesión con la cuenta demo, recorrer una lección y verificar el progreso en el dashboard.
* Probar la recuperación con `APP_ENV=local` (enlace en pantalla) y con `APP_ENV=production` (sin enlace).
* El registro completo de pruebas está en `docs/AUDITORIA/06-pruebas.md`.

### 6.4 Notas para Producción
* Definir `APP_ENV=production` para ocultar el enlace de recuperación y usar `error_log`.
* Servir exclusivamente por HTTPS; `.htaccess` activa la redirección y HSTS fuera de `localhost`.
* Configurar credenciales de base de datos con un usuario de mínimo privilegio; no usar `root` sin contraseña.
* Integrar un servidor SMTP antes de habilitar la recuperación para usuarios reales (N-09).
* Revisar el backlog de §5.2 y el informe final `docs/AUDITORIA/07-informe-final.md`.

---

## 7. Documentación Relacionada

| Documento | Contenido |
|---|---|
| `docs/PLAN_REMEDIACION.md` | Plan de remediación y criterios de aceptación por fase |
| `docs/AUDITORIA/README.md` | Índice de la auditoría de ingeniería inversa |
| `docs/AUDITORIA/00-linea-base.md` | Captura PRE-intervención |
| `docs/AUDITORIA/01-inventario.md` | Archivos, LOC, endpoints y dependencias |
| `docs/AUDITORIA/02-modelo-datos.md` | ERD, tablas, FKs e índices |
| `docs/AUDITORIA/03-flujos.md` | Diagramas de secuencia |
| `docs/AUDITORIA/04-trazabilidad.md` | Matriz MEMORIA ↔ código ↔ BD |
| `docs/AUDITORIA/05-seguridad.md` | OWASP/CWE, D01–D23 y riesgos residuales |
| `docs/AUDITORIA/06-pruebas.md` | 38 pruebas dinámicas y WCAG 2.1 AA |
| `docs/AUDITORIA/07-informe-final.md` | Comparativa antes/después |
| `docs/PR_FINAL.md` | Informe y checklist del Pull Request final |
