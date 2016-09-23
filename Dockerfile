# A basic config. for phpnetmap

FROM tutum/apache-php

MAINTAINER Marcelo Matos <marcelo.matos@ufrr.br>

RUN apt-get update \
    && apt-get install -y php5-snmp php5-sqlite sqlite3 snmpd git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# code
RUN rm -rf /app && cd / && git clone https://github.com/marcelofmatos/phpnetmap.git app

# volume for database and config
VOLUME /app/protected/data


# container config
ENV ALLOW_OVERRIDE true
RUN a2enmod rewrite \
    && sed -i 's/-V systemonly//g' /etc/snmp/snmpd.conf \ 
    && echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE' > /etc/php5/mods-available/errorreporting.ini \
    && echo 'disable_functions = ' > /etc/php5/mods-available/disable_functions.ini \
    && php5enmod errorreporting \
    && sed -i '2i service snmpd start' /run.sh

