#!/bin/sh
set -e

chown -R node:node /app/node_modules

exec su-exec node "$@"
