#!/bin/bash
mkdir -p test-results
echo "=== laravel.log (most recent) ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app ls -la storage/logs/laravel.log
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app tail -2 storage/logs/laravel.log > test-results/laralog.txt 2>&1
wc -l test-results/laralog.txt
