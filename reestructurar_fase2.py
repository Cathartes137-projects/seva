# -*- coding: utf-8 -*-
"""Fase 2: reestructura de carpetas para producción.
  - public/  <- todo lo servido por el navegador
  - config/  <- conexion.php + seguridad.php (fuera del docroot)
  - limpieza de artefactos temporales/evolucion
"""
import shutil
from pathlib import Path

root = Path("C:/xampp/htdocs/seva")
pub = root / "public"
conf = root / "config"

# 1) Crear directorios objetivo
pub.mkdir(exist_ok=True)
conf.mkdir(exist_ok=True)
print(f"[1/4] directorios creados: {pub}  {conf}")

# 2) Mover carpetas + archivos de interfaz a public/
moves_public = [
    ("camara", "camara"),
    ("partials", "partials"),
    ("js", "js"),
    ("media", "media"),
    ("data", "data"),
    ("video", "video"),
    ("css", "css"),
    ("img", "img"),
    ("DataBase", "DataBase"),
    ("practica.php", "practica.php"),
    ("index.html", "index.html"),
    ("login.php", "login.php"),
    ("registro.php", "registro.php"),
    ("dashboard.php", "dashboard.php"),
    ("leccion.php", "leccion.php"),
    ("perfil.php", "perfil.php"),
    ("recuperar.php", "recuperar.php"),
    ("restablecer.php", "restablecer.php"),
    ("logout.php", "logout.php"),
    ("configuracion.php", "configuracion.php"),
    (".htaccess", ".htaccess"),
    (".gitignore", ".gitignore"),
]
for src, dst in moves_public:
    s = root / src
    d = pub / dst
    if s.exists():
        shutil.move(str(s), str(d))
        print(f"  >> MOVIO public/  {src}")
    else:
        print(f"  <!--  ADVERTENCIA  {src} no existe -->")

# 3) Mover cifrado/seguridad a config/
moves_config = [
    ("conexion.php", "conexion.php"),
    ("seguridad.php", "seguridad.php"),
]
for src, dst in moves_config:
    s = root / src
    d = conf / dst
    if s.exists():
        shutil.move(str(s), str(d))
        print(f"  >> MOVIO config/  {src}")
    else:
        print(f"  <!--  ADVERTENCIA  {src} no existe -->")

# 4) Eliminar artefactos temporales/evolucion
cleanup = [
    "db_dump.php",
    "check_agents.py",
    ".orca_groups.py",
    ".orca_schema.py",
    ".orca_status.py",
    "account_dump.json",
]
for f in cleanup:
    p = root / f
    if p.exists():
        p.unlink()
        print(f"  << ELIMO      {f}")
    else:
        print(f"  (no existe){f}")

print("FASE 2 MOVIMIENTOS COMPLETOS")
