#!/bin/bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 grep -rE 'error_log|access_log' /var/www/nginx/ 2>/dev/null | head -10
echo "---"
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 ls /var/log/nginx/ 2>/dev/null
