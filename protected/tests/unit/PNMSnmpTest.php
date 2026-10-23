<?php

use PHPUnit\Framework\TestCase;

/**
 * PHP 8 promoted "undefined variable" / "undefined array key" from E_NOTICE
 * (silenced by custom.ini) to E_WARNING, which reaches the Apache log and,
 * with display_errors on, the response body. These calls must not raise any.
 */
class PNMSnmpTest extends TestCase
{
    private $host;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        // No snmp_template_id: walk()/get()/set() never reach the network.
        $this->host = new Host();
        $this->host->ip = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testWalkWithoutSnmpTemplateReturnsEmptyArray()
    {
        $this->assertSame(array(), PNMSnmp::walk($this->host, PNMSnmp::getOid('ifDescr')));
    }

    public function testGetWithoutSnmpTemplateReturnsNull()
    {
        $this->assertNull(PNMSnmp::get($this->host, PNMSnmp::getOid('sysName')));
    }

    public function testSetWithoutSnmpTemplateReturnsNull()
    {
        $this->assertNull(PNMSnmp::set($this->host, PNMSnmp::getOid('ifAlias') . '.1', 's', 'x'));
    }

    public function testGetOidReturnsRawOidForUnknownKey()
    {
        $this->assertSame('.1.3.6.1.2.1.1.3.0', PNMSnmp::getOid('.1.3.6.1.2.1.1.3.0'));
    }

    public function testGetOidResolvesKnownKey()
    {
        $this->assertSame('.1.3.6.1.2.1.2.2.1.2', PNMSnmp::getOid('ifDescr'));
    }
}
