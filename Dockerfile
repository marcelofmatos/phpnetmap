# PHPNetMap — imagem self-contained equivalente ao tutum/php-apache.
# Base oficial multi-arch (linux/amd64 + linux/arm64) com Apache + mod_php.
FROM php:7.4-apache

LABEL maintainer="Marcelo Matos <marcelo.matos@ufrr.br>"

# Dependências de runtime (SNMP CLI + daemon, SQLite, htpasswd) e as libs de
# desenvolvimento usadas só para compilar as extensões PHP (purgadas em seguida).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        snmp snmpd sqlite3 apache2-utils \
        libsnmp-dev libsqlite3-dev \
    && docker-php-ext-install snmp pdo_sqlite \
    && apt-get purge -y --auto-remove libsnmp-dev libsqlite3-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Mesmo error_reporting do container original (suprime notices/deprecated).
RUN { \
        echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE'; \
        echo 'disable_functions ='; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Apache: DocumentRoot em /app e AllowOverride All (rewrite e auth via .htaccess).
RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!/app!g; s!/var/www!/app!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && { \
        echo '<Directory /app>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/phpnetmap.conf \
    && a2enconf phpnetmap

# Libera a view do snmpd local (mesma intenção do Dockerfile original).
RUN sed -i 's/-V systemonly//g' /etc/snmp/snmpd.conf

# Script de startup substitui o /run.sh do tutum (sobe snmpd, gera htpasswd).
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Código da aplicação a partir do checkout (respeita o .dockerignore).
COPY . /app
WORKDIR /app

# Diretórios que precisam de escrita pelo Apache (SQLite e runtime do Yii).
RUN chmod +x /app/set_htpasswd.sh \
    && mkdir -p /app/protected/data /app/protected/runtime /app/assets \
    && chown -R www-data:www-data /app/protected/data /app/protected/runtime /app/assets

VOLUME /app/protected/data
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
