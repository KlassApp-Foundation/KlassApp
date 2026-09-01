#!/bin/bash
# Toggle APP_DEBUG=true, run test, restore APP_DEBUG=false.
echo "=== turning APP_DEBUG=true ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app grep '^APP_DEBUG' .env

echo ""
echo "=== clearing cache ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app php artisan optimize:clear 2>&1 | tail -5

echo ""
echo "=== running test ==="
bash scripts/run-pw-step1.sh 2>&1 | tail -8

echo ""
echo "=== logs after ==="
sleep 3
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app ls -la storage/logs/laravel-2026-08-31.log

echo ""
echo "=== restoring APP_DEBUG=false ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app grep '^APP_DEBUG' .env
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app php artisan optimize:clear 2>&1 | tail -3
