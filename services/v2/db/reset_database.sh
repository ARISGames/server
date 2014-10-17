#!/bin/bash
#cat /var/www/html/server/services/v2/reset_database.sql | tee | mysql -u root -p
cat /var/www/html/server/services/v2/reset_database.sql | mysql -u root -p
sudo rm -rf /var/www/html/server/gamedata/v2/*
git checkout /var/www/html/server/gamedata/v2
