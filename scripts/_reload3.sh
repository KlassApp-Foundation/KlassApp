#!/bin/bash
scp -i ~/.ssh/id_ed25519_do scripts/_proclist-inner.sh root@46.101.111.131:/tmp/_proclist.sh
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker cp /tmp/_proclist.sh sms-app:/tmp/_proclist.sh
# Use kill builtin in shell with numeric signal: USR2 = 12 on most platforms, or 31 on linux x86
# Actually on Linux x86_64: SIGUSR2 = 12
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh -c "kill -12 1; sleep 3" 2>&1
echo "=== after USR2 (sig 12) ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh /tmp/_proclist.sh 2>&1 | grep php-fpm
