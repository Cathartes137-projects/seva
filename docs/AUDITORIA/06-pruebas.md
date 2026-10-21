# 06 — Registro de pruebas dinámicas y accesibilidad WCAG 2.1 AA

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Fase:** 7 (D20) · **Fecha:** 2026-10-21
**Entorno:** PHP 8.2.12 · MariaDB 10.4.32 · Apache 2.4.58 · `http://localhost/seva/`
**Método:** caja negra HTTP con `curl`, consultas SQL de verificación y análisis estático de accesibilidad

---

## 1. Resumen de resultados

| Resultado | Casos |
|---|---:|
| ✅ Superado | 37 |
| ⚠️ Parcial | 1 |
| ❌ Fallido | 0 |
| **Total** | **38** |

**Única parcial:** contraste de las alertas de error (`#D6465B` sobre `#FDECEF` = **3,77:1**, mínimo AA 4,5:1). Se documenta como hallazgo N-02; no se corrigió en esta fase porque el alcance de F7 es la auditoría.

---

## 2. Datos de prueba y limpieza

Se usaron cuentas temporales con el sufijo `.f7@example.com`:

| Cuenta | Uso |
|---|---|
| `auditoria.f7@example.com` | Sesión, fijación, CSRF autenticado |
| `rehash.f7@example.com` | Hash legado con coste 4 |
| `xss.f7@example.com` | Nombre con `<script>` |
| `recovery.f7@example.com` | Solicitud y consumo de token |
| `idor.f7@example.com` | Casteo de `id` en `leccion.php` |
| `csrf.f7@example.com` | CSRF en `restablecer.php` |
| `logout.f7@example.com` | Cierre de sesión |

**Limpieza final verificada**

```sql
DELETE FROM usuarios WHERE email LIKE '%.f7@example.com';
DELETE FROM intentos_login WHERE email LIKE '%f7@example.com' OR email LIKE 'recuperacion:%';

SELECT COUNT(*) FROM usuarios WHERE email LIKE '%.f7@example.com'; -- 0
SELECT COUNT(*) FROM intentos_login WHERE email LIKE '%f7@example.com'; -- 0
SELECT COUNT(*) FROM recuperacion_password; -- 0
```

Las cuentas reales de la base viva no se modificaron.

---

## 3. Control de acceso

| ID | Prueba | Comando | Esperado | Obtenido | Estado |
|---|---|---|---|---|---|
| A-01 | Dashboard sin sesión | `curl -s -o NUL -w "%{http_code} %{redirect_url}" http://localhost/seva/dashboard.php` | 302 → `login.php` | `302 → login.php` | ✅ |
| A-02 | Perfil sin sesión | idem `perfil.php` | 302 → `login.php` | `302 → login.php` | ✅ |
| A-03 | Configuración sin sesión | idem `configuracion.php` | 302 → `login.php` | `302 → login.php` | ✅ |
| A-04 | Lección sin sesión | idem `leccion.php?id=1` | 302 → `login.php` | `302 → login.php` | ✅ |

