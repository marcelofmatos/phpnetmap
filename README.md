# PHPNetMap

Software para monitoramento de equipamentos de Camada 2 e Camada 3 com protocolo 
SNMP v(1 / 2c / 3). Testado com 3Com / HP, ProCurve, Dell e Extreme. 
Alguns outros modelos com suporte SNMP funcionam corretamente.
Framework [yii](http://www.yiiframework.com/) com 
[bootstrap](http://www.yiiframework.com/extension/bootstrap), 
[colorpicker](http://www.yiiframework.com/extension/colorpicker) e 
[CAdvancedArBehavior](http://www.yiiframework.com/extension/cadvancedarbehavior).
Usando biblioteca JavaScript [D3](http://d3js.org/) para o mapa.


# Requisitos

O software foi testado em servidor Debian e Ubuntu com os seguintes pacotes 
instalados:

* apache2
* libapache2-mod-php5
* php5-snmp
* php5-sqlite
* php-apc
* snmpd
* sqlite3

O login está configurado em .htaccess e .htpasswd portanto o apache deve estar 
configurado para ler as instruções


# PHPNetMap e Docker

Criei uma imagem Docker com toda a configuração pronta para uso do PHPNetMap, 
disponível no [Docker Hub](https://hub.docker.com/r/marcelofmatos/phpnetmap/). 
Com o docker-compose.yml na raiz do projeto é possível baixar a imagem e rodar o 
sistema somente com o comando `docker-compose up` dentro do diretório do 
projeto. Observe as configurações do servidor para rodar em modo de produção 
(senha em .htpasswd, allowoverride=true, etc). Altere o docker-compose.yml 
conforme necessário.

## Instalação do Docker
```
curl -sSL https://get.docker.com/ | sh
```
## Instalação do docker-compose

```
apt-get install python-pip
pip install docker-compose
```



# Referências
* http://www.yiiframework.com/
* http://d3js.org/
* https://docs.docker.com/engine/installation/
* https://docs.docker.com/compose/compose-file/
