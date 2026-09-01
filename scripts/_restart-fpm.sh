#!/bin/bash
# Restart the master so workers get fresh code.
# USR2 = graceful reload (workers continue with new code on next request)
# But with opcache validate=1 freq=2, even without USR2 the workers
# should pick up the new code in 2s. Let's force a full restart.
echo "=== sending USR2 ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh -c 'kill -USR2 1 && sleep 2 && echo SENT' 2>&1
echo ""
echo "=== workers after USR2 ==="
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh /tmp/_proclist.sh 2>&1 | grep php-fpm
