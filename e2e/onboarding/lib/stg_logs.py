#!/usr/bin/env python3
"""Read a sign-up code from the staging Cloud log stream.

Thin wrapper around stg_bridge.py --logs so the staging guard, token handling,
and log-stream client live in one place.
"""
import json
import sys

from stg_bridge import poll_code


if __name__ == "__main__":
    email = sys.argv[1] if len(sys.argv) > 1 else ""
    if not email:
        print(json.dumps({"ok": False, "error": "email-required"}))
        sys.exit(1)
    print(json.dumps(poll_code(email)))
