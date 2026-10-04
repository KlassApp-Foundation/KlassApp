#!/usr/bin/env python3
"""Read staging Cloud runtime logs (Bearer via kc.py). Staging-env guard only."""
import importlib.util, json, os, re, sys, time, urllib.parse
from datetime import datetime, timezone, timedelta

KC_PATH = os.environ.get(
    "E2E_KC_PATH",
    os.path.expanduser("~/.openclaw-autoclaw/workspace/.openclaw/tmp/kc.py"),
)
EXPECTED_PREFIX = os.environ.get("E2E_STG_ENV_PREFIX", "env-a2b86c90")

spec = importlib.util.spec_from_file_location("kc", KC_PATH)
kc = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kc)
assert str(kc.STG).startswith(EXPECTED_PREFIX), f"Refusing: not staging ({kc.STG})"


def fetch_logs(minutes=10, query=None, pages=6):
    to = datetime.now(timezone.utc)
    fr = to - timedelta(minutes=minutes)
    cursor = ""
    rows = []
    for _ in range(pages):
        params = {
            "from": fr.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "to": to.strftime("%Y-%m-%dT%H:%M:%SZ"),
        }
        if query:
            params["query"] = query
        if cursor:
            params["cursor"] = cursor
        r = kc.call(f"/environments/{kc.STG}/logs?{urllib.parse.urlencode(params)}")
        if isinstance(r, dict) and r.get("_http"):
            return {"ok": False, "error": r, "rows": rows}
        batch = (r or {}).get("data") or []
        rows.extend(batch)
        cursor = ((r or {}).get("meta") or {}).get("cursor") or ""
        if not batch or not cursor:
            break
    return {"ok": True, "rows": rows}


def _row_text(row):
    """Message plus structured payload.

    Cloud returns the Log::info context under data.context (e.g.
    {"email": ..., "code": ...}), not inside "message", so matching on the
    message alone never finds the email or the code.
    """
    data = row.get("data")
    return str(row.get("message") or "") + " " + (json.dumps(data) if data else "")


def extract_code_for_email(email, minutes=10):
    """Find klassapp.email_verification_code log line for this email."""
    # Prefer structured marker; also accept "Your KlassApp code is NNNNNN".
    result = fetch_logs(minutes=minutes, query="klassapp.email_verification_code")
    if not result.get("ok"):
        return {"ok": False, "error": result.get("error"), "source": "logs"}
    code = None
    for row in reversed(result["rows"]):
        msg = _row_text(row)
        if email.lower() not in msg.lower():
            continue
        m = re.search(r"'code'\s*=>\s*'(\d{6})'|\"code\"\s*:\s*\"(\d{6})\"|code[\"']?\s*[:=]\s*[\"']?(\d{6})", msg)
        if not m:
            m = re.search(r"\b(\d{6})\b", msg)
        if m:
            code = next(g for g in m.groups() if g)
            break
    if not code:
        # Fallback subject line pattern.
        result2 = fetch_logs(minutes=minutes, query="Your KlassApp code is")
        for row in reversed(result2.get("rows") or []):
            msg = _row_text(row)
            if email.lower() not in msg.lower() and "KlassApp code" not in msg:
                continue
            m = re.search(r"Your KlassApp code is (\d{6})", msg)
            if m:
                code = m.group(1)
                break
    if not code:
        return {"ok": False, "error": "code-not-in-logs", "source": "logs", "scanned": len(result["rows"])}
    return {"ok": True, "code": code, "source": "logs"}


def poll_code(email, tries=8, sleep_s=3.0, minutes=15):
    last = None
    for i in range(tries):
        last = extract_code_for_email(email, minutes=minutes)
        if last.get("ok"):
            return last
        time.sleep(sleep_s)
    return last or {"ok": False, "error": "poll-exhausted", "source": "logs"}


if __name__ == "__main__":
    # Usage: stg_logs.py <email>
    email = sys.argv[1] if len(sys.argv) > 1 else ""
    if not email:
        print(json.dumps({"ok": False, "error": "email-required"}))
        sys.exit(1)
    print(json.dumps(poll_code(email)))
