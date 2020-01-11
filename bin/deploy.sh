#!/usr/bin/env bash

export $(grep -v '^#' .env | xargs -d '\n')

ssh $DEPLOY_HOST "
  echo 'Change directory to $DEPLOY_PATH'
  cd $DEPLOY_PATH

  echo 'Checkout $DEPLOY_BRANCH branch'
  git checkout $DEPLOY_BRANCH

  echo 'Pull latest changes'
  git pull

  echo 'Install composer dependencies'
  composer install --no-ansi --no-interaction --no-plugins --no-progress --no-scripts --no-suggest --optimize-autoloader

  echo 'Install npm dependencies'
  npm install

  echo 'Build assets for production'
  npm run production

  echo 'Restart $DEPLOY_SERVICE service'
  sudo systemctl restart $DEPLOY_SERVICE
"
