# Informe de Pull Request — Cierre de la remediación SEVA (F0–F8)

**Título propuesto:** `fix(auditoria): remediación integral de seguridad, accesibilidad y documentación (F0–F8)`

| Campo | Valor |
|---|---|
| Rama origen | `fix/auditoria-defectos` |
| Rama destino | `seva-antigraity-migracion` |
| Estado | Preparado; pendiente de abrir en GitHub |
| Fecha de cierre | 2026-10-21 |
| Etiquetas | `fase-0` … `fase-8` |
| Documento de auditoría | [`docs/AUDITORIA/README.md`](./AUDITORIA/README.md) |

**Crear el PR por web:**
<https://github.com/Cathartes137-projects/seva/compare/seva-antigraity-migracion...fix/auditoria-defectos?expand=1>

> `gh` no está instalado en el entorno local; el cuerpo de este documento puede
> pegarse directamente en el formulario de GitHub.

---

## 1. Resumen

Este PR cierra la remediación de los **23 defectos** detectados en la auditoría
inicial de SEVA. El cambio cubre seguridad (CSRF, sesión, rate limiting,
honeypot, cabeceras, recuperación de contraseña), accesibilidad (foco, ARIA,
movimiento reducido, contraste), contenido (vocabulario desde base de datos,
placeholders de medios), datos (seed sanitizado, IDs normalizados) y
documentación (MEMORIA reparada + 8 documentos de auditoría).

**Resultado:** 22 defectos resueltos y D04 mitigado por decisión expresa de no
reescribir el historial de Git. No quedan vulnerabilidades críticas conocidas en
el código actual.

---

## 2. Cambios por fase

| Fase | Alcance | Evidencia |
|---|---|---|
| F0 | Línea base, backups y BD de pruebas | `docs/AUDITORIA/00-linea-base.md` |
| F1 | `.gitignore`, `.gitattributes`, eliminación de `db.php` y dump, seed sanitizado, esquema y migraciones | 0 secretos en archivos actuales; import limpio |
| F2 | Núcleo `seguridad.php`, CSRF, sesión endurecida, rehash, logout dedicado | 7/7 POST con token; SID regenerado |
| F3 | Rate limiting y honeypot | 5 fallos → 15 min; bots con respuesta neutra |
| F4 | Cabeceras, CSP, HTTPS/HSTS condicional, errores ocultos | 5 cabeceras + CSP en local; HSTS en producción |
| F5 | Recuperación con tokens de un solo uso, `default.svg`, carpeta `video/` | Token 30 min, uso único, anti-enumeración |
| F6 | Vocabulario por lección, `aria-pressed`, seed `1..n`, validación de nombre en servidor | 50 señas; 0 `role="tab"`; nombre 3–100 |
| F7 | Auditoría de ingeniería inversa (8 documentos) | 38 pruebas: 37 ✅ / 1 ⚠️ |
| F8 | MEMORIA reparada, índice de auditoría y este informe | D19 cerrado; enlaces válidos |

**Archivos principales:** `seguridad.php`, `login.php`, `registro.php`,
`recuperar.php`, `restablecer.php`, `leccion.php`, `dashboard.php`,
`configuracion.php`, `perfil.php`, `logout.php`, `conexion.php`, `.htaccess`,
`DataBase/*`, `docs/*`, `img/lecciones/default.svg`, `video/*`.

---

## 3. Evidencia de pruebas

| Verificación | Resultado |
|---|---|
| Pruebas dinámicas | **38** (37 ✅ · 1 ⚠️ · 0 ❌) |
| CSRF negativo | 7/7 POST rechazados con `?error=csrf` |
| SQLi en login | Sin bypass; sin trazas SQL |
| XSS almacenado/reflejado | Escapado con `htmlspecialchars` |
| Fijación de sesión | SID cambia y el anterior deja de autenticar |
| Rehash | `$2y$04$` → `$2y$10$` |
| Rate limit | 5 fallos → bloqueo de 15 min (por IP + correo) |
| Recuperación | Token válido, caducado y reutilizado probados; anti-enumeración con cuerpos idénticos |
| Cabeceras | `nosniff`, `DENY`, `Referrer-Policy`, `Permissions-Policy`, CSP; HSTS condicional |
| Sintaxis PHP | `php -l` en 11 archivos: 0 errores |
| Accesibilidad | `lang="es"` 8/8, 15 `label`, 35 `aria-label`, 11 `:focus-visible`, 23 `min-height:44px` |
| Contraste | 6 combinaciones ✅; alerta de error 3,77:1 (⚠️ N-02) |
| Limpieza | 0 usuarios temporales, 0 intentos, 0 tokens |

**Única parcial:** N-02, contraste de la alerta de error. Está documentada y no
bloquea el merge; se recomienda corregirla en el backlog posterior.

El detalle reproducible está en [`06-pruebas.md`](./AUDITORIA/06-pruebas.md).

---

## 4. Checklist de defectos

