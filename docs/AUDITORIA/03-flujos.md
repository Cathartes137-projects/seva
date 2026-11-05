# 03 — Flujos de datos y diagramas de secuencia

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Fase:** 7 (D20) · **Fecha:** 2026-10-21
**Método:** lectura del código (`login.php`, `registro.php`, `dashboard.php`, `leccion.php`, `perfil.php`, `configuracion.php`, `recuperar.php`, `restablecer.php`, `seguridad.php`) y verificación HTTP
**Diagramas:** 9 (7 flujos principales, recuperación en 2 fases y cierre de sesión)

---

## 0. Reglas transversales

| Control | Implementación |
|---|---|
| Sesión | `seguridad_iniciar_sesion()`: `use_strict_mode`, `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS |
| Autenticación | `seguridad_requiere_login()` redirige a `login.php` si no hay `usuario_id` |
| CSRF | Token de 32 bytes en sesión; `seguridad_validar_csrf($destino)` redirige con `?error=csrf` |
| Escapado | `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` en todas las salidas de usuario |
| SQL | PDO + `prepare()`; `ATTR_EMULATE_PREPARES = false` |
| Contraseñas | `password_hash(PASSWORD_DEFAULT)` / `password_verify` + rehash |
| Anti-bot | Campo trampa `seva_web` (oculto) |
| Rate limit | 5 fallos por IP + correo → 15 minutos (`intentos_login`) |

---

## 1. Registro de usuario (`registro.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant R as registro.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Abre el formulario
    N->>R: GET /registro.php
    R->>S: csrf_token() y honeypot_campo()
    S-->>R: Token + campo trampa
    R-->>N: HTML con formulario

    U->>N: Envía nombre, correo y contraseña
    N->>R: POST /registro.php
    R->>S: seguridad_validar_csrf('registro.php')
    alt Token inválido
        S-->>N: 302 /registro.php?error=csrf
    else Token válido
        R->>R: honeypot_accionado()
        alt Honeypot relleno
            R-->>N: Éxito simulado sin insertar (anti-bot)
        else Humano
            R->>R: Valida nombre 3-100, email y contraseña >= 8
            R->>DB: SELECT id_usuario WHERE email = ?
            alt Correo ya registrado
                DB-->>R: Fila existente
                R-->>N: Error "Ya existe una cuenta"
            else Correo libre
                R->>R: password_hash()
                R->>DB: INSERT INTO usuarios
                DB-->>R: OK
                R-->>N: Panel de registro completado
            end
        end
    end
```

**Notas**

- La validación del nombre es doble: `minlength/maxlength` en HTML y `mb_strlen` 3–100 en servidor (D22).
- El honeypot simula éxito para no dar pistas al bot.
- No hay envío de correo de verificación (fuera de alcance).

---

## 2. Inicio de sesión (`login.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant L as login.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Introduce credenciales
    N->>L: POST /login.php
    L->>S: seguridad_validar_csrf('login.php')
    alt Token inválido
        S-->>N: 302 /login.php?error=csrf
    else Token válido
        L->>S: seguridad_login_bloqueo(ip, email)
        S->>DB: SELECT de intentos_login
        alt Bloqueo activo
            S-->>L: Segundos restantes
            L-->>N: "Demasiados intentos fallidos" (mensaje neutro)
        else Sin bloqueo
            L->>L: honeypot_accionado()
            L->>DB: SELECT id_usuario, nombre, password WHERE email = ?
            alt Usuario existe y password_verify() es true
                L->>S: seguridad_login_exito()
                S->>DB: DELETE intentos_login de la clave
                L->>S: seguridad_rehash_password()
                S->>DB: UPDATE password si el coste cambió
                L->>S: seguridad_regenerar_sesion()
                L->>L: Guarda usuario_id, nombre y email en sesión
                L-->>N: 302 /dashboard.php
            else Credenciales incorrectas
                L->>S: seguridad_login_fallo()
                S->>DB: UPSERT intentos_login (+1)
                L-->>N: "Correo o contraseña incorrectos"
            end
        end
    end
