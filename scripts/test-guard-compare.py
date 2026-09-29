#!/usr/bin/env python3
"""Compare a PHPUnit junit report against the known-failures list(s).

Exit codes:
  0  suite green and no stale entries
  1  new failing tests not in the list
  2  stale entries: tests listed as known-failing that now PASS
"""
import os
import sys
import xml.etree.ElementTree as ET

junit_path = sys.argv[1]
list_paths = sys.argv[2:]

root = ET.parse(junit_path).getroot()

current = set()
total = failed = errored = skipped = 0
skip_names = []
for case in root.iter('testcase'):
    total += 1
    failure = case.find('failure')
    error = case.find('error')
    skip = case.find('skipped')
    cls = (case.get('classname') or '').replace('.', '\\')
    meth = case.get('name') or ''
    name = f'{cls}::{meth}'
    if failure is not None or error is not None:
        current.add(name)
        if failure is not None:
            failed += 1
        else:
            errored += 1
    elif skip is not None:
        skipped += 1
        if len(skip_names) < 25:
            reason = (skip.get('message') or skip.text or '').strip().split('\n')[0][:120]
            skip_names.append(f'{name} - {reason}' if reason else name)

known = set()
for path in list_paths:
    if path and os.path.exists(path):
        with open(path, encoding='utf-8') as fh:
            known.update(
                line.strip()
                for line in fh
                if line.strip() and not line.strip().startswith('#')
            )

new = current - known
recovered = known - current

print(f'[test-guard] Suite totals: {total} tests, {failed} failures, {errored} errors, {skipped} skipped.')
if skip_names:
    print('[test-guard] Skipped tests (up to 25 shown):')
    for n in skip_names:
        print(f'  {n}')

if new:
    print(f'[test-guard] FAIL: {len(new)} new failing test(s) not in the known-failures list(s):')
    for name in sorted(new):
        print(f'  {name}')
    print(f'[test-guard] baseline listed: {len(known)}, current failing: {len(current)}')
    sys.exit(1)

if recovered:
    print(f'[test-guard] FAIL: {len(recovered)} test(s) listed in the known-failures list(s) now PASS; remove stale entries:')
    for name in sorted(recovered):
        print(f'  {name}')
    print(f'[test-guard] baseline listed: {len(known)}, current failing: {len(current)}')
    sys.exit(2)

print(f'[test-guard] OK: {len(current)} failing test(s), all accounted for in the known-failures list(s); no stale entries.')
