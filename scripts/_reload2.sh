#!/bin/bash
scp -i ~/.ssh/id_ed25519_do scripts/_proclist-inner.sh root@46.101.111.131:/tmp/_proclist.sh
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker cp /tmp/_proclist.sh sms-app:/tmp/_proclist.sh
# Use sh -c with kill built-in
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh -c "kill -USR2 1; sleep 2" 2>&1
echo "=== after USR2 ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh /tmp/_proclist.sh 2>&1 | grep php-fpm
