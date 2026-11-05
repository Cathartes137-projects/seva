# 05 — Seguridad: hallazgos, mitigaciones y riesgos residuales

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Fase:** 7 (D20) · **Fecha:** 2026-10-21
**Alcance:** aplicación PHP + Apache + MariaDB en `http://localhost/seva/`
**Método:** revisión de código, inspección de configuración, pruebas HTTP y análisis de historial git

---

## 1. Controles implementados

| Control | Implementación | Evidencia |
|---|---|---|
| CSRF | Token de 32 bytes por sesión, validado en los 7 POST | `seguridad.php:136-207`; pruebas C-01…C-07 |
| Sesión | `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS, `use_strict_mode`, regeneración tras login | `seguridad.php:49-85`; cookie observada en `curl -I` |
| Contraseñas | `password_hash(PASSWORD_DEFAULT)` + `password_verify` + rehash | `seguridad.php:220-243`; prueba R-01 |
| Rate limiting | 5 fallos por IP + correo → 900 s | `seguridad.php:245-363`; prueba L-01 |
| Honeypot | Campo `seva_web` oculto; éxito simulado | `seguridad.php:365-388` |
| Anti-enumeración | Respuesta idéntica con/sin cuenta en recuperación | `recuperar.php:60-140`; prueba P-01 |
| Tokens de recuperación | 32 bytes aleatorios, SHA-256 en BD, 30 min, un solo uso | `recuperar.php`, `restablecer.php`; pruebas P-02…P-04 |
| SQL injection | PDO preparado, `EMULATE_PREPARES=false` | `conexion.php:11-15`; 27 `prepare()`; prueba I-01 |
| XSS | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` en salidas | 59 usos; pruebas X-01, X-02 |
| Cabeceras | `nosniff`, `DENY`, `Referrer-Policy`, `Permissions-Policy`, CSP, HSTS condicional | `.htaccess:36-79`; salida de `curl -I` |
| Errores | `display_errors=Off`, `log_errors=On`, sin `X-Powered-By` | `.htaccess:20-31, 58-59` |
| Archivos sensibles | `.sql`, `.env`, `.git`, logs bloqueados por Apache | `.htaccess:85-90` |
| HTTPS/HSTS | Redirección y HSTS solo fuera de localhost | `.htaccess:67-79` |
| Secretos | Credenciales por `DB_*`; seed sin datos reales; `.gitignore` | `conexion.php:3-6`; `.gitignore` |

---

## 2. Mapa OWASP Top 10 (2021)

| Categoría | Estado | Evidencia / observación |
|---|---|---|
| A01 Broken Access Control | ⚠️ Parcial | Páginas privadas exigen sesión (302); `leccion.php?id` casteado a entero. No hay roles ni panel administrativo |
| A02 Cryptographic Failures | ⚠️ Parcial | Bcrypt para contraseñas y SHA-256 para tokens; HTTPS condicional; sin cifrado en reposo |
| A03 Injection | ✅ Mitigado | PDO preparado y escapado de salidas; SQLi y XSS probados |
| A04 Insecure Design | ⚠️ Parcial | Rate limit, honeypot y tokens de un solo uso; sin MFA ni verificación de correo |
| A05 Security Misconfiguration | ✅ Mitigado | Cabeceras, CSP, errores ocultos, `.git` bloqueado, `X-Powered-By` eliminado |
| A06 Vulnerable Components | ⚠️ Parcial | PHP 8.2.12 y MariaDB 10.4.32 (10.4 sin soporte desde 2024-06); sin gestor de dependencias |
| A07 Auth Failures | ⚠️ Parcial | Bloqueo por intentos, rehash y regeneración; política mínima de 8 caracteres, sin MFA |
| A08 Data Integrity Failures | ⚠️ Parcial | Repositorio git sin CI/CD ni firmas; fuera del alcance funcional |
| A09 Logging Failures | ⚠️ Parcial | `error_log` de eventos de seguridad; sin centralización ni alertas |
| A10 SSRF | ✅ No aplica | La aplicación no realiza peticiones salientes |

---

## 3. Matriz de hallazgos (D01–D23)

