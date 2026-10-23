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
}
