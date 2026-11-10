#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Fija la indentación del img tag de detector.php."""
SRC = "C:/xampp/htdocs/seva/public/camara/detector.php"

with open(SRC, encoding="utf-8") as f:
    src = f.read()

# Reemplazo identico al bloque HTML existente pero con la indentacion corregida
old = '''    <div class="reto-letra">
                <img id="reto-imagen"
                     src="<?= $retoLetras[0]['imagen'] ?? '../img/lecciones/default.svg' ?>"
                     alt="Seña de la letra <?= htmlspecialchars($retoLetras[0]['letra'] ?? 'A', ENT_QUOTES, 'UTF-8') ?> en Lengua de Señas Colombiana"
                     decoding="async">
                <div class="reto-letra-texto">
                    <p class="reto-letra-eyebrow">Letra objetivo</p>
                    <p class="reto-letra-grande" id="reto-letra"><?= htmlspecialchars($retoLetras[0]['letra'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="reto-descripcion" id="reto-descripcion"><?= htmlspecialchars($retoLetras[0]['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>'''

new = '''    <div class="reto-letra">
        <img id="reto-imagen"
                     src="<?= $retoLetras[0]['imagen'] ?? '../img/lecciones/default.svg' ?>"
                     alt="Seña de la letra <?= htmlspecialchars($retoLetras[0]['letra'] ?? 'A', ENT_QUOTES, 'UTF-8') ?> en Lengua de Señas Colombiana"
                     decoding="async">
        <div class="reto-letra-texto">
                    <p class="reto-letra-eyebrow">Letra objetivo</p>
                    <p class="reto-letra-grande" id="reto-letra"><?= htmlspecialchars($retoLetras[0]['letra'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="reto-descripcion" id="reto-descripcion"><?= htmlspecialchars($retoLetras[0]['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>'''

if old in src:
    src = src.replace(old, new, 1)
    with open(SRC, "w", encoding="utf-8") as f:
        f.write(src)
    print("Replaced successfully")
else:
    print("Old block NOT found")
    print("Looking for:", repr(old[:200]))