```powershell
foreach ($p in @('dashboard.php','perfil.php','configuracion.php','leccion.php?id=1')) {
  curl.exe -s -o NUL -w "$p : %{http_code} -> %{redirect_url}`n" "http://localhost/seva/$p"
}
```

---

## 4. CSRF (negativo y positivo)

| ID | Petición | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| C-01 | `POST login.php` sin token | Rechazo | `302 → login.php?error=csrf` | ✅ |
| C-02 | `POST registro.php` sin token | Rechazo | `302 → registro.php?error=csrf` | ✅ |
| C-03 | `POST recuperar.php` sin token | Rechazo | `302 → recuperar.php?error=csrf` | ✅ |
| C-04 | `POST perfil.php` sin token (con sesión) | Rechazo | `302 → perfil.php?error=csrf` | ✅ |
| C-05 | `POST configuracion.php` sin token (con sesión) | Rechazo | `302 → configuracion.php?error=csrf` | ✅ |
| C-06 | `POST leccion.php` sin token (con sesión) | Rechazo | `302 → leccion.php?id=1?error=csrf` | ✅ |
| C-07 | `POST restablecer.php` con token válido y sin CSRF | No cambia la contraseña; conserva el formulario | Formulario conservado; `password_verify()` sigue aceptando la anterior | ✅ |

**C-07 en detalle:** el token de recuperación era válido (64 hex). El POST sin `csrf_token` no ejecutó el `UPDATE`; la contraseña anterior seguía siendo válida al terminar la prueba.

**Positivo:** el login con token válido devuelve `302 → dashboard.php`, por lo que la protección no bloquea el flujo legítimo.

---

## 5. Inyección

| ID | Prueba | Entrada | Esperado | Obtenido | Estado |
|---|---|---|---|---|---|
| I-01 | SQLi en login | `email=' OR '1'='1' -- `, `password=x` | Sin sesión y sin error SQL | `302` en dashboard; mensaje genérico; `SQLSTATE/PDOException` ausente | ✅ |
| I-02 | IDOR / casteo en lección | `id=1 OR 1=1` y `id=999999` | Entero forzado; sin error SQL | `HTTP 200` en ambos; sin trazas SQL | ✅ |

```powershell
# I-01: el correo se envía con --data-urlencode
curl.exe -s -b jar -c jar --data-urlencode "csrf_token=$csrf" `
  --data-urlencode "email=' OR '1'='1' -- " --data-urlencode "password=x" `
  http://localhost/seva/login.php

# I-02: con sesión válida
curl.exe -s -b jar "http://localhost/seva/leccion.php?id=1%20OR%201=1"
curl.exe -s -b jar "http://localhost/seva/leccion.php?id=999999"
```

> En I-01 la página de login contiene la palabra «BIENVENIDO» en un chip decorativo; el criterio de éxito no es esa palabra, sino la **ausencia de sesión** (`dashboard.php` → 302) y la ausencia de errores SQL.

---

## 6. XSS

| ID | Prueba | Entrada | Esperado | Obtenido | Estado |
|---|---|---|---|---|---|
| X-01 | XSS almacenado | Nombre `<script>alert(1)</script>` | Escape en dashboard | `<script>` bruto: **False**; `&lt;script&gt;`: **True** | ✅ |
| X-02 | XSS reflejado | Email `<script>alert("x")</script>@example.com` | Escape en el formulario | `<script>` bruto: **False**; `&lt;script&gt;`: **True** | ✅ |

---

## 7. Sesión

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| S-01 | Fijación de sesión | El ID cambia tras autenticarse | Antes `smkt8nh…` → después `bua1dpe…` (distintos) | ✅ |
| S-02 | ID anterior tras login | No autenticado | `dashboard.php` con cookie vieja → `302` | ✅ |
| S-03 | Flags de cookie | `HttpOnly`, `SameSite=Lax`; `Secure` en HTTPS | `Set-Cookie: PHPSESSID=...; path=/; HttpOnly; SameSite=Lax` | ✅ |

**S-03:** en local (`http://`) no se envía `Secure` por diseño; la rama HTTPS de `seguridad_iniciar_sesion()` lo activa. La prueba dinámica de `Secure` requiere TLS y queda como verificación de despliegue.

Reproducción:

```powershell
# 1) cookie previa
curl.exe -s -c jar0 http://localhost/seva/login.php -o login0.html
# 2) login con el CSRF del formulario
curl.exe -s -b jar0 -c jar1 -D headers.txt --data-urlencode "csrf_token=$csrf" `
  --data-urlencode "email=auditoria.f7@example.com" --data-urlencode "password=Auditoria.F7.2026" `
  http://localhost/seva/login.php
# 3) comparar PHPSESSID de jar0 y jar1; usar jar0 contra dashboard
```

---