```

**Notas**

- El bloqueo se evalúa antes de consultar al usuario: no revela si la cuenta existe.
- `session_regenerate_id(true)` invalida el ID previo (fijación de sesión, D06).
- El rehash mantiene compatibilidad con hashes antiguos (D07).

---

## 3. Dashboard y progreso (`dashboard.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant D as dashboard.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Abre "Tu Camino"
    N->>D: GET /dashboard.php
    D->>S: seguridad_requiere_login()
    alt Sin sesión
        S-->>N: 302 /login.php
    else Con sesión
        D->>DB: SELECT contenidos_educativos ORDER BY orden
        D->>DB: SELECT COUNT progreso completado
        D->>DB: SELECT contenido_id completados
        DB-->>D: Lecciones y progreso
        D->>D: Calcula porcentaje y nivel
        D-->>N: HTML con tarjetas y barra de progreso
        N->>N: JS filtra por texto y nivel (aria-pressed)
    end
```

**Notas**

- El filtrado es 100 % cliente: no genera peticiones ni recarga.
- Las tarjetas usan `is_file()` para caer en `img/lecciones/default.svg` (D14).
- El contador de resultados tiene `role="status"` y `aria-live="polite"` (D17).

---

## 4. Lección y marcado de progreso (`leccion.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant L as leccion.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Abre una lección
    N->>L: GET /leccion.php?id=5
    L->>S: seguridad_requiere_login()
    L->>L: $id = (int) $_GET['id']
    L->>DB: SELECT contenido WHERE id = ?
    L->>DB: SELECT vocabulario_senas WHERE contenido_id = ? ORDER BY orden
    L->>DB: SELECT completado FROM progreso_usuario
    DB-->>L: Contenido, señas y progreso
    L->>L: Valida ruta_imagen y ruta_video con is_file()
    L-->>N: HTML con video o placeholder y tarjetas de señas

    U->>N: Marca la lección como completada
    N->>L: POST /leccion.php?id=5 (marcar_completada=1)
    L->>S: seguridad_validar_csrf('leccion.php?id=5')
    alt Token válido
        L->>DB: INSERT ... ON DUPLICATE KEY UPDATE completado = 1
        DB-->>L: OK
        L-->>N: JSON {exito:true} o 302 al dashboard
    else Token inválido
        S-->>N: 302 /leccion.php?id=5?error=csrf
    end
```

**Notas**

- Si el clip no existe, se muestra un placeholder accesible en lugar de un reproductor roto (D15).
- Si la lección no tiene vocabulario, se muestra un estado vacío (Fase 6).
- El progreso de las casillas de práctica se guarda en `localStorage` por lección; el progreso de la lección, en la base de datos.

---

## 5. Perfil del estudiante (`perfil.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant P as perfil.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Abre su perfil
    N->>P: GET /perfil.php
    P->>S: seguridad_requiere_login()
    P->>DB: SELECT usuario WHERE id_usuario = ?
    P->>DB: SELECT total de lecciones y completadas
    DB-->>P: Datos y estadísticas
    P->>P: Calcula porcentaje y nivel
    P-->>N: HTML con datos y estadísticas

    U->>N: Cambia su nombre
    N->>P: POST accion=actualizar_nombre
    P->>S: seguridad_validar_csrf('perfil.php')
    P->>P: mb_strlen 3-100
    P->>DB: UPDATE usuarios SET nombre = ?
    P->>P: Sincroniza $_SESSION['nombre']
    P-->>N: Mensaje de éxito o error
