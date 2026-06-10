#!/bin/bash
set -e

# Sobe o daemon SNMP local e gera o htpasswd antes de entregar ao Apache.
service snmpd start || true
/app/set_htpasswd.sh

exec "$@"
