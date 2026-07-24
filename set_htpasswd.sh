#!/bin/bash

if [ -z "$ADMIN_USER" ]; then
ADMIN_USER=admin
fi;

if [ -z "$ADMIN_PASSWORD" ]; then
ADMIN_PASSWORD=$(tr -dc A-Za-z0-9_ < /dev/urandom | head -c 8 | xargs)
fi;

printf '%s\n' "$ADMIN_PASSWORD" | htpasswd -i /app/.htpasswd "$ADMIN_USER"

MASKED_PASSWORD="${ADMIN_PASSWORD:0:2}$(printf '%*s' "$((${#ADMIN_PASSWORD} - 2))" '' | tr ' ' '*')"

echo -e "\nPHPNetMap HTTP Authentication:\n\nUser: $ADMIN_USER\nPassword: $MASKED_PASSWORD\n\n"
