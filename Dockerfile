# A basic config. for phpnetmap

FROM tutum/apache-php

MAINTAINER Marcelo Matos

RUN apt-get update \
    && apt-get install -y php5-snmp php5-sqlite sqlite3 snmpd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/-V systemonly//g' /etc/snmp/snmpd.conf \ 
	&& echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE' > /etc/php5/mods-available/errorreporting.ini \
	&& php5enmod errorreporting \
	&& sed -i '2i service snmpd start' /run.sh


# TODO: configurar protected/data para container de persistência