| ID | Hallazgo | CWE / OWASP | Sev. | Mitigación aplicada | Estado |
|---|---|---|---|---|---|
| D01 | Credenciales reales y Admin en texto plano en el dump | CWE-798 / A07 | 🔴 Crítico | Dump eliminado; seed con datos ficticios y bcrypt | ✅ Resuelto |
| D02 | Sin `.gitignore` | CWE-538 / A05 | 🟠 Alto | `.gitignore` con secretos, dumps y logs | ✅ Resuelto |
| D03 | `db.php` muerto con credenciales | CWE-798 / A05 | 🟠 Alto | Archivo eliminado; 0 referencias | ✅ Resuelto |
| D04 | Secreto en historial git | CWE-798 / A02 | 🟠 Alto | Fuera del árbol actual; contraseña demo rotada; historial no reescrito | ⚠️ Mitigado |
| D05 | Sin CSRF en 5 formularios | CWE-352 / A01 | 🟠 Alto | Token en los 7 POST; rechazo verificado | ✅ Resuelto |
| D06 | Cookies sin flags y fijación de sesión | CWE-614, CWE-384 / A07 | 🟡 Medio | Flags, `strict_mode` y regeneración | ✅ Resuelto |
| D07 | Sin rehash de hashes heredados | CWE-916 / A02 | 🟡 Medio | `password_needs_rehash` en login; probado 04 → 10 | ✅ Resuelto |
| D08 | Logout por `?salir` en login | CWE-598 / A01 | 🟢 Bajo | Solo `logout.php` | ✅ Resuelto |
| D09 | Sin rate limiting | CWE-307 / A07 | 🟠 Alto | 5 fallos → 15 min por IP + correo | ✅ Resuelto |
| D10 | Sin honeypot | CWE-799 / A04 | 🟡 Medio | Campo trampa en login, registro y recuperación | ✅ Resuelto |
| D11 | Sin cabeceras de seguridad | CWE-693 / A05 | 🟠 Alto | 5 cabeceras + CSP en local; HSTS condicional | ✅ Resuelto |
| D12 | Sin HTTPS/HSTS | CWE-319 / A02 | 🟡 Medio | Redirección y HSTS condicionados a producción | ✅ Resuelto |
| D13 | `recuperar.php` inexistente (404) | — / A04 | 🟠 Alto | Flujo completo con tokens | ✅ Resuelto |
| D14 | 8 imágenes de lección ausentes | — / A05 | 🟡 Medio | `default.svg` + `is_file()` | ✅ Resuelto |
| D15 | `default.jpg` y `video/` ausentes | — / A05 | 🟢 Bajo | SVG + carpeta documentada | ✅ Resuelto |
| D16 | Vocabulario hardcodeado | — / A04 | 🟡 Medio | `vocabulario_senas` + 50 señas | ✅ Resuelto |
| D17 | `role="tab"` sin `tabpanel` | — / A04 | 🟢 Bajo | `role="group"` + `aria-pressed` | ✅ Resuelto |
| D18 | IDs forzados y AUTO_INCREMENT desalineado | — / A04 | 🟢 Bajo | Seed 1..n verificado; BD viva no renumerada | ✅ Resuelto |
| D19 | MEMORIA corrupta | CWE-1059 / A05 | 🟠 Alto | Pendiente de la Fase 8 | ⏳ Pendiente |
| D20 | Sin auditoría documentada | CWE-1059 / A05 | 🟡 Medio | Esta carpeta `docs/AUDITORIA/` | ✅ Resuelto |
| D21 | Errores PHP y versión expuestos | CWE-209 / A05 | 🟡 Medio | `display_errors=Off`; `X-Powered-By` eliminado | ✅ Resuelto |
| D22 | Validación de nombre solo en cliente | CWE-602 / A04 | 🟢 Bajo | `mb_strlen` 3–100 en servidor | ✅ Resuelto |
| D23 | Conflictos CRLF/LF | — / A08 | 🟡 Medio | `.gitattributes` con `eol=lf` | ✅ Resuelto |

**Resultado:** 21 resueltos · 1 mitigado (D04) · 1 pendiente de Fase 8 (D19).

---

## 4. Estado de rotación de credenciales (D04)

### 4.1 Evidencia

