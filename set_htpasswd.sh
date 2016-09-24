
if [ -z "$ADMIN_USER" ]; then
ADMIN_USER=admin
fi;

if [ -z "$ADMIN_PASSWORD" ]; then
ADMIN_PASSWORD=$(tr -dc A-Za-z0-9_ < /dev/urandom | head -c 8 | xargs)
fi;

htpasswd -b /app/.htpasswd "$ADMIN_USER" "$ADMIN_PASSWORD"

echo -e "\nPHPNetMap HTTP Authentication:\n\nUser: $ADMIN_USER\nPassword: $ADMIN_PASSWORD\n\n"
