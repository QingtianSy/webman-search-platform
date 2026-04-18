#!/usr/bin/env python3
import json
import sys
import urllib.request
import urllib.error


def fetch_json(url: str, timeout: int = 10) -> tuple[bool, dict | str]:
    try:
        req = urllib.request.Request(url, headers={"Accept": "application/json"})
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            data = resp.read().decode("utf-8", errors="replace")
            return True, json.loads(data)
    except urllib.error.HTTPError as e:
        return False, f"HTTP {e.code}"
    except Exception as e:
        return False, str(e)


def main() -> int:
    base_url = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1"
    base_url = base_url.rstrip("/")

    result = {
        "health": None,
        "ready": None,
        "ok": False,
    }

    ok_health, health = fetch_json(base_url + "/health")
    ok_ready, ready = fetch_json(base_url + "/ready")

    result["health"] = health
    result["ready"] = ready
    result["ok"] = bool(ok_health and ok_ready)

    print(json.dumps(result, ensure_ascii=False, indent=2))
    if not ok_health or not ok_ready:
        return 1
    if isinstance(ready, dict):
        data = ready.get("data", {}) if "data" in ready else ready
        if data.get("ready") is False:
            return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
