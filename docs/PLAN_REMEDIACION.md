# Plan de Remediación y Auditoría de Ingeniería Inversa — Proyecto SEVA
**Rama base:** `seva-antigraity-migracion` · **Rama de trabajo:** `fix/auditoria-defectos`
**Fecha:** 2026-10-20 · **Estado:** fases 0–8 completadas; PR pendiente de apertura (2026-10-21)

> Fuente de verdad del proyecto: `docs/MEMORIA_PROYECTO.md`.
> Reglas de trabajo: `docs/.antigravityrules.md` (planificar antes de codificar, un módulo por vez, YAGNI, WCAG 2, 44×44 px, design tokens).

---

## 1. Decisiones confirmadas

| Decisión | Elección |
|---|---|
| Recuperación de contraseña (D13) | Flujo completo con tokens, sin envío real de correo (enlace en local + log) |
| Secreto en historial git (D04) | Rotar contraseña y sanear solo los archivos actuales; **no** se reescribe historial (`filter-repo` descartado) |
| Commits locales | Publicarlos en `origin/seva-antigraity-migracion` antes de ramificar |
| Rama de trabajo | `fix/auditoria-defectos` |

---

## 2. Contexto verificado (línea base)

| Elemento | Estado |
|---|---|
| Remoto `origin` | `https://github.com/Cathartes137-projects/seva.git` (HTTPS) ✔ |
| Autenticación | Git Credential Manager (`credential.helper=manager`) ✔ |
| Identidad git | `Cathartes137-projects` / `edwinsegundocn@gmail.com` ✔ |
| GitHub CLI (`gh`) | No instalado → PR se crea por web |
| Entorno | PHP 8.2.12, MariaDB 10.4.32, Apache :80, MySQL :3306 ✔ |
| Archivos PHP | 9 archivos, todos sin errores de sintaxis (`php -l`) |
| Rama `main` | Desfasada (`ahead 3 / behind 2`) — fuera de alcance |

---

## 3. Vinculación GitHub ↔ git (verificación)

```powershell
git remote -v
git fetch --prune origin
git ls-remote --heads origin                                # prueba de lectura
git push --dry-run origin seva-antigraity-migracion         # prueba de escritura
```

- Si falla la escritura: `git credential-manager github login` (o PAT con scope `repo`).
  No incrustar tokens en la URL del remoto.
- Alternativa SSH solo si GCM falla: `ssh-keygen -t ed25519` + alta de la clave en GitHub.
- PR final (sin `gh`): `https://github.com/Cathartes137-projects/seva/compare/seva-antigraity-migracion...fix/auditoria-defectos`

---

## 4. Estrategia de ramas y commits

```
main ──●──●
        └── seva-antigraity-migracion ──●──● (sincronización + plan)
                                        └── fix/auditoria-defectos  ← trabajo de remediación
```

```powershell
git tag pre-auditoria HEAD
git push origin pre-auditoria
git push origin seva-antigraity-migracion
git switch -c fix/auditoria-defectos seva-antigraity-migracion
git push -u origin fix/auditoria-defectos
```

- Un commit por defecto corregido (o por fase si son cohesivos).
- Convención: `tipo(alcance): descripción` → `security`, `fix`, `feat`, `chore`, `docs`, `refactor`, `test`.
- Push al cerrar cada fase + tag por fase (`fase-1` … `fase-8`).
- Rollback: `git revert` puntual sobre el tag de fase.
- Regla: no se avanza de fase sin cumplir su **puerta de calidad** (§11).

---

## 5. Inventario de defectos

