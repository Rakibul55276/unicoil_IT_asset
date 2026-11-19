#!/bin/bash
# Rebuild the QA database from the project backup and seed one account per role.
set -e
sudo mariadb -e "DROP DATABASE IF EXISTS central_asset_db; CREATE DATABASE central_asset_db;"
sudo mariadb central_asset_db < /home/claude/app/database/backups/central_asset_db-2026-11-09.sql
H=$(php -r 'echo password_hash("Test@12345", PASSWORD_BCRYPT);')
sed "s|__HASH__|$H|g" /home/claude/qa/seed.sql | sudo mariadb central_asset_db
