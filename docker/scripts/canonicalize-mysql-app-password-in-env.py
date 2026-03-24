#!/usr/bin/env python3
"""Прибрати MYSQL_PASSWORD з production.env — канон лише DB_PASSWORD.

У docker/compose.yaml для образу mysql підставляється
  ${DB_PASSWORD:-${MYSQL_PASSWORD:-redpanda}}
тобто якщо задано DB_PASSWORD, рядок MYSQL_PASSWORD у файлі не впливає на MySQL
і лише плутає (два «різні» паролі в одному файлі).

Ніколи не перезаписує DB_PASSWORD значенням з MYSQL_PASSWORD — у БД залишився той пароль,
з яким ініціалізували том (зазвичай саме DB_PASSWORD на момент першого старту).

Використання:
  python3 docker/scripts/canonicalize-mysql-app-password-in-env.py /path/to/production.env
"""
from __future__ import annotations

import sys
from datetime import datetime, timezone
from pathlib import Path
import shutil


def main() -> int:
    if len(sys.argv) != 2:
        print("usage: canonicalize-mysql-app-password-in-env.py PATH_TO_production.env", file=sys.stderr)
        return 2
    path = Path(sys.argv[1])
    if not path.is_file():
        print(f"not a file: {path}", file=sys.stderr)
        return 1

    lines = path.read_text().splitlines()
    removed = sum(1 for ln in lines if ln.startswith("MYSQL_PASSWORD="))
    if removed == 0:
        print("OK nothing_to_do (no MYSQL_PASSWORD= lines)")
        return 0

    bak = path.with_suffix(path.suffix + ".bak." + datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ"))
    shutil.copy2(path, bak)

    out = [ln for ln in lines if not ln.startswith("MYSQL_PASSWORD=")]
    path.write_text("\n".join(out) + "\n")
    print(f"OK backup={bak.name} removed_mysql_password_lines={removed} (DB_PASSWORD unchanged)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
