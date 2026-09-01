#!/bin/sh
for pid in $(ls /proc 2>/dev/null | grep -E '^[0-9]+$'); do
    cmd=$(cat /proc/$pid/cmdline 2>/dev/null | tr '\0' ' ')
    if [ -n "$cmd" ]; then
        echo "$pid: $cmd"
    fi
done | head -20
