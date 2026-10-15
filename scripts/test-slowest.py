#!/usr/bin/env python3
"""Print the N slowest test cases from a junit report as a Markdown table."""
import sys
import xml.etree.ElementTree as ET

path = sys.argv[1]
n = int(sys.argv[2]) if len(sys.argv) > 2 else 15
cases = []
for case in ET.parse(path).getroot().iter('testcase'):
    cls = (case.get('classname') or case.get('class') or '').replace('.', '\\')
    cases.append((float(case.get('time') or 0), f"{cls}::{case.get('name')}"))
cases.sort(reverse=True)
print(f'### {n} slowest tests\n')
print('| # | Seconds | Test |\n|---|---:|---|')
for i, (t, name) in enumerate(cases[:n], 1):
    print(f'| {i} | {t:.2f} | `{name}` |')
