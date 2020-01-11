#!/usr/bin/env bash

export $(grep -v '^#' .env | xargs -d '\n')

ssh $DEPLOY_HOST "
  echo 'Change directory to $DEPLOY_PATH'
  cd $DEPLOY_PATH

  echo 'Checkout $DEPLOY_BRANCH branch'
  git checkout $DEPLOY_BRANCH

  echo 'Pull latest changes'
  git pull

  echo 'Install npm dependencies'
  npm install

  echo 'Build assets for production'
  npm run production

  echo 'Prune npm dependencies'
  npm prune --production

  echo 'Restart $DEPLOY_SERVICE service'
  sudo systemctl restart $DEPLOY_SERVICE
"
