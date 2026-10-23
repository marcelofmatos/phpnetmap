<?php

use PHPUnit\Framework\TestCase;

class HostControllerTest extends TestCase
{
    private $hostId;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($_GET['id']);
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
        }
    }

    // An empty ARP table left $arp_table undefined: a notice on PHP 7, a
    // warning (HTTP 500 on host/arpTable) on PHP 8.
    public function testArpTableWithEmptyArpTableRendersWithoutWarning()
    {
        $host = new Host();
        $host->name = 'arp-empty-test';
        $host->type = Host::TYPE_UNKNOWN;
        $this->assertTrue($host->save(false));
        $this->hostId = $host->id;
        $_GET['id'] = $host->id;

        $controller = new class('host') extends HostController {
            public $rendered;

            public function render($view, $data = null, $return = false)
            {
                $this->rendered = array($view, $data);
            }
        };
        $controller->actionArpTable();

        $this->assertSame('arpTable', $controller->rendered[0]);
        $this->assertSame(array(), $controller->rendered[1]['arp_table']);
    }
}
