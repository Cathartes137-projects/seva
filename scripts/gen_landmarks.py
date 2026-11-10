#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador de datasets de referencias de landmarks para el detector de cámara MediaPipe.
Cada lección se representa con metadata global (difficulty, xp, hand_side, version) y
una lista de 'reference_signs' con coordenadas de referencia (21 landmarks), los
valores esperados de features y los rangos válidos para validación.
"""
import json
import os

OUT_DIR = os.path.join("public", "data", "landmarks")
os.makedirs(OUT_DIR, exist_ok=True)


def landmark_to_dict(p):
    return {"x": round(p[0], 4), "y": round(p[1], 4), "z": 0.0}


def base_hand(x_off=0.0, y_off=0.0):
    """Poses base de manos (palm facing camera, fingers toward +y)."""
    return [
        (0.50 + x_off, 0.56 + y_off),   # 0  WRIST
        (0.46 + x_off, 0.60 + y_off),   # 1  THUMB_CMC
        (0.43 + x_off, 0.62 + y_off),   # 2  THUMB_MCP
        (0.40 + x_off, 0.65 + y_off),   # 3  THUMB_IP
        (0.37 + x_off, 0.68 + y_off),   # 4  THUMB_TIP
        (0.52 + x_off, 0.54 + y_off),   # 5  INDEX_MCP
        (0.53 + x_off, 0.57 + y_off),   # 6  INDEX_PIP
        (0.55 + x_off, 0.61 + y_off),   # 7  INDEX_DIP
        (0.57 + x_off, 0.66 + y_off),   # 8  INDEX_TIP
        (0.50 + x_off, 0.53 + y_off),   # 9  MIDDLE_MCP
        (0.51 + x_off, 0.56 + y_off),   # 10 MIDDLE_PIP
        (0.52 + x_off, 0.60 + y_off),   # 11 MIDDLE_DIP
        (0.53 + x_off, 0.64 + y_off),   # 12 MIDDLE_TIP
        (0.48 + x_off, 0.53 + y_off),   # 13 RING_MCP
        (0.49 + x_off, 0.56 + y_off),   # 14 RING_PIP
        (0.50 + x_off, 0.60 + y_off),   # 15 RING_DIP
        (0.51 + x_off, 0.64 + y_off),   # 16 RING_TIP
        (0.46 + x_off, 0.53 + y_off),   # 17 PINKY_MCP
        (0.47 + x_off, 0.56 + y_off),   # 18 PINKY_PIP
        (0.48 + x_off, 0.60 + y_off),   # 19 PINKY_DIP
        (0.49 + x_off, 0.64 + y_off),   # 20 PINKY_TIP
    ]


def gen_features(i, base, offset=0.0):
    return {
        "avg_ext": round(base + 0.03 * (i % 4) + offset, 4),
        "avg_curl": round(1.0 - base - 0.03 * (i % 4) - offset, 4),
        "max_ext": round(base + 0.10 + 0.03 * (i % 4) + offset, 4),
        "tip_palm": round(0.42 + 0.02 * (i % 3) + offset, 4),
        "thumb_out": round(0.30 + 0.03 * (i % 4) + offset, 4),
        "thumb_index_gap": round(0.55 + 0.02 * (i % 5) + offset, 4),
        "thumb_straight": round(0.85 + 0.03 * (i % 4) + offset, 4),
        "together": round(0.55 + 0.02 * (i % 4) + offset, 4),
        "scale": 1.00,
    }


def valid_range(features):
    def rng(key, width):
        return [round(features[key] - width, 4), round(features[key] + width, 4)]
    return {
        "avg_ext": rng("avg_ext", 0.10),
        "avg_curl": rng("avg_curl", 0.10),
        "max_ext": rng("max_ext", 0.12),
        "tip_palm": rng("tip_palm", 0.08),
        "thumb_out": rng("thumb_out", 0.10),
        "thumb_index_gap": rng("thumb_index_gap", 0.12),
        "thumb_straight": rng("thumb_straight", 0.12),
        "together": rng("together", 0.12),
    }


def add_sign(lesson, sign):
    lesson["reference_signs"].append(sign)


# ================= LECCION 1: ALFABETO (27) ============================
def generate_alfabeto():
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 1,
        "lesson_title": "Alfabeto dactilológico",
        "difficulty": "basico",
        "xp": 15,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    letters = [
        ("A", "Cierra la mano formando un pu\u00f1o y extiende el pulgar hacia un costado."),
        ("B", "Extiende los cuatro dedos juntos y rectos hacia arriba, pulgar doblado en la palma."),
        ("C", "Curva todos los dedos y el pulgar formando la silueta de la letra C abierta."),
        ("D", "Levanta el \u00edndice estirado abajo, los dem\u00e1s en pu\u00f1o apoyados sobre el pulgar."),
        ("E", "Dobla los cuatro dedos hacia la palma dejando las yemas visibles; pulgar recogido."),
        ("F", "Indic e y pulgar formando un aro (V invertido), los otros tres doblados."),
        ("G", "Indic apuntando al costado, pulgar levantado, giro de mu\u00f1eca."),
        ("H", "Indic y medio unidos hacia el costado, pulgar recogido, movimiento lateral."),
        ("I", "Solo el me\u00f1ique y el pulgar extendidos, los dem\u00e1s cerrados."),
        ("J", "Me\u00f1ique extendido hacia abajo y pulgar al costado, traza la J en el aire."),
        ("K", "Indic y pulgar en escuadra, media y anular en pu\u00f1o."),
        ("L", "Indic hacia arriba y pulgar hacia el costado formando la L."),
        ("M", "Pu\u00f1o cerrado con el pulgar tapado por los dedos indic, medio y anular."),
        ("N", "Pu\u00f1o cerrado con el pulgar tapado solo por los dedos indic y medio."),
        ("\u00d1", "Oscila la mu\u00f1eca de lado a lado; igual que la N."),
        ("O", "Puntas de todos los dedos y el pulgar en c\u00edrculo cerrado."),
        ("P", "Mu\u00f1eca hacia abajo, indic tocando el pulgar; pinza hacia el suelo."),
        ("Q", "Puntas de todos los dedos apuntando abajo, como si pellizcaran algo."),
        ("R", "Indic cruzado sobre el medio; los dem\u00e1s cerrados."),
        ("S", "Pu\u00f1o cerrado con el pulgar cruzado por delante; movimiento de S."),
        ("T", "Pulgar asomado entre los dedos indic y medio."),
        ("U", "Indic y me\u00f1ique extendidos juntos, forma de cuernos."),
        ("V", "Indic y medio separados en V, los dem\u00e1s cerrados."),
        ("W", "Indic, medio y anular extendidos en W, me\u00f1ique y pulgar recogidos."),
        ("X", "Indic doblado formando gancho, restos en pu\u00f1o."),
        ("Y", "Pulgar y me\u00f1ique extendidos (shaka), centrocerrados."),
        ("Z", "Indic y medio trazan la Z en el aire."),
    ]
    for i, (letra, desc) in enumerate(letters):
        l = base_hand(0.0, 0.0)
        if letra == "A":
            l[2] = (0.43, 0.60); l[4] = (0.38, 0.63)
            l[5] = (0.54, 0.56); l[8] = (0.55, 0.58)
            for k in range(9, 21):
                l[k] = (l[k][0], l[k][1] + 0.012)
        elif letra == "B":
            for k in (2, 3, 4):
                l[k] = (l[k][0], l[k][1] - 0.01)
            l[8] = (l[8][0], l[8][1] + 0.015)
            for k in (9, 12, 16, 20):
                l[k] = (l[k][0], l[k][1] + 0.015)
        elif letra == "C":
            for k in (1, 2, 3, 4):
                l[k] = (l[k][0] + 0.02, l[k][1] + 0.01)
            l[5] = (l[5][0] + 0.01, l[5][1] - 0.01)
            l[8] = (l[8][0] + 0.01, l[8][1] - 0.01)
        elif letra == "D":
            l[8] = (l[8][0], l[8][1] - 0.02)
            l[9] = (l[9][0] + 0.02, l[9][1] + 0.02)
            for k in (10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0], l[k][1] + 0.01)
        elif letra == "E":
            for k in range(5, 21):
                l[k] = (l[k][0] + 0.01, l[k][1] - 0.01)
        elif letra == "F":
            l[4] = (l[4][0] + 0.01, l[4][1] + 0.01)
            l[5] = (l[5][0] + 0.02, l[5][1] - 0.02)
            l[8] = (l[8][0] + 0.02, l[8][1] - 0.02)
            for k in (9, 12, 16, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] + 0.01)
        elif letra == "G":
            l[8] = (l[8][0] + 0.08, l[8][1] - 0.02)
            l[5] = (l[5][0] - 0.01, l[5][1] - 0.01)
            l[9] = (l[9][0] - 0.01, l[9][1] - 0.01)
        elif letra == "H":
            l[5] = (l[5][0] - 0.02, l[5][1] - 0.02)
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
            l[9] = (l[9][0] - 0.01, l[9][1] - 0.02)
            l[12] = (l[12][0] - 0.01, l[12][1] - 0.02)
        elif letra == "I":
            l[8] = (l[8][0] + 0.01, l[8][1] + 0.03)
            for k in (9, 12, 16, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] + 0.02)
        elif letra == "J":
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.01)
            l[5] = (l[5][0] - 0.02, l[5][1] + 0.02)
            l[9] = (l[9][0] - 0.01, l[9][1] + 0.02)
        elif letra == "K":
            l[4] = (l[4][0] - 0.02, l[4][1] + 0.01)
            l[5] = (l[5][0] - 0.01, l[5][1] - 0.01)
            l[9] = (l[9][0] - 0.01, l[9][1] - 0.01)
            l[12] = (l[12][0] + 0.01, l[12][1] - 0.01)
        elif letra == "L":
            l[4] = (l[4][0] + 0.08, l[4][1] + 0.03)
            l[8] = (l[8][0] + 0.02, l[8][1] - 0.01)
            l[9] = (l[9][0] + 0.01, l[9][1] + 0.01)
        elif letra == "M":
            for k in (5, 8, 9, 10, 11, 12):
                l[k] = (l[k][0], l[k][1] + 0.01)
            l[4] = (l[4][0], l[4][1] + 0.02)
            l[17] = (l[17][0] + 0.01, l[17][1] + 0.01)
        elif letra == "N":
            for k in (5, 8, 9, 10, 11, 12):
                l[k] = (l[k][0], l[k][1] + 0.01)
            l[4] = (l[4][0], l[4][1] + 0.015)
            l[17] = (l[17][0] + 0.02, l[17][1] + 0.01)
        elif letra == "\u00d1":
            l[20] = (l[20][0] + 0.06, l[20][1] - 0.02)
            for k in (5, 9):
                l[k] = (l[k][0] + 0.03, l[k][1] - 0.01)
            for k in (12, 13, 14, 15, 16, 17, 18, 19):
                l[k] = (l[k][0] - 0.01, l[k][1] + 0.01)
        elif letra == "O":
            for k in (4, 5, 8, 12, 16, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] - 0.01)
        elif letra == "P":
            l[8] = (l[8][0] - 0.06, l[8][1] + 0.02)
            l[5] = (l[5][0] - 0.01, l[5][1] - 0.01)
            l[9] = (l[9][0] - 0.01, l[9][1] - 0.01)
            l[12] = (l[12][0] - 0.01, l[12][1] - 0.01)
        elif letra == "Q":
            l[4] = (l[4][0] - 0.02, l[4][1] - 0.02)
            for k in range(5, 21):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.01)
        elif letra == "R":
            l[6] = (l[6][0] + 0.04, l[6][1] - 0.02)
            l[8] = (l[8][0] + 0.02, l[8][1] - 0.01)
            l[9] = (l[9][0] + 0.01, l[9][1] - 0.01)
        elif letra == "S":
            for k in (2, 3, 4):
                l[k] = (l[k][0] + 0.015, l[k][1] + 0.015)
            for k in range(5, 21):
                l[k] = (l[k][0] + 0.01, l[k][1] - 0.01)
        elif letra == "T":
            l[4] = (l[4][0] - 0.02, l[4][1] + 0.01)
            l[8] = (l[8][0] + 0.02, l[8][1] - 0.02)
            l[9] = (l[9][0] + 0.01, l[9][1] + 0.02)
            l[12] = (l[12][0] + 0.01, l[12][1] + 0.02)
        elif letra == "U":
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
            l[20] = (l[20][0] + 0.02, l[20][1] - 0.02)
            l[5] = (l[5][0] + 0.01, l[5][1] + 0.01)
            l[9] = (l[9][0] - 0.01, l[9][1] + 0.01)
        elif letra == "V":
            l[4] = (l[4][0] + 0.01, l[4][1] - 0.01)
            l[8] = (l[8][0] + 0.06, l[8][1] - 0.02)
            l[12] = (l[12][0] + 0.01, l[12][1] - 0.01)
        elif letra == "W":
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
            l[12] = (l[12][0] - 0.02, l[12][1] - 0.02)
            l[16] = (l[16][0] - 0.02, l[16][1] - 0.02)
            l[20] = (l[20][0] + 0.02, l[20][1] - 0.02)
            l[5] = (l[5][0] + 0.01, l[5][1] - 0.01)
            l[9] = (l[9][0] + 0.01, l[9][1] - 0.01)
        elif letra == "X":
            l[8] = (l[8][0] - 0.04, l[8][1] - 0.02)
            l[5] = (l[5][0] - 0.01, l[5][1] + 0.01)
            l[9] = (l[9][0] - 0.01, l[9][1] + 0.01)
        elif letra == "Y":
            l[4] = (l[4][0] + 0.07, l[4][1] - 0.01)
            l[20] = (l[20][0] - 0.07, l[20][1] - 0.01)
            l[5] = (l[5][0] + 0.01, l[5][1] - 0.01)
            l[8] = (l[8][0] + 0.01, l[8][1] - 0.01)
            l[9] = (l[9][0] + 0.01, l[9][1] - 0.01)
            l[12] = (l[12][0] + 0.01, l[12][1] - 0.01)
        elif letra == "Z":
            l[8] = (l[8][0] - 0.08, l[8][1] - 0.02)
            l[5] = (l[5][0] + 0.01, l[5][1] - 0.01)
            l[9] = (l[9][0] + 0.02, l[9][1] - 0.01)

        feat = gen_features(i * 1.5, 0.78)
        add_sign(data, {
            "sign_id": letra.lower(),
            "letter": letra,
            "description": desc,
            "con_movimiento": any(k in desc.lower() for k in ["gira", "mueve", "traza", "dibuja", "vaiv", "cruza"]),
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 2: SALUDOS (24) ============================
def generate_lsc_from_json(lesson_id, title, difficulty, xp, json_path):
    with open(json_path, encoding="utf-8") as f:
        raw = json.load(f)
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": lesson_id,
        "lesson_title": title,
        "difficulty": difficulty,
        "xp": xp,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, s in enumerate(raw):
        sign_id = s["nombre"].lower().replace(" ", "_").replace("-", "_")
        l = base_hand(0.0, 0.0)
        desc = s["descripcion"]
        if "frente" in desc or "cara" in desc:
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] - 0.01)
        if "pecho" in desc:
            l[2] = (l[2][0] + 0.01, l[2][1] + 0.01)
        if "barbilla" in desc:
            l[4] = (l[4][0] + 0.01, l[4][1] + 0.02)
            l[8] = (l[8][0] + 0.02, l[8][1] + 0.01)
        if "cabeza" in desc or "cara" in desc:
            l[8] = (l[8][0] - 0.01, l[8][1] - 0.01)
        k = i % 6
        l[8] = (l[8][0] + 0.005, l[8][1] - 0.005)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": sign_id,
            "palabra": s["nombre"],
            "archivo": s["archivo"],
            "description": desc,
            "con_movimiento": bool(s.get("con_movimiento")),
            "instruction_movimiento": s.get("instruction_movimiento") or s.get("instruccion_movimiento") or "",
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 3: NUMEROS (10) ============================
def generate_numeros():
    items = [
        ("uno", "Levanta solo el dedo indic con la palma orientada hacia el frente."),
        ("dos", "Extiende el indic y el dedo medio formando una V."),
        ("tres", "Extiende indic, medio y anular hacia arriba."),
        ("cuatro", "Extiende los cuatro dedos separados, pulgar recogido."),
        ("cinco", "Mano abierta con los cinco dedos separados."),
        ("se\u00eds", "Cierra el pulgar entre el indic y el medio."),
        ("se\u00eds", "Extiende el pulgar hacia arriba, los dem\u00e1s cerrados."),
        ("se\u00eds", "Palma abierta con los dedos juntos, pulgar apoyado."),
        ("nueve", "Muy cerca de cinco, pero con dedos juntos sobre la palma."),
        ("diez", "Abre y cierra el pu\u00f1o con los dedos extendidos en movimiento r\u00e1pido."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 3,
        "lesson_title": "N\u00fameros del 1 al 10",
        "difficulty": "basico",
        "xp": 15,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word in ("uno", "tres"):
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.05)
            l[5] = (l[5][0] + 0.02, l[5][1] - 0.02)
            for k in range(9, 21):
                l[k] = (l[k][0], l[k][1] + 0.015)
        elif word == "dos":
            l[8] = (l[8][0] - 0.05, l[8][1] - 0.04)
            l[12] = (l[12][0] - 0.03, l[12][1] - 0.02)
            l[5] = (l[5][0] - 0.03, l[5][1] - 0.02)
            l[4] = (l[4][0] + 0.01, l[4][1] + 0.01)
        elif word == "tres":
            l[8] = (l[8][0] - 0.08, l[8][1] - 0.04)
            l[12] = (l[12][0] - 0.04, l[12][1] - 0.02)
            l[16] = (l[16][0] - 0.03, l[16][1] - 0.02)
            l[5] = (l[5][0] + 0.02, l[5][1] - 0.01)
        elif word == "cuatro":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.01)
        elif word == "cinco":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.02)
        elif word == "se\u00eds":
            l[4] = (l[4][0] - 0.05, l[4][1] + 0.02)
            l[8] = (l[8][0] + 0.04, l[8][1] - 0.03)
        elif word == "nueve":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.01)
        elif word == "diez":
            for k in (2, 3, 4, 5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] + 0.01)
            l[4] = (l[4][0] + 0.03, l[4][1] - 0.03)

        feat = gen_features(i * 1.5, 0.60)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 4: COLORES (20) ============================
def generate_colores():
    items = [
        ("morado", "Mano abierta frente a la boca; vaiv\u00e9 de los dedos."),
        ("negro", "Dedo \u00edndice sobre la ceja; desliza hacia un lado."),
        ("oscuro", "Ambas palmas abiertas, cruza las manos tapando el campo visual."),
        ("opaco", "Pu\u00f1o cerrado frente a la boca; dos toques hacia abajo."),
        ("p\u00falido", "Dedo \u00edndice sobre la barbilla; giro circular leve."),
        ("plateado", "Mano abierta frente a la boca; abre la mano hacia el frente."),
        ("transparente", "Ambas manos abiertas al frente; vibra los dedos y baja los brazos."),
        ("verde", "V con indic y medio; giro circular de mu\u00f1eca."),
        ("color", "Mano abierta frente a la boca; movimiento suave de los dedos."),
        ("amarillo", "Mano abierta junto al hombro; dedos separados; movimiento."),
        ("azul", "Mano con pulgar y me\u00f1ique extendidos (shaka); movimiento lateral."),
        ("anaranjado", "Mano cerca de la mejilla y la boca; movimiento circular."),
        ("blanco", "Una mano frente al pecho; la otra se eleva en arco."),
        ("brillante", "Ambas manos abiertas a los lados; mu\u00f1ecas en zigzag."),
        ("cafe", "Mano abierta cerca del rostro; movimiento hacia adelante y atr\u00e1s."),
        ("claro", "Ambas manos abiertas a los lados; bajan y se alejan."),
        ("colorear", "\u00edndices de ambas manos enfrentados; frotar suavemente."),
        ("dorado", "Una mano en pu\u00f1o frente al cuerpo; la otra en ondas hacia un lado."),
        ("fosforescente", "Una mano abierta junto a la mejilla; el indic toca el rostro."),
        ("gris", "Dedo \u00edndice extendido hacia arriba; peque\u00f1o c\u00edrculo en el aire."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 4,
        "lesson_title": "Colores",
        "difficulty": "basico",
        "xp": 15,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        l[8] = (l[8][0] + 0.005, l[8][1] - 0.005)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": True,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 5: FAMILIA (20) ============================
def generate_familia():
    items = [
        ("mujer", "Mano junto a la mejilla baja en arco; la otra en pu\u00f1o."),
        ("abuelo", "Mano junto a la mejilla/oreja; movimiento corto y suave."),
        ("pap\u00e1", "Dedo \u00edndice apoyado sobre los labios cerca de la nariz."),
        ("mam\u00e1", "Mano abierta apoyada sobre el pecho."),
        ("hermano", "\u00edndices extendidos; juntar y separar las manos."),
        ("amigo", "Una mano baja con flecha, la otra debajo."),
        ("hijo", "Mano en la boca que baja en curva hacia afuera."),
        ("abuela", "Mano cerca de la mejilla; arco hacia atr\u00e1s."),
        ("tio", "Mano plana sobre el pecho, la otra debajo al abdomen."),
        ("hombre", "Mano junto a la nariz; desplazamiento hacia el lado."),
        ("nieta", "Mano cerca de la boca/barbilla; desplazamiento en curva."),
        ("novia", "Ambos pu\u00f1os frente al pecho, toc\u00e1ndose."),
        ("nuera", "Pu\u00f1o levantado; giro de mu\u00f1eca."),
        ("suegra", "Mano abierta al lado de la cara; la otra en pulgar abajo."),
        ("cuna", "Mano en L que se mueve de lado a lado."),
        ("primo", "Manos frente al pecho; una baja y toca la otra."),
        ("sobrino", "Mano forma c\u00edrculo con los dedos sobre la otra."),
        ("yerno", "Mano en forma de Y a la altura del hombro."),
        ("madrina", "Mano cerca de la mejilla; toque suave."),
        ("familia", "Mano en L (\u00edndice y pulgar) movi\u00e9ndose de lado a lado."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 5,
        "lesson_title": "La familia",
        "difficulty": "basico",
        "xp": 15,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        l[8] = (l[8][0] + 0.005, l[8][1] - 0.005)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": True,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 6: DIAS (7) ============================
def generate_dias():
    items = [
        ("lunes", "Mano en L; c\u00edrculo peque\u00f1o en el aire."),
        ("martes", "Mano abierta; movimiento corto hacia el frente."),
        ("mi\u00e9rcoles", "Mano gira en c\u00edrculo horizontal sobre la otra."),
        ("jueves", "Mano en pinza; la otra abajo; giro de mu\u00f1eca."),
        ("viernes", "Mano en V; movimiento lateral."),
        ("s\u00e1bado", "Pu\u00f1o horizontal; c\u00edrculo con el pulgar hacia arriba."),
        ("domingo", "Ambas manos abiertas; dos arcos hacia los lados."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 6,
        "lesson_title": "D\u00edas de la semana",
        "difficulty": "intermedio",
        "xp": 18,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word in ("lunes", "viernes"):
            l[8] = (l[8][0] - 0.04, l[8][1] - 0.04)
        elif word == "mi\u00e9rcoles":
            l[8] = (l[8][0] - 0.06, l[8][1] - 0.02)
            l[9] = (l[9][0] - 0.02, l[9][1] - 0.02)
        elif word == "jueves":
            l[4] = (l[4][0] - 0.03, l[4][1] + 0.03)
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
        elif word == "s\u00e1bado":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.01)
        elif word == "domingo":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.02)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": True,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 7: EMOCIONES (5) ============================
def generate_emociones():
    items = [
        ("feliz", "Ambas manos en c\u00edrculo sobre el pecho; expresi\u00f3n sonriente."),
        ("triste", "Dedos deslizan hacia abajo por el rostro."),
        ("enojado", "Manos en garra frente al pecho; separar y cerrar con tensi\u00f3n."),
        ("sorprendido", "Ambas manos abiertas a la altura del rostro; abrir los ojos."),
        ("cansado", "Apoyar la mejilla sobre la palma y cerrar los ojos."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 7,
        "lesson_title": "Emociones y sentimientos",
        "difficulty": "intermedio",
        "xp": 18,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word == "feliz":
            l[8] = (l[8][0] - 0.03, l[8][1] - 0.03)
            l[12] = (l[12][0] - 0.02, l[12][1] - 0.02)
            l[16] = (l[16][0] - 0.02, l[16][1] - 0.02)
        elif word == "triste":
            l[8] = (l[8][0] - 0.02, l[8][1] + 0.03)
            l[12] = (l[12][0] - 0.01, l[12][1] + 0.02)
        elif word == "enojado":
            l[8] = (l[8][0] - 0.04, l[8][1] - 0.02)
            l[12] = (l[12][0] - 0.03, l[12][1] - 0.02)
            l[16] = (l[16][0] - 0.02, l[16][1] - 0.02)
        elif word == "sorprendido":
            l[8] = (l[8][0] + 0.03, l[8][1] - 0.03)
            l[12] = (l[12][0] + 0.02, l[12][1] - 0.02)
            l[16] = (l[16][0] + 0.02, l[16][1] - 0.02)
        elif word == "cansado":
            l[8] = (l[8][0] - 0.02, l[8][1] + 0.02)
            l[12] = (l[12][0] - 0.01, l[12][1] + 0.01)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 8: COMIDA (4) ============================
def generate_comida():
    items = [
        ("agua", "Mano en C; toca el ment\u00f3n con indic y pulgar."),
        ("pan", "Dedos juntos; corte suave en el aire sobre la palma opuesta."),
        ("fruta", "Sostener fruta imaginaria y llevarla a la boca."),
        ("cafe", "Pu\u00f1o cerrado; moler sobre la palma opuesta."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 8,
        "lesson_title": "Comida y bebida",
        "difficulty": "intermedio",
        "xp": 18,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word == "agua":
            l[8] = (l[8][0] - 0.06, l[8][1] - 0.03)
            l[4] = (l[4][0] - 0.03, l[4][1] + 0.01)
        elif word == "pan":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] - 0.01, l[k][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.02)
        elif word == "fruta":
            l[8] = (l[8][0] - 0.04, l[8][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.02)
        elif word == "cafe":
            for k in (2, 3, 4, 5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] + 0.01, l[k][1] + 0.01)
            l[4] = (l[4][0] + 0.03, l[4][1] - 0.03)
        feat = gen_features(i * 1.5, 0.60)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 9: PREGUNTAS (4) ============================
def generate_preguntas():
    items = [
        ("que", "\u00edndice extendido; movimiento lateral con expresi\u00f3n interrogativa."),
        ("quien", "\u00edndice toca el pulgar cerca del ment\u00f3n; c\u00edrculo peque\u00f1o."),
        ("donde", "\u00edndice extendido; c\u00edrculos en el aire hacia el frente."),
        ("como", "Ambas manos en C enfrentadas; girar hacia arriba."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 9,
        "lesson_title": "Preguntas b\u00e1sicas",
        "difficulty": "intermedio",
        "xp": 18,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        l[8] = (l[8][0] - 0.05, l[8][1] - 0.03)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 10: Lugares (4) ============================
def generate_lugares():
    items = [
        ("casa", "Yemas de ambos pu\u00f1os juntas formando un tejado."),
        ("escuela", "Dos palmadas suaves con la palma abierta sobre la otra."),
        ("hospital", "\u00edndice dibuja una cruz sobre el brazo contrario."),
        ("izquierda", "\u00edndice extendido; se\u00f1ala hacia la izquierda."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 10,
        "lesson_title": "Lugares y direcciones",
        "difficulty": "avanzado",
        "xp": 20,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word == "casa":
            l[8] = (l[8][0] - 0.06, l[8][1] - 0.03)
            l[12] = (l[12][0] - 0.04, l[12][1] - 0.02)
            l[16] = (l[16][0] - 0.02, l[16][1] - 0.01)
        elif word == "escuela":
            l[5] = (l[5][0] - 0.03, l[5][1] - 0.02)
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
            l[9] = (l[9][0] + 0.02, l[9][1] + 0.01)
        elif word == "hospital":
            l[8] = (l[8][0] - 0.03, l[8][1] + 0.02)
            l[12] = (l[12][0] + 0.02, l[12][1] - 0.01)
        elif word == "izquierda":
            l[8] = (l[8][0] - 0.02, l[8][1] + 0.03)
            l[4] = (l[4][0] - 0.01, l[4][1] + 0.02)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 11: CLIMA (4) ============================
def generate_clima():
    items = [
        ("sol", "\u00edndice dibuja c\u00edrculo y extiende los dedos como rayos."),
        ("lluvia", "Dedos bajan de ambas manos abiertas simulando gotas."),
        ("frio", "Cruza los brazos sobre el pecho; frota los brazos con las manos."),
        ("calor", "Abanica el rostro con la mano abierta."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 11,
        "lesson_title": "El clima",
        "difficulty": "avanzado",
        "xp": 20,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word == "sol":
            l[8] = (l[8][0] - 0.05, l[8][1] - 0.03)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.01)
        elif word == "lluvia":
            for k in (5, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20):
                l[k] = (l[k][0] - 0.01, l[k][1] + 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] - 0.02)
        elif word == "frio":
            l[8] = (l[8][0] - 0.02, l[8][1] + 0.02)
            l[12] = (l[12][0] - 0.01, l[12][1] + 0.01)
        elif word == "calor":
            l[8] = (l[8][0] + 0.03, l[8][1] + 0.01)
            l[12] = (l[12][0] + 0.02, l[12][1] + 0.01)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= LECCION 12: VERBOS (4) ============================
def generate_verbos():
    items = [
        ("comer", "Llevar repetidamente los dedos juntos a la boca."),
        ("dormir", "Apoyar la mejilla sobre la palma y cerrar los ojos."),
        ("trabajar", "Golpear suavemente el dorso de la mano cerrada sobre la mu\u00f1eca contraria."),
        ("estudiar", "Abrir y cerrar la mano frente a la frente, como pasar p\u00e1ginas."),
    ]
    data = {
        "schema": "seva-landmarks-v1",
        "lesson_id": 12,
        "lesson_title": "Verbos comunes",
        "difficulty": "avanzado",
        "xp": 20,
        "hand_side": "right",
        "version": 1,
        "generated": "2026-11-06",
        "reference_signs": [],
    }
    for i, (word, desc) in enumerate(items):
        l = base_hand(0.0, 0.0)
        if word == "comer":
            l[8] = (l[8][0] - 0.03, l[8][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.02)
        elif word == "dormir":
            l[8] = (l[8][0] - 0.02, l[8][1] + 0.02)
            l[12] = (l[12][0] - 0.01, l[12][1] + 0.01)
        elif word == "trabajar":
            l[8] = (l[8][0] - 0.03, l[8][1] - 0.02)
            l[4] = (l[4][0] + 0.02, l[4][1] + 0.01)
        elif word == "estudiar":
            l[8] = (l[8][0] - 0.02, l[8][1] - 0.02)
            l[12] = (l[12][0] - 0.01, l[12][1] - 0.01)
        feat = gen_features(i * 1.5, 0.62)
        add_sign(data, {
            "sign_id": word,
            "palabra": word,
            "description": desc,
            "con_movimiento": False,
            "landmarks": [landmark_to_dict(p) for p in l],
            "features": feat,
            "valid_range": valid_range(feat),
        })
    return data


# ================= MAIN ============================
def main():
    print("Generando datasets de landmarks de referencia en", OUT_DIR)
    files = [
        ("alfabeto.json", generate_alfabeto()),
        ("saludos.json", generate_lsc_from_json(2, "Saludos y presentaciones", "basico", 15, "public/data/lsc/senas.json")),
        ("numeros.json", generate_numeros()),
        ("colores.json", generate_colores()),
        ("familia.json", generate_familia()),
        ("dias.json", generate_dias()),
        ("emociones.json", generate_emociones()),
        ("comida.json", generate_comida()),
        ("preguntas.json", generate_preguntas()),
        ("lugares.json", generate_lugares()),
        ("clima.json", generate_clima()),
        ("verbos.json", generate_verbos()),
    ]
    for name, obj in files:
        path = os.path.join(OUT_DIR, name)
        with open(path, "w", encoding="utf-8") as f:
            json.dump(obj, f, ensure_ascii=False, indent=2)
        with open(path, encoding="utf-8") as f:
            loaded = json.load(f)
        assert loaded["lesson_id"] == obj["lesson_id"], "lesson_id mismatch"
        assert len(loaded["reference_signs"]) >= 4, "too few signs"
        print("  OK", name, "-", len(loaded["reference_signs"]), "se\u00f1as")
    print("\nTotal archivos:", len(files))
    print("Ruta base:", os.path.abspath(OUT_DIR))


if __name__ == "__main__":
    main()
