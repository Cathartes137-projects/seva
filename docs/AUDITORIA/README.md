# Auditoría de Ingeniería Inversa — SEVA

**Proyecto:** SEVA — Sistema Educativo de Lengua de Señas
**Rama:** `fix/auditoria-defectos` · **Cierre:** Fase 8 (2026-10-21)
**Alcance:** reconstrucción del sistema desde sus artefactos, verificación dinámica y comparativa antes/después.

> Este índice es el punto de entrada a la documentación de auditoría. Los ocho
> documentos (`00`–`07`) se generaron en la Fase 7 (D20); la MEMORIA se reparó en
> la Fase 8 (D19).
>
> **Alcance temporal:** `01`–`07` conservan el estado al cierre de la Fase 7.
> En `05` y `07`, D19 aparece como pendiente porque la reparación de la MEMORIA
> se ejecutó después, en la Fase 8. El estado vigente de los defectos es el de
> [`MEMORIA_PROYECTO.md`](../MEMORIA_PROYECTO.md) §5: **22 resueltos + D04 mitigado**.

---

## 1. Índice de documentos

| # | Documento | Contenido | Métrica clave |
|---|---|---|---|
| 00 | [Línea base](./00-linea-base.md) | Captura PRE-intervención: inventario, base de datos, pruebas y fallos originales | 23 archivos, 5 tablas, 0 CSRF |
| 01 | [Inventario](./01-inventario.md) | Archivos, LOC, grafo `require/include`, endpoints y deuda técnica | 3.690 LOC PHP, 37 archivos auditados |
| 02 | [Modelo de datos](./02-modelo-datos.md) | ERD Mermaid, columnas, FKs, índices y volúmenes | 8 tablas, 5 FKs, 20 índices |
| 03 | [Flujos](./03-flujos.md) | Diagramas de secuencia de los flujos principales y complementarios | 9 diagramas Mermaid |
| 04 | [Trazabilidad](./04-trazabilidad.md) | Matriz MEMORIA ↔ código ↔ base de datos | 40 filas (estado F7) |
| 05 | [Seguridad](./05-seguridad.md) | OWASP Top 10, CWE, D01–D23, D04 y riesgos residuales | 23 defectos + 10 riesgos (estado F7) |
| 06 | [Pruebas](./06-pruebas.md) | Registro ejecutable de pruebas dinámicas y WCAG 2.1 AA | 38 pruebas: 37 ✅ / 1 ⚠️ |
| 07 | [Informe final](./07-informe-final.md) | Comparativa estado inicial vs. final y cierre de defectos | 21 resueltos + D04 mitigado al cierre de F7 |

**Documentos relacionados**

- [MEMORIA del proyecto](../MEMORIA_PROYECTO.md) — fuente de verdad funcional, reparada en Fase 8.
- [Plan de remediación](../PLAN_REMEDIACION.md) — fases, puertas de calidad y checklist.
- [Informe de Pull Request](../PR_FINAL.md) — resumen, evidencia y checklist para el merge.

---

## 2. Cómo leer esta auditoría

| Objetivo | Ruta recomendada |
|---|---|
| Entender el estado inicial y los defectos | `00` → `05` → `07` |
| Revisar la arquitectura y el modelo de datos | `01` → `02` → `03` |
| Verificar que los controles funcionan | `06` → `05` |
| Preparar el merge o la revisión de código | `07` → `../PR_FINAL.md` |

> El documento `00` es una **fotografía histórica** y no debe actualizarse. Las
> cifras de la auditoría están en `01`–`07` (estado F7); el estado vigente de los
> defectos está en [`MEMORIA_PROYECTO.md`](../MEMORIA_PROYECTO.md) §5.

---

## 3. Resultados clave

| Indicador | Resultado |
|---|---|
| Defectos del plan | **22 resueltos · 1 mitigado (D04)** |
| Pruebas dinámicas | **38** (37 ✅ · 1 ⚠️ · 0 ❌) |
| Formularios con CSRF | **7/7** |
| Cabeceras de seguridad | **5 en HTTP local + HSTS condicional** |
| Rate limiting | **5 fallos → 15 min** por IP + correo |
| Tablas / FKs / índices | **8 / 5 / 20** |
| Datos de prueba tras la limpieza | **0 usuarios, 0 intentos, 0 tokens** |
| Código fuente modificado en F7/F8 | **Ninguno** (solo documentación) |

### 3.1 Único hallazgo abierto de accesibilidad

**N-02 — Contraste de la alerta de error:** `#D6465B` sobre `#FDECEF` = **3,77:1**;
WCAG 2.1 AA exige 4,5:1 para texto normal. Se documenta en `05-seguridad.md` y
`06-pruebas.md`. Recomendación: oscurecer el token a `#B4233A` (≈ 5,4:1) o
aumentar tamaño/peso del texto.

### 3.2 Riesgos residuales principales

| ID | Riesgo | Prioridad |
|---|---|---|
| N-01 | CSP con `'unsafe-inline'` | Media |
| N-03 | Sin subtítulos WebVTT | Baja |
| N-04 | Registro sin rate limit | Baja |
| N-05 | Cambio de contraseña no invalida otras sesiones | Baja |
| N-06 | MariaDB 10.4 sin soporte | Media |
| N-07 | Sin cifrado en reposo ni roles | Media |
| N-09 | Recuperación sin SMTP | Baja |
| N-10 | BD viva con datos reales e IDs legacy | Media |

El detalle completo, con evidencia y recomendaciones, está en
[`05-seguridad.md`](./05-seguridad.md).

---

## 4. Entorno y reproducción

**Entorno auditado:** PHP 8.2.12 · MariaDB 10.4.32 · Apache 2.4.58 · `http://localhost/seva/`

```powershell
cd C:\xampp\htdocs\seva

# Estado del repositorio
git status
git log --oneline -5

# Sintaxis PHP
Get-ChildItem *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }

# Cabeceras
curl.exe -I http://localhost/seva/login.php

# Base de datos
mysql -u root sevaeducativo -e "SHOW TABLES;"
```

Las pruebas completas, con comandos y salidas esperadas, están en
[`06-pruebas.md`](./06-pruebas.md).

---

## 5. Convenciones de la documentación

- **Idioma:** español.
- **Fechas:** `AAAA-MM-DD`.
- **Estados:** ✅ resuelto/superado · ⚠️ parcial/mitigado · ❌ ausente/fallido · ⏳ pendiente.
- **Defectos:** `D01`–`D23` (plan de remediación).
- **Riesgos nuevos:** `N01`–`N10` (auditoría de Fase 7).
- **Pruebas:** `A`, `C`, `I`, `X`, `S`, `P`, `L`, `H`, `F`, `W`, `T` (ver `06-pruebas.md`).
- **Diagramas:** Mermaid embebido; se renderizan en GitHub, GitLab y VS Code.
