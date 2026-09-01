#!/bin/bash
mkdir -p test-results
scp -i ~/.ssh/id_ed25519_do scripts/_opcache.php root@46.101.111.131:/var/www/_opcache.php
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker cp /var/www/_opcache.php sms-app:/var/www/_opcache.php
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app php /var/www/_opcache.php > test-results/opcache.txt 2>&1
cat test-results/opcache.txt
