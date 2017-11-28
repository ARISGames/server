#!/bin/bash
cat /var/www/html/server/services/migration/reset_database.sql | tee | mysql -u root -p
