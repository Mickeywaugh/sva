#!/bin/bash


npm config set registry http://mirrors.cloud.tencent.com/npm/

npm i -g pnpm
pnpm i
pnpm run build