```

---

## 6. Configuración y cambio de contraseña (`configuracion.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant C as configuracion.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Envía el formulario de contraseña
    N->>C: POST accion=cambiar_password
    C->>S: seguridad_validar_csrf('configuracion.php')
    alt Token inválido
        S-->>N: 302 /configuracion.php?error=csrf
    else Token válido
        C->>C: Valida campos, longitud >= 8, coincidencia y que sea distinta
        C->>DB: SELECT password WHERE id_usuario = ?
        alt password_verify(actual) es true
            C->>C: password_hash(nueva)
            C->>DB: UPDATE usuarios SET password = ?
            C-->>N: "Contraseña actualizada"
        else Contraseña actual incorrecta
            C-->>N: Error específico
        end
    end
```

**Notas**

- Las preferencias de accesibilidad (tamaño de fuente y movimiento reducido) se guardan en `localStorage` y se aplican antes de pintar el DOM; no se persisten en la base de datos.
- El cambio de contraseña no invalida otras sesiones abiertas (no hay tabla de sesiones).

---

## 7. Recuperación de contraseña

### 7.1 Solicitud del enlace (`recuperar.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant R as recuperar.php
    participant S as seguridad.php
    participant DB as MariaDB
    participant LOG as error_log

    U->>N: Escribe su correo
    N->>R: POST /recuperar.php
    R->>S: seguridad_validar_csrf('recuperar.php')
    R->>R: honeypot_accionado()
    R->>S: seguridad_login_bloqueo(ip, clave recuperacion:email)
    alt Bloqueado
        R-->>N: "Demasiadas solicitudes" (mensaje neutro)
    else Permitido
        R->>S: seguridad_login_fallo() para contar el intento
        R->>DB: SELECT id_usuario WHERE email = ?
        alt Cuenta existe
            R->>DB: UPDATE tokens previos SET usado = 1
            R->>R: token = bin2hex(random_bytes(32))
            R->>DB: INSERT recuperacion_password(token_hash, expira +30 min)
            R->>LOG: Registra el enlace de recuperación
            opt APP_ENV = local
                R-->>N: Muestra el enlace en pantalla
            end
        else Cuenta inexistente
            R->>LOG: Registra el intento sin revelar nada
        end
        R-->>N: Mensaje genérico idéntico en ambos casos
    end
```

### 7.2 Restablecimiento (`restablecer.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant RS as restablecer.php
    participant S as seguridad.php
    participant DB as MariaDB

    U->>N: Abre el enlace del correo
    N->>RS: GET /restablecer.php?token=...
    RS->>RS: Valida formato de 64 hex
    RS->>DB: SELECT token_hash, usado y TIMESTAMPDIFF(NOW(), expira)
    alt Token válido, no usado y vigente
        RS-->>N: Formulario de nueva contraseña
        U->>N: Envía contraseña y confirmación
        N->>RS: POST /restablecer.php
        RS->>S: csrf_es_valido() (error inline, conserva el token)
        RS->>RS: Valida longitud >= 8 y coincidencia
        RS->>DB: BEGIN
        RS->>DB: UPDATE usuarios SET password = hash
        RS->>DB: UPDATE tokens SET usado = 1
        RS->>DB: DELETE tokens caducados
        RS->>DB: COMMIT
        RS->>S: seguridad_cerrar_sesion()
        RS-->>N: 302 /login.php?recuperado=1
    else Token inválido, usado o caducado
        RS-->>N: Página de error con enlace a /recuperar.php
    end
```

**Notas**

- La vigencia se compara en SQL (`TIMESTAMPDIFF`) para evitar desfases de zona horaria.
- Un token nuevo invalida los anteriores del mismo usuario.
- El CSRF inválido no redirige: muestra el error y conserva el token para reintentar.
- En producción no se envía correo real: el enlace queda en `error_log` (decisión de alcance).

---

## 8. Cierre de sesión (`logout.php`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant N as Navegador
    participant O as logout.php
    participant S as seguridad.php

    U->>N: Pulsa "Cerrar sesión"
    N->>O: GET /logout.php
    O->>S: seguridad_iniciar_sesion()
    O->>S: seguridad_cerrar_sesion()
    S->>S: Vacía $_SESSION, invalida cookie y destruye sesión
    O-->>N: 302 /login.php
```