| ID | Defecto | Fase | Sev. | Criterio de aceptación |
|---|---|---|---|---|
| D01 | Dump SQL con correos reales y contraseña en texto plano (`admin12358`) | F1 | 🔴 Crítico | Seed sanitizado, Admin con hash bcrypt válido, import limpio |
| D02 | Sin `.gitignore` | F1 | 🟠 Alto | Ignora dumps, `.env`, logs, temporales |
| D03 | `db.php` muerto (mysqli root/sin clave, sin referencias) | F1 | 🟠 Alto | Eliminado; `grep` sin referencias |
| D04 | Secreto expuesto en historial git | F1 | 🟠 Alto | Contraseña rotada y documentada en la auditoría |
| D23 | Conflictos CRLF/LF entre ramas (ya causó 1 conflicto) | F1 | 🟡 Medio | `.gitattributes` con `* text=auto eol=lf` y binarios |
| D05 | Sin CSRF en 5 formularios POST (login, registro, perfil, configuración, lección) | F2 | 🟠 Alto | Token en todos; POST sin token rechazado |
| D06 | Cookies de sesión sin flags | F2 | 🟡 Medio | HttpOnly, SameSite=Lax, Secure en HTTPS |
| D07 | Sin rehash de contraseñas heredadas | F2 | 🟡 Medio | `password_needs_rehash` probado en login |
| D08 | `login.php?salir` duplica el logout vía GET | F2 | 🟢 Bajo | Solo `logout.php`; enlaces verificados |
| D09 | Sin rate limiting en login | F3 | 🟠 Alto | Bloqueo 5 intentos / 15 min; reset en éxito |
| D10 | Sin honeypot anti-bots en login/registro | F3 | 🟡 Medio | Campo trampa; rechazo silencioso sin afectar humanos |
| D11 | Sin cabeceras de seguridad ni CSP | F4 | 🟠 Alto | `curl -I` muestra todas; fuentes e inline funcionando |
| D12 | Sin HTTPS/HSTS | F4 | 🟡 Medio | Redirección condicional + HSTS documentado (comentado en local) |
| D21 | Errores PHP potencialmente expuestos | F4 | 🟡 Medio | `display_errors=Off`, `log_errors=On` |
| D13 | `recuperar.php` enlazado pero no existe (404) | F5 | 🟠 Alto | Flujo solicitud + reset con tokens funcionando |
| D14 | 8 imágenes de lecciones referenciadas ausentes | F5 | 🟡 Medio | Placeholder + sin 404 visibles |
| D15 | `img/lecciones/default.jpg` inexistente y carpeta `video/` ausente | F5 | 🟢 Bajo | Placeholder SVG + `video/.gitkeep` |
| D16 | Vocabulario hardcodeado (lección "Saludos" para todos los IDs) | F6 | 🟡 Medio | Tabla `vocabulario_senas` + seed + fallback |
| D17 | `role="tablist"/tab` sin `tabpanel` en filtros del dashboard | F6 | 🟢 Bajo | `role="group"` + `aria-pressed` |
| D18 | IDs forzados (1234567899…) y AUTO_INCREMENT desalineado | F6 | 🟢 Bajo | Seed normalizado 1..n |
| D22 | Validación de nombre solo en cliente en registro | F6 | 🟢 Bajo | `mb_strlen` 3–100 en servidor |
| D20 | Sin auditoría documentada del sistema | F7 | 🟡 Medio | `docs/AUDITORIA/` completo con evidencia |
| D19 | `docs/MEMORIA_PROYECTO.md` corrupta (basura inicial, texto de terminal, citas vacías) | F8 | 🟠 Alto | Documento íntegro y pendientes actualizados |

---

## 6. Fases de ejecución (escalonadas)

### Fase 0 — Preparación y línea base
**Alcance:** backups y captura del estado previo, sin tocar código de producción.
- Backup de BD: `mysqldump -u root sevaeducativo > backup_seva_pre_auditoria.sql` (fuera del repo).
- BD de pruebas: `seva_educativo_test` con `utf8mb4`.
- Capturar: `php -l` de los 9 PHP, inventario de endpoints, `curl -I` de cabeceras actuales, prueba manual de flujos (login, registro, lección, perfil, configuración).
- **Salida:** `docs/AUDITORIA/00-linea-base.md`.

### Fase 1 — Higiene del repositorio (D01, D02, D03, D04, D23)
- Separar `DataBase/schema.sql` (estructura) y `DataBase/seed_demo.sql` (datos ficticios `@example.com`).
- Usuario Admin: eliminar `admin12358` en texto plano, reemplazar por hash bcrypt generado con `password_hash()`; documentar rotación.
- `.gitignore`: `*.sql` de backup, `.env`, logs, temporales, `docs/repomix-output.xml` opcional.
- `.gitattributes`: `* text=auto eol=lf`, `*.jpg binary`.
- Eliminar `db.php` (verificado: ninguna referencia activa).
- **Regresión:** import del seed en `seva_educativo_test` sin errores.

### Fase 2 — Sesión y CSRF (D05, D06, D07, D08)
- Nuevo `seguridad.php` con:
  - `iniciar_sesion_segura()`: `session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => HTTPS])` + `session_regenerate_id(true)`.
  - `csrf_token()` / `csrf_campo()` / `csrf_validar()` con `hash_equals()`.
  - `cabeceras_seguridad()` (complementarias a `.htaccess`), `requiere_login()`.
