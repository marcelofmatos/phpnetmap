# PHPNetMap

Software para monitoramento de equipamentos de rede com protocolo 
SNMP v(1 / 2c / 3). Testado com 3Com / HP, ProCurve, Dell e Extreme. 
Alguns outros modelos com suporte SNMP funcionam corretamente.
Framework [yii](http://www.yiiframework.com/) com 
[bootstrap](http://www.yiiframework.com/extension/bootstrap), 
[colorpicker](http://www.yiiframework.com/extension/colorpicker) e 
[CAdvancedArBehavior](http://www.yiiframework.com/extension/cadvancedarbehavior).
Usando biblioteca JavaScript [D3](http://d3js.org/) para o mapa.


## Como funciona

O PHPNetMap mostra o mapeamento dos host conectados nos equipamentos a partir das 
informações do [FIB](https://en.wikipedia.org/wiki/Forwarding_information_base) 
ou tabela CAM dos switches, e da tabela [ARP](https://en.wikipedia.org/wiki/Address_Resolution_Protocol) 
dos equipamentos. É possível executar buscas dentro dessas tabelas em vários 
equipamentos ao mesmo tempo. Com a visualização do mapa é possível verificar 
as conexões entre os hosts e para cada host há uma tela indicando o status das
portas com seu respectivo host conectado. É possível ver o status de operação 
da porta e status do [Protocolo Spanning Tree](https://en.wikipedia.org/wiki/Spanning_Tree_Protocol) 
em switches com o OID dot1dStpPortState. Usando uma community SNMP com permissão
de escrita é possível alterar o ifAdminStatus ou preencher o ifAlias.


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


# Servidor

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


# Screenshots

![Home Screenshot](https://raw.githubusercontent.com/marcelofmatos/phpnetmap/master/images/screenshot_home.png)
![Host Screenshot](https://raw.githubusercontent.com/marcelofmatos/phpnetmap/master/images/screenshot_host.png)



# Referências
* http://www.yiiframework.com/
* http://d3js.org/
* https://docs.docker.com/engine/installation/
* https://docs.docker.com/compose/compose-file/
