# Lecciones de Lengua de Señas — Recurso para proyecto de enseñanza

Tres lecciones, cada una con sus imágenes recortadas una por una (sin modificar)
y un archivo JSON con la descripción de cómo hacer cada seña.

```
lecciones_lsc/
├── senas.json      <- Lección 1: 24 señas (saludos y frases)
├── alfabeto.json   <- Lección 2: abecedario A-Z + Ñ (27 letras)
├── colores.json    <- Lección 3: 20 colores
└── images/
    ├── senas/      (hola-informal.png, gracias.png, ...)
    ├── alfabeto/   (letra_A.png ... letra_Z.png)
    └── colores/    (morado.png, azul.png, ...)
```

## Estructura del JSON

- `nombre` (o `letra` en alfabeto.json): lo que representa la seña.
- `archivo`: ruta relativa a la imagen.
- `con_movimiento`: `true` si la seña requiere movimiento.
- `descripcion`: cómo formar la mano o la posición.
- `instruccion_movimiento` (solo si hay movimiento): cómo ejecutarlo.

## Uso en JavaScript

```javascript
const data = await fetch('senas.json').then(r => r.json());
data.forEach(s => {
  console.log(`${s.nombre}: ${s.descripcion} ${s.instruccion_movimiento || ''}`);
  // <img src={s.archivo} alt={`Seña: ${s.nombre}`} />
});
```

Para abrirlo en el navegador usa un servidor local (por ejemplo, la extensión Live Server de VS Code).