## 8. Contraseñas y recuperación

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| P-01 | Rehash automático | Hash legado se actualiza | `$2y$04$` → `$2y$10$` tras login | ✅ |
| P-02 | Solicitud de enlace (local) | Token de 64 hex y enlace visible | Enlace `restablecer.php?token=…` generado | ✅ |
| P-03 | Restablecimiento | Cambio y redirección | `302 → login.php?recuperado=1` | ✅ |
| P-04 | Reutilización del token | Rechazo | «ya fue utilizado» | ✅ |
| P-05 | Token caducado | Rechazo | «ha caducado» | ✅ |
| P-06 | Anti-enumeración (`APP_ENV=production`) | Cuerpos idénticos, sin enlace | Cuerpos idénticos: **True**; enlace visible: **False**; mensaje genérico: **True** | ✅ |

**P-01:** el usuario se creó con `password_hash(..., ['cost' => 4])`; tras el login, `password_needs_rehash()` actualizó el hash al coste por defecto de PHP 8.2 (10). La contraseña siguió funcionando.

**P-06:** se levantó un servidor PHP embebido en `127.0.0.1:8099` con `APP_ENV=production` para probar el modo sin enlace en pantalla. El servidor se detuvo al terminar.

---

## 9. Rate limiting

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| L-01 | 5 fallos consecutivos | 6.º intento bloqueado 15 min | «Demasiados intentos fallidos. Espera 15 minuto(s)…» | ✅ |
| L-02 | Alcance por IP + correo | Otro correo sigue operativo | `auditoria.f7@example.com` → `302 dashboard` con la misma IP | ✅ |

```sql
SELECT ip, email, intentos, bloqueado_hasta IS NOT NULL AS bloqueado
FROM intentos_login WHERE email = 'rate.f7@example.com';
-- 5 intentos, bloqueado = 1
```

**L-02** demuestra que el bloqueo no es global por IP: la clave es `(ip, email)`. La tabla se purgó al finalizar.

---

## 10. Cabeceras de seguridad

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| H-01 | `curl -I http://localhost/seva/login.php` | 5 cabeceras + CSP en local; HSTS condicional | Ver bloque siguiente | ✅ |

```http
HTTP/1.1 200 OK
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self';
  frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline';
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;
  media-src 'self'; connect-src 'self'
Set-Cookie: PHPSESSID=...; path=/; HttpOnly; SameSite=Lax
Cache-Control: no-store, no-cache, must-revalidate
```

No se observa `X-Powered-By`. `display_errors` está desactivado por `.htaccess`.

---

## 11. Regresión funcional

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| F-01 | Imágenes de lección ausentes | Placeholder sin 404 | 8 tarjetas usan `img/lecciones/default.svg`; 0 respuestas 404 (F5) | ✅ |
| F-02 | Cierre de sesión | Destruye la sesión | Antes `200`; logout `302`; después `302 → login.php` | ✅ |

---

## 12. Accesibilidad WCAG 2.1 AA

### 12.1 Verificaciones estáticas

| ID | Criterio | Evidencia | Estado |
|---|---|---|---|
| W-01 | Idioma y viewport (3.1.1) | `lang="es"` en **8/8** páginas; `meta viewport` en **8/8** | ✅ |
| W-02 | Texto alternativo (1.1.1) | **1** plantilla `<img>` en `dashboard.php`, con `alt="Portada de la lección: …"` | ✅ |
| W-03 | Etiquetas de formulario (1.3.1, 3.3.2) | **15** `label for`; **35** `aria-label`; los 7 campos ocultos (CSRF/honeypot) no requieren etiqueta | ✅ |
| W-04 | Foco visible (2.4.7) | **11** reglas `:focus-visible` con contorno de 3 px | ✅ |
| W-05 | Movimiento reducido (2.3.3) | **3** bloques `prefers-reduced-motion` + preferencia persistida | ✅ |
| W-06 | Tamaño de objetivo (2.5.5) | **23** declaraciones `min-height:44px` | ✅ |
| W-07 | Contraste (1.4.3) | Ver tabla 12.2 | ⚠️ Parcial |
| W-08 | Estado anunciado (4.1.3) | `role="status"`, `aria-live="polite"`, `aria-pressed` en filtros | ✅ |

