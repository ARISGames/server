#!/bin/bash
cat reset_database.sql | tee | mysql -u root -p
