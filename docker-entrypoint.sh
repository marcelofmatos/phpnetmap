#!/bin/bash
set -e

# Sobe o daemon SNMP local e gera o htpasswd antes de entregar ao Apache.
service snmpd start || true

#[ -f /app/.htpasswd ] || /app/set_htpasswd.sh
/app/set_htpasswd.sh

# Sincroniza a conta Yii "admin" (login da aplicação) com o mesmo
# ADMIN_PASSWORD usado acima pro HTTP Basic Auth — camada independente,
# ver protected/components/UserIdentity.php e seed_admin_user.php.
php /app/seed_admin_user.php

exec "$@"
