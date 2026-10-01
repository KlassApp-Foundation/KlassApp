#!/usr/bin/env python3
"""Run a PHP snippet on the STAGING environment via the Laravel Cloud API.

Reads PHP from stdin and prints the command output (plus any sentinel JSON the
snippet echoes). Hard-guards the staging environment id — never production.
"""
import importlib.util, json, os, sys, time, base64

KC_PATH = os.environ.get(
    "E2E_KC_PATH",
    os.path.expanduser("~/.openclaw-autoclaw/workspace/.openclaw/tmp/kc.py"),
)
EXPECTED_PREFIX = os.environ.get("E2E_STG_ENV_PREFIX", "env-a2b86c90")

spec = importlib.util.spec_from_file_location("kc", KC_PATH)
kc = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kc)

assert str(kc.STG).startswith(EXPECTED_PREFIX), f"Refusing to run: not staging ({kc.STG})"

php = sys.stdin.read()
if not php.strip():
    sys.exit("stg_bridge: empty PHP on stdin")

b64 = base64.b64encode(php.encode()).decode()
cmd = "php artisan tinker --execute=\"eval(base64_decode('%s'))\"" % b64

r = kc.call(f"/environments/{kc.STG}/commands", "POST", {"command": cmd})
cid = (r.get("data") or {}).get("id")
if not cid:
    print("BRIDGE_ERR " + json.dumps(r)[:400])
    sys.exit(1)

tries = int(os.environ.get("E2E_BRIDGE_TRIES", "90"))
for _ in range(tries):
    time.sleep(4)
    s = kc.call("/commands/" + cid)
    a = (s.get("data") or {}).get("attributes", {})
    st = str(a.get("status"))
    if st not in ("command.running", "command.pending", "command.queued", "None"):
        sys.stdout.write(a.get("output") or "")
        sys.exit(0 if st == "command.success" else 0)

print("BRIDGE_TIMEOUT")
sys.exit(2)
