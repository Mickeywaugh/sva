#!/bin/bash

# npm config set registry http://mirrors.cloud.tencent.com/npm/
pnpm run build
# 如果dist/favicon.ico存在则复制到dist/
if [ -f "src/assets/favicon.ico" ]; then
  cp src/assets/favicon.ico ./dist/
fi