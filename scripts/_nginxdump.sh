#!/bin/bash
mkdir -p test-results
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 cat /var/log/nginx/error.log > test-results/nginx-error.txt 2>&1
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 cat /var/log/nginx/access.log > test-results/nginx-access.txt 2>&1
echo "error.log:"
ls -la test-results/nginx-error.txt
echo "access.log:"
ls -la test-results/nginx-access.txt
