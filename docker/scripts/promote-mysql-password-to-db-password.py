#!/usr/bin/env python3
"""Перенести пароль з MYSQL_PASSWORD у DB_PASSWORD (один канонічний рядок).

Бере значення MYSQL_PASSWORD з поточного файлу або з бекапу (другий аргумент).

Після зміни — обов'язково: php artisan config:clear && php artisan optimize у контейнері php.

Використання:
  python3 promote-mysql-password-to-db-password.py /path/to/production.env
  python3 promote-mysql-password-to-db-password.py /path/to/production.env /path/to/production.env.bak.…
"""
from __future__ import annotations

import sys
from datetime import datetime, timezone
from pathlib import Path
import shutil


def extract_mysql_password(lines: list[str]) -> str | None:
    mp_vals = [ln.split("=", 1)[1].strip().strip('"').strip("'") for ln in lines if ln.startswith("MYSQL_PASSWORD=") and "=" in ln]
    return mp_vals[-1] if mp_vals else None


def main() -> int:
    if len(sys.argv) not in (2, 3):
        print(
            "usage: promote-mysql-password-to-db-password.py TARGET.env [SOURCE.env_WITH_MYSQL_PASSWORD]",
            file=sys.stderr,
        )
        return 2
    path = Path(sys.argv[1])
    if not path.is_file():
        print(f"not a file: {path}", file=sys.stderr)
        return 1

    lines = path.read_text().splitlines()
    if len(sys.argv) == 3:
        src = Path(sys.argv[2])
        if not src.is_file():
            print(f"not a file: {src}", file=sys.stderr)
            return 1
        mp = extract_mysql_password(src.read_text().splitlines())
    else:
        mp = extract_mysql_password(lines)

    if not mp:
        print("no MYSQL_PASSWORD= found (add backup path as 2nd arg if key already removed from target)", file=sys.stderr)
        return 1

    db_vals = [ln.split("=", 1)[1].strip().strip('"').strip("'") for ln in lines if ln.startswith("DB_PASSWORD=") and "=" in ln]
    dbp = db_vals[-1] if db_vals else None
    if mp != dbp:
        print(f"promoting MYSQL_PASSWORD into DB_PASSWORD (new len {len(mp)} vs old db len {len(dbp or '')})", file=sys.stderr)

    bak = path.with_suffix(path.suffix + ".bak." + datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ"))
    shutil.copy2(path, bak)

    out: list[str] = []
    inserted = False
    for ln in lines:
        if ln.startswith("MYSQL_PASSWORD="):
            continue
        if ln.startswith("DB_PASSWORD="):
            continue
        out.append(ln)
        if ln.startswith("DB_USERNAME="):
            out.append(f"DB_PASSWORD={mp}")
            inserted = True

    if not inserted:
        print("no DB_USERNAME= line", file=sys.stderr)
        return 1

    path.write_text("\n".join(out) + "\n")
    print(f"OK backup={bak.name} single DB_PASSWORD set; MYSQL_PASSWORD lines removed from target")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