- [x] D01 — Credenciales reales y Admin en texto plano
- [x] D02 — Falta de `.gitignore`
- [x] D03 — `db.php` muerto con credenciales
- [x] D04 — Secreto en historial Git (**mitigado**; historial no reescrito)
- [x] D05 — CSRF ausente
- [x] D06 — Cookies sin flags y fijación de sesión
- [x] D07 — Rehash de contraseñas
- [x] D08 — Logout por `?salir`
- [x] D09 — Rate limiting
- [x] D10 — Honeypot
- [x] D11 — Cabeceras de seguridad
- [x] D12 — HTTPS/HSTS
- [x] D13 — Recuperación de contraseña (404)
- [x] D14 — Imágenes de lección ausentes
- [x] D15 — `default.jpg` y carpeta `video/`
- [x] D16 — Vocabulario hardcodeado
- [x] D17 — `role="tab"` sin `tabpanel`
- [x] D18 — IDs forzados / AUTO_INCREMENT
- [x] D19 — MEMORIA corrupta
- [x] D20 — Auditoría documentada
- [x] D21 — Errores PHP y versión expuestos
- [x] D22 — Validación de nombre solo en cliente
- [x] D23 — CRLF/LF

**Puertas de calidad:** F0 ✅ · F1 ✅ · F2 ✅ · F3 ✅ · F4 ✅ · F5 ✅ · F6 ✅ · F7 ✅ · F8 ✅

---

## 5. Checklist para el revisor

- [ ] Revisar el diff de `seguridad.php`, `login.php` y `registro.php`.
- [ ] Confirmar que no hay secretos en archivos actuales (`git grep -n "admin12358"`).
- [ ] Importar `DataBase/schema.sql` y `DataBase/seed_demo.sql` en una BD desechable.
- [ ] Aplicar las migraciones `001`–`003` si la BD ya existe.
- [ ] Ejecutar `php -l` sobre los 11 archivos PHP.
- [ ] Probar login, dashboard, lección, perfil y cambio de contraseña con la cuenta demo.
- [ ] Probar recuperación con `APP_ENV=local` y `APP_ENV=production`.
- [ ] Verificar cabeceras con `curl -I`.
- [ ] Revisar la navegación por teclado y el foco visible.
- [ ] Aceptar o planificar N-02 (contraste) y los riesgos N-01…N-10.
- [ ] Aprobar el merge.

---

## 6. Cómo probar (entorno local)

```powershell
cd C:\xampp\htdocs\seva

# 1. Sintaxis
Get-ChildItem *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }

# 2. Base de datos limpia (opcional, BD desechable)
mysql -u root -e "DROP DATABASE IF EXISTS seva_educativo_test; CREATE DATABASE seva_educativo_test CHARACTER SET utf8mb4;"
Get-Content DataBase\schema.sql,DataBase\seed_demo.sql | mysql -u root seva_educativo_test

# 3. Cabeceras
curl.exe -I http://localhost/seva/login.php

# 4. Flujo manual
#    http://localhost/seva/login.php
#    admin@example.com / Seva.Admin.2026
```

**Credenciales demo:**

| Cuenta | Contraseña |
|---|---|
| `admin@example.com` | `Seva.Admin.2026` |
| `demo@example.com` | `Seva.Demo.2026` |

---

## 7. Riesgos residuales

| ID | Riesgo | Prioridad | Acción |
|---|---|---|---|
| N-01 | CSP con `'unsafe-inline'` | Media | Extraer JS/CSS inline y usar nonces |
| N-02 | Contraste de alerta 3,77:1 | Baja | Oscurecer `--danger` a `#B4233A` |
| N-03 | Sin subtítulos WebVTT | Baja | Añadir `<track kind="captions">` con los clips |
| N-04 | Registro sin rate limit | Baja | Reutilizar `intentos_login` por IP |
| N-05 | Cambio de contraseña no invalida otras sesiones | Baja | Forzar re-login o *session version* |
| N-06 | MariaDB 10.4 sin soporte | Media | Actualizar a 10.11 LTS / 11.x |
| N-07 | Sin cifrado en reposo ni roles | Media | Cifrado de disco y mínimo privilegio |
| N-08 | Sin monitoreo centralizado | Baja | Recolector de logs y alertas |
| N-09 | Recuperación sin SMTP | Baja | Integrar SMTP antes de producción |
| N-10 | BD viva con datos reales e IDs legacy | Media | Plan de migración/anonimización |

---

## 8. Notas de despliegue

1. Definir `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` por variables de entorno.
2. Configurar `APP_ENV=production` para ocultar el enlace de recuperación.
3. Servir por HTTPS; `.htaccess` activa redirección y HSTS fuera de `localhost`.
4. No usar `root` sin contraseña en producción.
5. Integrar SMTP antes de habilitar la recuperación para usuarios reales.
6. Hacer copia de seguridad de la BD antes de aplicar migraciones.
7. Revisar el backlog de [`05-seguridad.md`](./AUDITORIA/05-seguridad.md) y [`07-informe-final.md`](./AUDITORIA/07-informe-final.md).

---

## 9. Rollback

- Revertir el merge commit de este PR restaura el estado anterior del código.
- Las migraciones `001`–`003` son **aditivas** (nuevas tablas/índices); no eliminan datos.
- Si se revierte el código, las tablas nuevas pueden permanecer sin afectar a la versión anterior.
- Restaurar la copia de seguridad si se revirtieron cambios de datos.

---

## 10. Commits y etiquetas

La rama contiene los commits de las fases F0–F7 y los de cierre F8. Etiquetas
publicadas: `fase-0` … `fase-7`; `fase-8` se crea al cerrar este PR.

```powershell
git log --oneline --decorate seva-antigraity-migracion..fix/auditoria-defectos
git tag --list "fase-*"
```

---

## 11. Nota final

Este PR no incluye cambios de código en F7/F8: la Fase 7 generó la auditoría y
la Fase 8 reparó la MEMORIA y preparó el cierre. Cualquier ajuste derivado de la
revisión (por ejemplo, N-02) debe entrar como un commit posterior y con su
propia evidencia.
