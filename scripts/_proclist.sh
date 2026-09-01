#!/bin/bash
scp -i ~/.ssh/id_ed25519_do scripts/_proclist-inner.sh root@46.101.111.131:/tmp/_proclist.sh
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker cp /tmp/_proclist.sh sms-app:/tmp/_proclist.sh
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh /tmp/_proclist.sh > test-results/proclist.txt 2>&1
cat test-results/proclist.txt
