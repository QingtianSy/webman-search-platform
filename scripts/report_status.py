#!/usr/bin/env python3
import json
import shutil
import subprocess
import sys
from datetime import datetime


def run_python(script: str, *args: str) -> dict:
    try:
        proc = subprocess.run(
            [sys.executable, script, *args],
            capture_output=True,
            text=True,
            timeout=30,
        )
        body = proc.stdout.strip() or proc.stderr.strip()
        parsed = None
        if body:
            try:
                parsed = json.loads(body)
            except Exception:
                parsed = body
        return {
            "exit_code": proc.returncode,
            "data": parsed,
        }
    except Exception as e:
        return {"exit_code": 255, "data": str(e)}


def disk_status(path: str = "/") -> dict:
    usage = shutil.disk_usage(path)
    total = usage.total
    used = usage.used
    free = usage.free
    used_percent = round((used / total) * 100, 2) if total else 0
    return {
        "path": path,
        "total": total,
        "used": used,
        "free": free,
        "used_percent": used_percent,
    }


def main() -> int:
    base_url = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1"
    root = "/var/www/search-platform"
    report = {
        "generated_at": datetime.now().isoformat(),
        "health": run_python(f"{root}/scripts/check_health.py", base_url),
        "services": run_python(f"{root}/scripts/check_services.py"),
        "disk": disk_status("/"),
    }
    print(json.dumps(report, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
