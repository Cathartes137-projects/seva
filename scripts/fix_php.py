#!/usr/bin/env python3
"""Reemplaza el bloque PHP de carga por el dinámico."""
SRC = "C:/xampp/htdocs/seva/public/camara/detector.php"

with open(SRC, encoding="utf-8") as f:
    lines = f.readlines()

# Buscar el inicio del bloque PHP antiguo
start_marker = "$secuencia = ['A', 'B', 'C', 'L', 'I'];\n"
end_marker = "}\n\n$mapaVocabulario = [];\n"

start_idx = None
end_idx = None

for i, line in enumerate(lines):
    if start_marker in line and start_idx is None:
        start_idx = i
    if start_idx is not None and end_marker in line and i > start_idx:
        end_idx = i
        break

if start_idx is None or end_idx is None:
    print("FALLO: no se encontraron los marcadores")
    print("Start marker found:", start_idx)
    print("End marker found:", end_idx)
    # Mostrar contexto
    for i, line in enumerate(lines):
        if "secuencia" in line:
            print(f"Line {i+1}: {line.rstrip()}")
            break
else:
    print(f"Replacing lines {start_idx+1} to {end_idx+1}")
    print(f"Start line: {lines[start_idx].rstrip()}")
    print(f"End line: {lines[end_idx].rstrip()}")
    
    new_lines = [
        "// ---------------- Parámetros dinámicos (Task 1a) ----------------\n",
        "$lesson_id  = isset($_GET['id'])  ? (int) $_GET['id']  : 0;\n",
        "$seña       = isset($_GET['seña']) ? mb_strtolower(trim($_GET['seña']), 'UTF-8') : null;\n",
        "\n",
        "$landmarks_dir = __DIR__ . '/../data/landmarks';\n",
        "$dataset      = null;\n",
        "$lesson_data  = null;\n",
        "\n",
        "if ($lesson_id > 0 && is_file($landmarks_dir . \"/{$lesson_id}.json\")) {\n",
        "    $raw = json_decode(file_get_contents($landmarks_dir . \"/{$lesson_id}.json\"), true);\n",
        "    if (is_array($raw) && isset($raw['reference_signs']) && count($raw['reference_signs']) > 0) {\n",
        "        $dataset = $raw;\n",
        "        $lesson_data = $raw;\n",
        "    }\n",
        "} elseif ($seña) {\n",
        "    $files = glob($landmarks_dir . '/*.json');\n",
        "    foreach ($files as $file) {\n",
        "        $raw = json_decode(file_get_contents($file), true);\n",
        "        if (!is_array($raw) || !isset($raw['reference_signs'])) continue;\n",
        "        foreach ($raw['reference_signs'] as $sign) {\n",
        "            if (isset($sign['palabra']) && mb_strtolower($sign['palabra'], 'UTF-8') === $seña) {\n",
        "                $dataset = $raw;\n",
        "                $lesson_data = $raw;\n",
        "                break 2;\n",
        "            }\n",
        "        }\n",
        "    }\n",
        "}\n",
        "\n",
        "$mapaVocabulario = [];\n",
        "if (!$lesson_data) {\n",
    ]
    
    lines[start_idx:end_idx+1] = new_lines
    
    with open(SRC, "w", encoding="utf-8") as f:
        f.writelines(lines)
    
    print("Replaced successfully")
