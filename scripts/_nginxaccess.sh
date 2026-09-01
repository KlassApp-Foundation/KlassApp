#!/bin/bash
mkdir -p test-results
# Get last access log (active) and the most recent rotated
for f in /var/log/nginx/access.log /var/log/nginx/access.log.1; do
    echo "=== $f ==="
    ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 sudo cat $f 2>/dev/null > test-results/$(basename $f).txt
    ls -la test-results/$(basename $f).txt
done
