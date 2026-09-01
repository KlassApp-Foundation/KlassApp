#!/bin/bash
echo "=== restarting PHP-FPM ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh -c 'kill -USR2 1; sleep 2; ls /proc/*/cmdline 2>/dev/null | xargs -I{} cat {} 2>/dev/null | grep -l php-fpm 2>/dev/null | head -3'
echo "--- after USR2 ---"
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app ps aux | grep php-fpm | head -3