> La comprobación W-02 se hizo por inspección del contexto del `<img>`: el regex multilínea no detectaba el `alt` en la misma etiqueta.

### 12.2 Ratios de contraste calculados

Fórmula WCAG 2.1: `(L1 + 0,05) / (L2 + 0,05)`.

| Combinación | Uso | Ratio | AA normal (4,5:1) |
|---|---|---:|---|
| `#1B1F3B` sobre `#FFFFFF` | Texto principal | **16,08:1** | ✅ |
| `#6B7690` sobre `#FFFFFF` | Texto atenuado | **4,55:1** | ✅ |
| `#FFFFFF` sobre `#0056B3` | Botón primario | **7,04:1** | ✅ |
| `#FFFFFF` sobre `#04294D` | Hero / fondo oscuro | **14,69:1** | ✅ |
| `#0056B3` sobre `#EAF2FB` | Enlace sobre fondo claro | **6,23:1** | ✅ |
| `#1E7E34` sobre `#E9F7EE` | Alerta de éxito | **4,65:1** | ✅ |
| `#D6465B` sobre `#FDECEF` | Alerta de error | **3,77:1** | ❌ |

**Hallazgo N-02:** el texto de las alertas de error es regular (~14,7 px), por lo que le aplica el umbral 4,5:1. Se recomienda oscurecer el token a un valor como `#B4233A` (≈ 5,4:1) o aumentar tamaño/peso para considerarlo texto grande (≥ 18,66 px en negrita o ≥ 24 px).

### 12.3 Revisión manual pendiente

- Tabulación completa con teclado en los 7 formularios.
- Zoom al 200 % y reflow.
- Prueba con lector de pantalla (NVDA/VoiceOver).
- subtítulos WebVTT (no hay clips ni pistas en el repositorio).

Estas comprobaciones no se automatizaron en F7 y se registran como límites del alcance.

---

## 13. Sintaxis PHP

| ID | Prueba | Esperado | Obtenido | Estado |
|---|---|---|---|---|
| T-01 | `php -l` en los 11 archivos PHP | 0 errores | **0 archivos con error** | ✅ |

```powershell
Get-ChildItem *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```

---

## 14. Trazabilidad pruebas ↔ defectos

| Defecto | Pruebas |
|---|---|
| D05 CSRF | C-01…C-07 |
| D06 sesión | S-01…S-03 |
| D07 rehash | P-01 |
| D08 logout | F-02 |
| D09 rate limit | L-01, L-02 |
| D11 cabeceras | H-01 |
| D13 recuperación | P-02…P-06 |
| D14/D15 medios | F-01 |
| D17 ARIA | W-08 |
| D21 errores | I-01 (sin trazas), H-01 |
| D22 validación | X-01 (nombre aceptado y escapado), registro |
| D01/D02/D03/D04 | Revisión de repositorio e historial (`05-seguridad.md`) |
| D16 vocabulario | F-01 + consulta a `vocabulario_senas` |
| D18 IDs | Import limpio en BD desechable (F6) |
| D19 MEMORIA | Revisión documental (`04-trazabilidad.md`) |
| D20 auditoría | Esta carpeta |

---

## 15. Limitaciones

1. No existe suite automatizada (PHPUnit/Playwright); las pruebas son reproducibles pero manuales.
2. El contraste se calculó con la fórmula WCAG, no con axe/Lighthouse.
3. `Secure` en cookies no se puede observar sobre HTTP local; se validó por código y queda para el despliegue TLS.
4. La revisión con lector de pantalla y el zoom 200 % quedan pendientes de una pasada manual.
5. Las pruebas se ejecutaron sobre la base viva; los datos temporales se eliminaron y los contadores se purgaron.
