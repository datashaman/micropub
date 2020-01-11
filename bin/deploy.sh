#!/usr/bin/env bash

ssh $DEPLOY_HOST "
    cd $DEPLOY_PATH

    git checkout $DEPLOY_BRANCH
    git pull

    composer install
    npm install --production
    npm prune --production
    npm run production

    sudo systemctl restart php7.2-fpm
"