- Integrar token en los 5 formularios POST y verificarlo al inicio del handler.
- `password_needs_rehash($hash, PASSWORD_DEFAULT)` tras login exitoso → actualización automática.
- Retirar el bloque `?salir` de `login.php`.
- **Regresión:** los 5 formularios funcionan en positivo y rechazan POST sin token.

### Fase 3 — Anti fuerza bruta (D09, D10)
- Tabla `intentos_login (id, ip, email, intentos, bloqueado_hasta, actualizado)` con `UNIQUE(ip, email)`.
- 5 fallos consecutivos → bloqueo 15 min; mensaje genérico; reset al éxito; limpieza de filas antiguas.
- Honeypot oculto en `login.php` y `registro.php`: si el campo se rellena → rechazo silencioso (misma respuesta que éxito/error neutro).
- El rate limit también protege `recuperar.php` (F5).
- **Regresión:** 6.º intento bloqueado; login legítimo tras la ventana; humanos no afectados.

### Fase 4 — Cabeceras, CSP y HTTPS (D11, D12, D21)
- `.htaccess` (Apache): `Options -Indexes`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, CSP inicial:
  ```
  default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src https://fonts.gstatic.com; img-src 'self' data:;
  script-src 'self' 'unsafe-inline'; frame-ancestors 'none';
  base-uri 'self'; form-action 'self'
  ```
- Redirección HTTP→HTTPS condicional + HSTS (comentado en localhost con instrucción de activación en producción).
- `display_errors=Off`, `log_errors=On` vía `.htaccess`/`php.ini`.
- **Deuda documentada:** sustituir `'unsafe-inline'` extrayendo JS a archivos y usando nonces.
- **Regresión:** `curl -I` muestra cabeceras; fuentes, estilos e inline intactos; errores no visibles al usuario.

### Fase 5 — Funcionalidad rota (D13, D14, D15) — ✅ completada (2026-10-21)
**Recuperación de contraseña (decisión: tokens sin envío real):**
- Tabla `recuperacion_password (id, id_usuario FK CASCADE, token_hash, expira, usado)`.
- `recuperar.php`: valida formato; **respuesta idéntica** con o sin usuario (evita enumeración); genera `random_bytes(32)`, guarda `hash('sha256', $token)` con `expira = NOW() + 30 min`; un token nuevo invalida los anteriores; aplica rate limit; `error_log` del enlace; en pantalla el enlace solo si `APP_ENV === 'local'`.
- `restablecer.php?token=...`: valida hash + vigencia + no usado, exige CSRF, longitud ≥ 8, `password_hash`, marca `usado = 1`.
**Media:**
- `img/lecciones/default.svg` (placeholder vectorial) + actualización del fallback de `dashboard.php`.
- `video/.gitkeep` + `video/README.md`; el código ya mitiga con `file_exists`.
- **Regresión:** misma respuesta para correo existente/inexistente; token de un solo uso y caducidad verificadas.
- **Evidencia (2026-10-21):** `php -l` sin errores en los 6 PHP tocados; migración `002` aplicada a `sevaeducativo` y `seva_educativo_test`; con `APP_ENV=production` los cuerpos de respuesta para correo existente e inexistente son idénticos (y sin enlace en pantalla); token reutilizado, caducado y POST sin CSRF rechazados; login con la contraseña nueva correcto; las 8 tarjetas del dashboard usan `default.svg` sin 404 y `leccion.php` valida portada y video en disco.

### Fase 6 — Datos, contenido y ARIA (D16, D17, D18, D22) — ✅ completada (2026-10-21)
- Tabla `vocabulario_senas (id, contenido_id FK, palabra, descripcion, orden)` + seed de la lección 2; `leccion.php` consulta la tabla con fallback al contenido actual.
- Filtros del dashboard: `role="group"` + `aria-pressed` (elimina el patrón tablist incompleto); JS actualizado.
- Seed con IDs normalizados 1..n y AUTO_INCREMENT coherente.
- Validación servidor del nombre en `registro.php` (`mb_strlen` 3–100).
- **Regresión:** vocabulario distinto por lección; filtros accesibles con teclado.
- **Evidencia (2026-10-21):** `php -l` sin errores; migración `003` aplicada a `sevaeducativo` y `seva_educativo_test` con 50 señas en las 12 lecciones (4 por lección, 6 en «Saludos»); import limpio en base desechable con IDs `1..12`, usuarios `1..2` y `AUTO_INCREMENT` 13/3/51 (D18 ya cubierto por el seed de F1); las lecciones 1, 2, 5 y 12 muestran tarjetas distintas y se probó el estado vacío; dashboard con `role="group"`, 4 `aria-pressed` y 0 `role="tab"/aria-selected`; registro rechaza 2 y 101 caracteres y acepta 3 (multibyte) y 100.

