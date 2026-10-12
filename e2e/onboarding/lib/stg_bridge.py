#!/usr/bin/env python3
"""Guarded staging bridge for the onboarding journeys.

Two modes, both staging-only (environment id must start with the staging
prefix). Production is refused. There is no SSH path.

  * PHP on stdin — Laravel Cloud Commands API (tinker snippet).
  * --logs EMAIL — Laravel Cloud log stream (GET /environments/{id}/logs),
    used to read a sign-up verification code for that email only.
"""
import base64
import json
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timedelta, timezone

# Staging environment only. The full id is the Cloud resource the Commands and
# log-stream APIs address; the prefix check refuses any other environment.
STAGING_ENV_ID = os.environ.get(
    "E2E_STG_ENV_ID",
    "env-a2b86c90-4bf8-4889-9c2d-d10fe62db016",
)
EXPECTED_PREFIX = os.environ.get("E2E_STG_ENV_PREFIX", "env-a2b86c90")
API_BASE = "https://cloud.laravel.com/api"
UA = "curl/8.7.1"


def assert_staging(env_id=None):
    env_id = str(env_id or STAGING_ENV_ID)
    if not env_id.startswith(EXPECTED_PREFIX):
        sys.exit(f"stg_bridge: refusing to run: not staging ({env_id})")
    return env_id


def token():
    env = os.environ.get("CLOUD_AGENT_TOOLING") or os.environ.get("LARAVEL_CLOUD")
    if env and env.strip():
        return env.strip()
    # A bare `doppler secrets get` needs a project. klassapp/dev holds the
    # Cloud tooling token; my-agent/dev keeps the same value under another name.
    attempts = (
        ["doppler", "secrets", "get", "CLOUD_AGENT_TOOLING", "--project", "klassapp", "--config", "dev", "--plain"],
        ["doppler", "secrets", "get", "LARAVEL_CLOUD", "--project", "my-agent", "--config", "dev", "--plain"],
    )
    for cmd in attempts:
        try:
            value = subprocess.check_output(cmd, text=True, stderr=subprocess.DEVNULL).strip()
        except subprocess.CalledProcessError:
            continue
        if value:
            return value
    sys.exit("stg_bridge: Cloud token unavailable (set CLOUD_AGENT_TOOLING)")


def call(path, method="GET", body=None, tok=None):
    tok = tok or token()
    data = json.dumps(body).encode() if body is not None else None
    req = urllib.request.Request(
        API_BASE + path,
        data=data,
        method=method,
        headers={
            "Authorization": "Bearer " + tok,
            "Accept": "application/json",
            "User-Agent": UA,
            "Content-Type": "application/json",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=90) as response:
            return json.loads(response.read().decode())
    except urllib.error.HTTPError as exc:
        return {"_http": exc.code, "_body": exc.read()[:400].decode(errors="replace")}
    except Exception as exc:
        return {"_err": str(exc)}


def run_php(php, env_id=None):
    env_id = assert_staging(env_id)
    if not php.strip():
        sys.exit("stg_bridge: empty PHP on stdin")

    b64 = base64.b64encode(php.encode()).decode()
    cmd = "php artisan tinker --execute=\"eval(base64_decode('%s'))\"" % b64

    create_tries = int(os.environ.get("E2E_BRIDGE_CREATE_TRIES", "3"))
    poll_tries = int(os.environ.get("E2E_BRIDGE_TRIES", "36"))
    poll_sleep = float(os.environ.get("E2E_BRIDGE_POLL_SLEEP", "5"))

    last_err = None
    for attempt in range(1, create_tries + 1):
        created = call(f"/environments/{env_id}/commands", "POST", {"command": cmd})
        cid = (created.get("data") or {}).get("id")
        if not cid:
            last_err = created
            time.sleep(min(8 * attempt, 30))
            continue

        for _ in range(poll_tries):
            time.sleep(poll_sleep)
            status_body = call("/commands/" + cid)
            attributes = (status_body.get("data") or {}).get("attributes", {})
            status = str(attributes.get("status"))
            if status in ("command.running", "command.pending", "command.queued", "command.created", "None"):
                continue
            out = attributes.get("output") or ""
            sys.stdout.write(out)
            if status == "command.success":
                return 0
            sys.stderr.write(f"stg_bridge: command {cid} status={status}\n")
            if "<<<E2E-JSON>>>" in out:
                return 0
            last_err = {"status": status, "output_tail": out[-400:], "id": cid}
            break
        else:
            sys.stderr.write("BRIDGE_TIMEOUT\n")
            last_err = {"timeout": True, "id": cid}
            continue

        time.sleep(min(8 * attempt, 30))

    print("BRIDGE_ERR " + json.dumps(last_err)[:600], file=sys.stderr)
    return 1


def fetch_logs(minutes=10, query=None, pages=6, env_id=None):
    """Read the Laravel Cloud log stream for the staging environment."""
    env_id = assert_staging(env_id)
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
        body = call(f"/environments/{env_id}/logs?{urllib.parse.urlencode(params)}")
        if isinstance(body, dict) and body.get("_http"):
            return {"ok": False, "error": body, "rows": rows}
        batch = (body or {}).get("data") or []
        rows.extend(batch)
        cursor = ((body or {}).get("meta") or {}).get("cursor") or ""
        if not batch or not cursor:
            break
    return {"ok": True, "rows": rows}


def _row_text(row):
    """Message plus structured payload.

    Cloud returns the Log::info context under data.context (email, code),
    not inside message.
    """
    data = row.get("data")
    return str(row.get("message") or "") + " " + (json.dumps(data) if data else "")


def extract_code_for_email(email, minutes=10):
    """Find a verification code in the staging log stream for this email only.

    A row is a candidate only when it names the account's exact email. A code
    belonging to another account is never accepted.
    """
    result = fetch_logs(minutes=minutes, query="klassapp.email_verification_code")
    if not result.get("ok"):
        return {"ok": False, "error": result.get("error"), "source": "logs"}
    code = None
    needle = email.lower()
    for row in reversed(result["rows"]):
        msg = _row_text(row)
        if needle not in msg.lower():
            continue
        match = re.search(
            r"'code'\s*=>\s*'(\d{6})'|\"code\"\s*:\s*\"(\d{6})\"|code[\"']?\s*[:=]\s*[\"']?(\d{6})",
            msg,
        )
        if not match:
            continue
        code = next(group for group in match.groups() if group)
        break
    if not code:
        result2 = fetch_logs(minutes=minutes, query="Your KlassApp code is")
        for row in reversed(result2.get("rows") or []):
            msg = _row_text(row)
            if needle not in msg.lower():
                continue
            match = re.search(r"Your KlassApp code is (\d{6})", msg)
            if match:
                code = match.group(1)
                break
    if not code:
        return {"ok": False, "error": "code-not-in-logs", "source": "logs", "scanned": len(result["rows"])}
    return {"ok": True, "code": code, "source": "logs"}


def poll_code(email, tries=8, sleep_s=3.0, minutes=15):
    last = None
    for _ in range(tries):
        last = extract_code_for_email(email, minutes=minutes)
        if last.get("ok"):
            return last
        time.sleep(sleep_s)
    return last or {"ok": False, "error": "poll-exhausted", "source": "logs"}


def main(argv):
    assert_staging()
    if len(argv) >= 2 and argv[1] == "--logs":
        email = argv[2] if len(argv) > 2 else ""
        if not email:
            print(json.dumps({"ok": False, "error": "email-required"}))
            return 1
        print(json.dumps(poll_code(email)))
        return 0
    return run_php(sys.stdin.read())


if __name__ == "__main__":
    sys.exit(main(sys.argv))