| Verificación | Comando | Resultado |
|---|---|---|
| Secreto en el árbol actual | `Select-String -Path *.php,*.sql,DataBase\*.sql -Pattern 'admin12358'` | **0 coincidencias** |
| Secreto en el historial | `git log --oneline -S'admin12358' --all` | Presente en 5 commits (`b2dd24a`, `649c2ea`, `627afd5`, `13c4de4`, `b136804`) |
| Archivo vulnerable eliminado | `Test-Path db.php` | `False` |
| Dump vulnerable eliminado | `git ls-files DataBase/sevaeducativo.sql` | Sin resultados |
| Seed actual | `DataBase/seed_demo.sql:41-42` | Bcrypt; `admin@example.com / Seva.Admin.2026` |

### 4.2 Decisión aplicada (confirmada en el plan)

- Se **rotó** la contraseña de la cuenta de demostración y se reemplazó por un hash bcrypt.
- Se **sanearon** los archivos actuales y el seed; el secreto ya no es válido en una instalación nueva.
- **No** se reescribió el historial (`git filter-repo` descartado por decisión del proyecto).
- **Riesgo aceptado:** cualquier clon del repositorio conserva el texto `admin12358` en commits antiguos.

### 4.3 Recomendaciones

1. Rotar cualquier credencial real que haya reutilizado esa contraseña (correo, base de datos, servicios).
2. Mantener el repositorio privado y activar *secret scanning* en GitHub.
3. No reutilizar la contraseña en entornos nuevos.
4. En producción, definir `DB_USER`/`DB_PASS` por variables de entorno; nunca `root` sin contraseña.

---

## 5. Riesgos residuales y hallazgos nuevos

| ID | Riesgo | Sev. | Detalle | Recomendación |
|---|---|---|---|---|
| N-01 | CSP con `'unsafe-inline'` | 🟡 Medio | Scripts y estilos inline permitidos | Extraer JS a archivos y usar nonces (deuda ya documentada en `.htaccess`) |
| N-02 | Contraste de alertas de error | 🟢 Bajo | `#D6465B` sobre `#FDECEF` = **3,77:1** (AA exige 4,5:1) | Oscurecer el token `--danger` (p. ej. `#B4233A`) |
| N-03 | Sin subtítulos WebVTT | 🟢 Bajo | Solo transcripción textual | Añadir `<track kind="captions">` cuando existan clips |
| N-04 | Registro sin rate limit | 🟢 Bajo | Solo honeypot; creación masiva posible | Reutilizar `intentos_login` por IP en `registro.php` |
| N-05 | Cambio de contraseña no invalida otras sesiones | 🟢 Bajo | No hay tabla de sesiones | Forzar re-login o almacenar *session version* |
| N-06 | MariaDB 10.4 sin soporte | 🟡 Medio | Fin de soporte en 2024-06 | Actualizar a 10.11 LTS / 11.x |
| N-07 | Sin cifrado en reposo ni roles | 🟡 Medio | Datos personales en claro en la BD | Evaluar cifrado de disco y mínimo privilegio |
| N-08 | Sin monitoreo centralizado | 🟢 Bajo | Solo `error_log` local | Recolector de logs y alertas |
| N-09 | Recuperación sin envío de correo | 🟢 Bajo | Enlace solo en `error_log`/pantalla local | Integrar SMTP antes de producción |
| N-10 | BD viva con datos reales e IDs legacy | 🟡 Medio | No versionada; IDs `1234567899…` | Plan de migración de datos y anonimización |

---

## 6. Evidencia HTTP (resumen)

```http
HTTP/1.1 200 OK
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; media-src 'self'; connect-src 'self'
Set-Cookie: PHPSESSID=...; path=/; HttpOnly; SameSite=Lax
Cache-Control: no-store, no-cache, must-revalidate
```

Las pruebas completas (comandos, entradas y salidas) están en `06-pruebas.md`.

---

## 7. Conclusión

El sistema pasó de **0 mecanismos CSRF, 0 rate limiting y cabeceras ausentes** a un modelo con defensa en profundidad: sesión endurecida, CSRF en todos los POST, límite de intentos, honeypot, tokens de recuperación de un solo uso, consultas preparadas y salidas escapadas.

Los riesgos que permanecen son **conocidos, documentados y acotados**: CSP con inline, contraste de una alerta, ausencia de subtítulos, ciclo de vida de MariaDB y el secreto histórico (D04). Ninguno permite una toma de control directa en el estado actual.
