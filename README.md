# PHPNetMap

Software para monitoramento de equipamentos de Camada 2 e Camada 3 com protocolo SNMP v(1 / 2c / 3)
Testado com 3Com / HP, ProCurve, Dell e Extreme. Alguns outros modelos com suporte SNMP funcionam corretamente.
Framework yii com bootstrap, colorpicker e CAdvancedArBehavior.
Usando biblioteca JavaScript D3 para o mapa.


# Requisitos

O software foi testado em servidor Debian e Ubuntu com os seguintes pacotes instalados:

* apache2
* libapache2-mod-php5
* php5-snmp
* php5-sqlite
* php-apc
* snmpd
* sqlite3

O login está configurado em .htaccess e .htpasswd portanto o apache deve estar configurado para ler as instruções


# PHPNetMap e Docker

Com o docker-compose.yml na raiz do projeto é possível rodar o sistema, bastando rodar o comando 'docker-compose up' no diretório do projeto. 
Observe as configurações do servidor para rodar em modo de produção (senha em .htpasswd, allowoverride=true, etc)
Altere o docker-compose.yml conforme necessário

## Instalação do Docker
* curl -sSL https://get.docker.com/ | sh



# Referências
* https://docs.docker.com/engine/installation/
