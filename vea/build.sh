#!/bin/bash

# npm config set registry http://mirrors.cloud.tencent.com/npm/
pnpm run build
cp src/assets/favicon.ico ./dist/