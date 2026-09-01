#!/bin/bash
date
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 date
ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131 docker exec sms-app date
