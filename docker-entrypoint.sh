#!/bin/bash
set -e

# Sobe o daemon SNMP local e gera o htpasswd antes de entregar ao Apache.
service snmpd start || true

#[ -f /app/.htpasswd ] || /app/set_htpasswd.sh
/app/set_htpasswd.sh

# Sem framework de migrations: garante que tabelas/colunas adicionadas em
# releases mais novas existam mesmo num volume persistente de um deploy
# antigo (a .db versionada no repo só ajuda numa instalação nova) — ver
# ensure_schema.php. Roda ANTES do seed do admin, que já consulta o banco.
php /app/ensure_schema.php

# Sincroniza a conta Yii "admin" (login da aplicação) com o mesmo
# ADMIN_PASSWORD usado acima pro HTTP Basic Auth — camada independente,
# ver protected/components/UserIdentity.php e seed_admin_user.php.
php /app/seed_admin_user.php

exec "$@"
