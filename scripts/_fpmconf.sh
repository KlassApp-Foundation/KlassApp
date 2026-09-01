#!/bin/bash
mkdir -p test-results
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 grep -E 'error_log|slowlog|access\.log' /etc/php/8.3/fpm/pool.d/www.conf > test-results/fpmconf.txt 2>&1
cat test-results/fpmconf.txt
