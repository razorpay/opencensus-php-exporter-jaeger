#!/bin/bash
set -euo pipefail
# Deployment Script
echo "Setting BASEDIR"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )
AUTH_INSTALL_DIR="/home/ubuntu/auth-service"
ALOHOMORA_BIN=$(which alohomora)

# Install new version
echo  "Install new version"
cd $BASEDIR && rsync -avz --force --delete --progress --exclude-from=./.rsyncignore ./ "$AUTH_INSTALL_DIR"

# Fix permissions
echo  "Fix permissions"
cd "$AUTH_INSTALL_DIR" && sudo chmod 777 -R storage

# Run alohomora
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$AUTH_INSTALL_DIR/environment/.env.vault.j2"
$ALOHOMORA_BIN cast --region ap-south-1 --env $DEPLOYMENT_GROUP_NAME --app $APPLICATION_NAME "$AUTH_INSTALL_DIR/environment/env.php.j2"

# DB Migrate
echo  "DB Migrate"
cd "$AUTH_INSTALL_DIR" && php artisan migrate
