#!/bin/bash
cd /var/www/html/server/test
configScripts/svnChanged.sh
php index.php > human/results.txt
