# PHPNetMap

[README.pt-BR](https://github.com/marcelofmatos/phpnetmap/blob/master/README.pt-BR.md)

PHP Software for network equipment monitoring with protocol
SNMP v(1/2c/3). Tested with 3Com/HP, ProCurve, Dell and Extreme devices.
Some other models with SNMP support work properly.
Framework [yii](http://www.yiiframework.com/) with
[Bootstrap](http://www.yiiframework.com/extension/bootstrap)
[Colorpicker](http://www.yiiframework.com/extension/colorpicker) and
[CAdvancedArBehavior](http://www.yiiframework.com/extension/cadvancedarbehavior).
Using JavaScript [D3](http://d3js.org/) library  to the map.


## How It works

The PHPNetMap shows connected hosts based on the [FIB](https://en.wikipedia.org/wiki/Forwarding_information_base)
or table CAM switches, and [ARP](https://en.wikipedia.org/wiki/Address_Resolution_Protocol) table 
devices. You can perform searches within these tables in various
devices with the Search form. With the map view you can check the connections 
between hosts and each host there is a screen indicating the  port status with 
their respective connected host. You can see the operating status port and 
status of the [Spanning Tree Protocol](https://en.wikipedia.org/wiki/Spanning_Tree_Protocol) 
on switches with dot1dStpPortState OID. Using an SNMP community with read/write 
permission you can change the ifAdminStatus or set ifAlias


# PHPNetMap and Docker

I created a Docker image with all the configuration ready for use PHPNetMap,
available in [Docker Hub](https://hub.docker.com/r/marcelofmatos/phpnetmap/).
With the docker-compose.yml in the project root you can download the image and run
system only with the command `docker-compose up` within the directory
project. Note server settings to run in production mode (Password in .htpasswd, 
AllowOverride=true, and so on). Change the docker-compose.yml as necessary.

## Installing Docker
```
curl -ssl https://get.docker.com/ | sh
```

## Installing the docker-compose

```
apt-get install python-pip
pip install docker-compose
```


# Server

The software was tested on Debian and Ubuntu Server with the following 
installed packages:

* apache2
* libapache2-mod-php5
* php5-snmp
* php5-sqlite
* php-apc
* snmpd
* sqlite3

The login is set in .htaccess and .htpasswd so apache must be configured to 
read the instructions


# Screenshots

## Main page
![Home Screenshot](https://raw.githubusercontent.com/marcelofmatos/phpnetmap/master/images/screenshot_home.png)

## Host and network map
![Host Screenshot](https://raw.githubusercontent.com/marcelofmatos/phpnetmap/master/images/screenshot_host.png)

## Traffic ports
![Traffic Screenshot](https://raw.githubusercontent.com/marcelofmatos/phpnetmap/master/images/screenshot_traffic.png)


# References
* http://www.yiiframework.com/
* http://d3js.org/
* https://docs.docker.com/engine/installation/
* https://docs.docker.com/compose/compose-file/
