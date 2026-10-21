# Carpeta `video/`

Aquí se alojan los clips demostrativos en Lengua de Señas (LSE) de cada lección.
La columna `contenidos_educativos.ruta_video` guarda la ruta relativa al proyecto
(por ejemplo `video/alfabeto.mp4`).

> Esta carpeta se versiona vacía (`.gitkeep`). Los videos **no** se suben al
> repositorio: son binarios pesados. En cada despliegue se copian manualmente
> o mediante el gestor de contenidos.

## Nombres esperados (según el seed actual)

| Lección | Archivo |
|---|---|
| Alfabeto dactilológico | `video/alfabeto.mp4` |
| Saludos y presentaciones | `video/saludos.mp4` |
| Números del 1 al 10 | `video/numeros.mp4` |
| Colores | `video/colores.mp4` |
| La familia | `video/familia.mp4` |
| Días de la semana | `video/dias.mp4` |
| Emociones y sentimientos | `video/emociones.mp4` |
| Comida y bebida | `video/comida.mp4` |
| Preguntas básicas | `video/preguntas.mp4` |
| Lugares y direcciones | `video/lugares.mp4` |
| El clima | `video/clima.mp4` |
| Verbos comunes | `video/verbos.mp4` |

Para una lección nueva, usa un *slug* en minúsculas sin tildes ni espacios
(`video/mi-leccion.mp4`) y actualiza `ruta_video` en la base de datos.

## Requisitos técnicos

- **Formato:** MP4 con códec **H.264** + audio **AAC** (compatibilidad amplia).
- **Relación de aspecto:** 16:9 (el contenedor `.video-lse` la fija).
- **Resolución recomendada:** 1280×720 o 1920×1080; mínimo 854×480.
- **Duración:** 20–90 segundos por seña o bloque.
- **Peso:** ≤ 15 MB por clip para carga rápida en conexiones lentas.
- **Contraste y encuadre:** manos y rostro visibles sobre fondo liso, sin
  contraluz.
- **Accesibilidad:** incluir subtítulos/transcripción. La transcripción textual
  ya se muestra en `leccion.php`; si se añaden subtítulos, usar `video/<slug>.vtt`
  y `<track kind="captions">`.

## Comportamiento del código

`leccion.php` comprueba la existencia del archivo con `is_file()` antes de
renderizar el `<video>`. Si el clip no existe (caso actual), se muestra un
placeholder accesible (“Demostración visual en preparación”) en lugar de un
reproductor roto, por lo que **no se generan errores 404**.

## Verificación rápida

```powershell
# Comprobar que un clip existe y su tamaño
Get-Item .\video\saludos.mp4 | Select-Object Name, Length

# Listar los clips presentes
Get-ChildItem .\video -Filter *.mp4
```
