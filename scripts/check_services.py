#!/usr/bin/env python3
import json
import shutil
import subprocess
import sys

DEFAULT_SERVICES = [
    "nginx",
    "webman-search-platform",
    "mysql",
    "redis",
    "mongod",
    "elasticsearch",
]


def check_service(name: str) -> dict:
    if shutil.which("systemctl") is None:
        return {"service": name, "status": "unknown", "reason": "systemctl not found"}
    try:
        proc = subprocess.run(
            ["systemctl", "is-active", name],
            capture_output=True,
            text=True,
            timeout=8,
        )
        status = proc.stdout.strip() or proc.stderr.strip() or "unknown"
        return {"service": name, "status": status, "ok": status == "active"}
    except Exception as e:
        return {"service": name, "status": "error", "ok": False, "reason": str(e)}


def main() -> int:
    services = sys.argv[1:] or DEFAULT_SERVICES
    report = [check_service(name) for name in services]
    overall = all(item.get("ok") for item in report if "ok" in item)
    print(json.dumps({"ok": overall, "services": report}, ensure_ascii=False, indent=2))
    return 0 if overall else 1


if __name__ == "__main__":
    raise SystemExit(main())
