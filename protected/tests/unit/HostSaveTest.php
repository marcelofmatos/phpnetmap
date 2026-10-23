<?php

use PHPUnit\Framework\TestCase;

class HostSaveTest extends TestCase
{
    private $hostId;

    protected function tearDown(): void
    {
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
        }
    }

    // PHP 8.1 deprecates passing null to preg_match()/strtolower(); a host
    // saved without a MAC must not trigger it.
    public function testSaveWithoutMacRaisesNoDeprecation()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        try {
            $host = new Host();
            $host->name = 'host-save-test-no-mac';
            $host->type = Host::TYPE_UNKNOWN;
            $this->assertTrue($host->save(false));
            $this->hostId = $host->id;
        } finally {
            restore_error_handler();
        }
    }

    public function testSaveNormalizesCiscoStyleMac()
    {
        $host = new Host();
        $host->name = 'host-save-test-mac';
        $host->type = Host::TYPE_UNKNOWN;
        $host->mac = '00AA-BBCC-DDEE';
        $this->assertTrue($host->save(false));
        $this->hostId = $host->id;
        $this->assertSame('00:aa:bb:cc:dd:ee', $host->mac);
    }
}
