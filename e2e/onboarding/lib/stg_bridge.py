#!/usr/bin/env python3
"""Run a PHP snippet on the STAGING environment via the Laravel Cloud API.

Reads PHP from stdin and prints the command output (plus any sentinel JSON the
snippet echoes). Hard-guards the staging environment id — never production.

Retries create + poll so hibernation wake-ups do not flake e2e mid-journey.
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

create_tries = int(os.environ.get("E2E_BRIDGE_CREATE_TRIES", "3"))
poll_tries = int(os.environ.get("E2E_BRIDGE_TRIES", "36"))  # 36*5s ≈ 3m per create
poll_sleep = float(os.environ.get("E2E_BRIDGE_POLL_SLEEP", "5"))

last_err = None
for attempt in range(1, create_tries + 1):
    r = kc.call(f"/environments/{kc.STG}/commands", "POST", {"command": cmd})
    cid = (r.get("data") or {}).get("id")
    if not cid:
        last_err = r
        # Hibernating / rate-limited — back off and retry.
        time.sleep(min(8 * attempt, 30))
        continue

    for _ in range(poll_tries):
        time.sleep(poll_sleep)
        s = kc.call("/commands/" + cid)
        a = (s.get("data") or {}).get("attributes", {})
        st = str(a.get("status"))
        # Laravel Cloud briefly reports command.created before queued/running.
        if st in ("command.running", "command.pending", "command.queued", "command.created", "None"):
            continue
        out = a.get("output") or ""
        sys.stdout.write(out)
        if st == "command.success":
            sys.exit(0)
        # Command finished but failed — retry whole create once more.
        sys.stderr.write(f"stg_bridge: command {cid} status={st}\n")
        if "<<<E2E-JSON>>>" in out:
            # Still return output so callers can parse; exit 0 for partial success.
            sys.exit(0)
        last_err = {"status": st, "output_tail": out[-400:], "id": cid}
        break
    else:
        sys.stderr.write("BRIDGE_TIMEOUT\n")
        last_err = {"timeout": True, "id": cid}
        continue

    # Failed command — retry create
    time.sleep(min(8 * attempt, 30))

print("BRIDGE_ERR " + json.dumps(last_err)[:600], file=sys.stderr)
sys.exit(1)