### Fase 7 — Auditoría de ingeniería inversa (D20) — ✅ completada (2026-10-21)
Reconstrucción del sistema desde sus artefactos + verificación antes/después:
1. `00-linea-base.md` — captura previa (Fase 0).
2. `01-inventario.md` — archivos, LOC, grafo `require/include`, mapa de endpoints (método: análisis estático).
3. `02-modelo-datos.md` — ERD Mermaid reconstruido desde el SQL, FKs, únicos, anomalías (método: `SHOW CREATE TABLE` vs dump).
4. `03-flujos.md` — diagramas de secuencia: registro, login, dashboard, lección, perfil, configuración, recuperación.
5. `04-trazabilidad.md` — matriz MEMORIA ↔ código ↔ BD (implementado / parcial / ausente).
6. `05-seguridad.md` — hallazgos con CWE/OWASP, evidencia, severidad, estado (incluye rotación de D04).
7. `06-pruebas.md` — pruebas dinámicas contra `http://localhost/seva`: CSRF negativo, SQLi/XSS, fijación de sesión, acceso sin sesión, IDOR en `leccion.php?id=`, rate limit, cabeceras.
8. `07-informe-final.md` — comparativa línea base vs estado final.

Métodos: análisis estático (`$_GET/$_POST`, concatenación SQL, salida sin escape), diff de esquema, caja negra HTTP, revisión manual de accesibilidad (teclado, zoom 200 %, contraste, `prefers-reduced-motion`).

- **Evidencia (2026-10-21):** los 8 documentos existen en `docs/AUDITORIA/` (`00` línea base conservada + `01`–`07`); 38 pruebas dinámicas ejecutadas (37 ✅ / 1 ⚠️: contraste de alerta de error 3,77:1, documentado como N-02); CSRF negativo en los 7 POST, SQLi/XSS sin bypass, fijación de sesión rechazada, rehash `$2y$04$` → `$2y$10$`, rate limit 5 fallos → 15 min, 5 cabeceras + CSP en local y HSTS condicional, recuperación con token de un solo uso/caducado y anti-enumeración con cuerpos idénticos; `php -l` sin errores en 11 archivos; 0 usuarios/intentos/tokens de prueba tras la limpieza; solo cambiaron `docs/AUDITORIA/*` y este plan.

### Fase 8 — Documentación y cierre (D19) — ✅ completada (2026-10-21)
- Reparar `MEMORIA_PROYECTO.md`: eliminar `ez<<<<` del inicio, purgar `uid=1000(sandbox)…` de §3.2, restaurar citas vacías (`utf8mb4`, nombres de flujo y tablas), actualizar §5 con lo resuelto y lo pendiente.
- `docs/AUDITORIA/README.md` como índice.
- PR `fix/auditoria-defectos → seva-antigraity-migracion` con checklist y evidencia de pruebas.

- **Evidencia (2026-10-21):** `MEMORIA_PROYECTO.md` reparada (250 líneas, sin `ez<<<<` ni `uid=1000`, 8 tablas, 22 defectos resueltos + D04 mitigado); `docs/AUDITORIA/README.md` indexa los 8 documentos con enlaces relativos válidos; `docs/PR_FINAL.md` contiene resumen, evidencia de las 38 pruebas, checklist D01–D23 y riesgos; `php -l` sigue en 0 errores y no se modificó código fuente. El PR queda preparado para abrirse por web porque `gh` no está instalado.

---

## 7. Matriz de verificación

| Prueba | Comando / acción |
|---|---|
| Sintaxis | `C:\xampp\php\php.exe -l <archivo>` |
| Importación BD | `mysql -u root seva_educativo_test < DataBase/seed_demo.sql` + `SHOW TABLES` |
| CSRF | POST sin token → rechazo, sin cambios en BD |
| Rate limit | 6 logins fallidos → bloqueo; válido tras expirar |
| Cabeceras | `curl -I http://localhost/seva/login.php` |
| XSS / SQLi | Payloads en nombre/email/id → escape y consultas parametrizadas |
| Acceso | `dashboard.php` sin sesión → redirect; `leccion.php?id=999999` → fallback |
| Recuperación | Mismo mensaje con/without usuario; token caducado y reutilizado rechazados |
| Accesibilidad | Tab completo, foco 3 px, zoom 200 %, `aria-pressed` en filtros |

