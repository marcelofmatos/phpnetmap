<?php

use PHPUnit\Framework\TestCase;

class HostArpTableTest extends TestCase
{
    private $templateId;
    private $hostId;

    protected function tearDown(): void
    {
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
        }
        if ($this->templateId) {
            Yii::app()->db->createCommand("DELETE FROM snmp_template WHERE id = {$this->templateId}")->execute();
        }
    }

    // loadArpTable() used each(), removed in PHP 8 — any host with an SNMP
    // template died with "Call to undefined function each()". No agent
    // listens on 127.0.0.1 here, so the walk returns nothing, fast.
    public function testLoadArpTableForHostWithSnmpTemplateReturnsArray()
    {
        $template = new SnmpTemplate;
        $template->name = 'test-arp-unreachable';
        $template->version = '2c';
        $template->community = 'public';
        $template->timeout = 10000;
        $template->retries = 0;
        $this->assertTrue($template->save(false));
        $this->templateId = $template->id;

        $host = new Host();
        $host->name = 'test-arp-host';
        $host->ip = '127.0.0.1';
        $host->type = Host::TYPE_UNKNOWN;
        $host->snmp_template_id = $template->id;
        $this->assertTrue($host->save(false));
        $this->hostId = $host->id;

        $this->assertSame(array(), $host->loadArpTable());
    }
}
