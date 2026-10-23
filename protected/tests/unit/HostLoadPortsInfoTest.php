<?php

use PHPUnit\Framework\TestCase;

class HostLoadPortsInfoTest extends TestCase
{
    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    // Port status/traffic endpoints load only status/counter keys, so ports
    // have no ifDescr — reading it was a notice on PHP 7, a warning (HTTP
    // 500 on loadPortStatus/loadPortTraffic) on PHP 8.
    public function testPortsWithoutIfDescrAreKeptWithoutWarning()
    {
        $host = new Host();
        $host->ports = array(
            1 => array('ifIndex' => 1, 'ifOperStatus' => 1),
            2 => array('ifIndex' => 2, 'ifDescr' => 'Vlan1'),
        );

        $ports = $host->loadPortsInfo(array());

        $this->assertSame(array(1 => array('ifIndex' => 1, 'ifOperStatus' => 1)), $ports);
    }

    // An empty value (ifAlias "") doesn't match the value regex: reading the
    // match groups was a notice on PHP 7 (value stayed null), a warning (HTTP
    // 500 on loadPortInfo) on PHP 8. Walk results come from a seeded APCu
    // cache, since no SNMP agent runs here.
    public function testEmptySnmpValueParsesToNull()
    {
        $previousCache = Yii::app()->params['cache'];
        Yii::app()->params['cache'] = true;
        apcu_clear_cache();
        try {
            $host = new Host();
            $host->ip = '127.0.0.68';
            apcu_store('PNM_' . str_replace('.', '_', $host->ip . '.1.3.6.1.2.1.31.1.1.1.18'), array(
                '.1.3.6.1.2.1.31.1.1.1.18.1' => '""',
                '.1.3.6.1.2.1.31.1.1.1.18.2' => 'STRING: "uplink"',
            ), 60);

            $ports = $host->loadPortsInfo(array('ifAlias'));

            $this->assertNull($ports[1]['ifAlias']);
            $this->assertSame('uplink', $ports[2]['ifAlias']);
        } finally {
            apcu_clear_cache();
            if ($previousCache === null) {
                Yii::app()->params->remove('cache');
            } else {
                Yii::app()->params['cache'] = $previousCache;
            }
        }
    }
}
