#!/bin/bash
set -e

echo "=== Logs BEFORE test ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app ls -la storage/logs/laravel-2026-08-31.log

echo ""
echo "=== Running test ==="
bash scripts/run-pw-step1.sh 2>&1 | tail -5

echo ""
echo "=== Logs AFTER test (wait 3s for file flush) ==="
sleep 3
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app ls -la storage/logs/laravel-2026-08-31.log

echo ""
echo "=== Last 30 lines ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app tail -30 storage/logs/laravel-2026-08-31.log > test-results/post-test-log.txt 2>&1
wc -l test-results/post-test-log.txt
echo "---"
head -50 test-results/post-test-log.txt