---

## 8. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Refactor de sesión rompe páginas | Cambio incremental, `php -l` + prueba de cada formulario por fase |
| CSP rompe fuentes o estilos inline | CSP permisiva inicial (`'unsafe-inline'`), deuda documentada |
| Pérdida de datos locales | `mysqldump` previo + BD de pruebas separada |
| Exponer el push de la base a colaboradores | Aprobado explícitamente; rama aislada para el trabajo |
| Recuperación sin SMTP | Enlace solo en local y en log hasta definir servidor de correo |
| Cambios en `main` ajeno a la rama | Fuera de alcance; todo el trabajo vive en `fix/auditoria-defectos` |

---

## 9. Entregables

1. Rama `fix/auditoria-defectos` publicada y PR abierto.
2. `seguridad.php`, `.htaccess`, `.gitignore`, `.gitattributes`.
3. `DataBase/schema.sql` + `DataBase/seed_demo.sql` sanitizados.
4. `recuperar.php`, `restablecer.php`, tabla `recuperacion_password`.
5. Tabla `vocabulario_senas` + seed.
6. `docs/AUDITORIA/` (8 documentos) y `MEMORIA_PROYECTO.md` reparada.
7. Registro de pruebas y comparativa antes/después.

---

## 10. Orden de commits previsto

1. `chore(repo): gitignore, gitattributes y limpieza de db.php`
2. `fix(db): seed sanitizado y credenciales fuera del repositorio`
3. `security(session): bootstrap seguro y cookies endurecidas`
4. `security(csrf): proteccion en formularios POST`
5. `security(auth): rate limiting y honeypot`
6. `security(headers): CSP, HSTS y errores ocultos`
7. `feat(auth): recuperacion de contrasena con tokens`
8. `fix(media): placeholders de imagenes y video`
9. `feat(leccion): vocabulario desde base de datos`
10. `fix(a11y): filtros con aria-pressed`
11. `docs(auditoria): informe de ingenieria inversa`
12. `docs(memoria): reparacion y actualizacion de pendientes`

---

## 11. Puertas de calidad por fase (no se avanza sin cumplirlas)

| Fase | Puerta |
|---|---|
| F0 | Backup creado, BD de pruebas lista, línea base documentada |
| F1 | Import limpio en `seva_educativo_test`; sin datos reales; `php -l` OK |
| F2 | 5 formularios rechazan POST sin token; login correcto sigue funcionando |
| F3 | 6.º intento bloqueado; login válido tras ventana; honeypot no afecta humanos |
| F4 | `curl -I` muestra las cabeceras; fuentes y estilos intactos; errores no visibles |
| F5 | Respuesta uniforme en recuperación; token de un solo uso y caducidad OK |
| F6 | Vocabulario por lección desde BD; filtros con `aria-pressed`; teclado completo |
| F7 | 8 documentos de auditoría con evidencia y comparativa |
| F8 | MEMORIA sin basura; enlaces internos válidos; PR preparado con checklist |

---

## 12. Próximos pasos

- [x] F0: backup, BD de pruebas y línea base (2026-10-21).
- [x] F1: higiene del repositorio, seed sanitizado y secretos fuera del árbol (2026-10-21).
- [x] F2: sesión endurecida, CSRF, rehash y logout dedicado (2026-10-21).
- [x] F3: rate limiting y honeypot (2026-10-21).
- [x] F4: cabeceras, CSP, HTTPS/HSTS y errores ocultos (2026-10-21).
- [x] F5: recuperación con tokens, placeholder SVG y carpeta `video/` (2026-10-21).
- [x] F6: vocabulario por lección, filtros accesibles, seed normalizado y validación de nombre (2026-10-21).
- [x] F7: auditoría de ingeniería inversa con 8 documentos y 38 pruebas (2026-10-21).
- [x] F8: MEMORIA reparada, índice de auditoría y PR preparado (2026-10-21).
- [ ] Publicar `seva-antigraity-migracion` si aún no está en el remoto.
- [ ] PR final con evidencia.
