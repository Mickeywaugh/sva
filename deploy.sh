#!/bin/bash

git fetch origin
git reset --hard origin/master # 假设你想要重置的是master分支

git pull

cd api/

composer config -g repo.packagist composer https://mirrors.cloud.tencent.com/composer/
composer update
php bin/console lexik:jwt:generate-keypair

cd ../vue

npm config set registry http://mirrors.cloud.tencent.com/npm/
npm i -g pnpm
pnpm i
pnpm run build

cp dist/index.html ../public/

cp -r dist/* ../public/
