#!/bin/bash
mkdir -p test-results
scp -i ~/.ssh/id_ed25519_do scripts/_envchk.php root@46.101.111.131:/var/www/_envchk.php
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker cp /var/www/_envchk.php sms-app:/var/www/_envchk.php
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app php /var/www/_envchk.php > test-results/envchk.txt 2>&1
cat test-results/envchk.txt
