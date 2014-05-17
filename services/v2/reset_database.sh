#!/bin/bash
cat reset_database.sql | tee | mysql -u root -p
rm -rf /var/www/html/server/gamedata/*
git checkout /var/www/html/server/gamedata
