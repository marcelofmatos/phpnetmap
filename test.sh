#!/bin/sh
set -e

cd "$(dirname "$0")"

docker build -f Dockerfile.test -t phpnetmap-test .
docker run --rm phpnetmap-test
