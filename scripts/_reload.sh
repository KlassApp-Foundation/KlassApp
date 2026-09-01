#!/bin/bash
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app kill -s USR2 1 2>&1
sleep 2
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app sh /tmp/_proclist.sh 2>&1 | grep php-fpm
