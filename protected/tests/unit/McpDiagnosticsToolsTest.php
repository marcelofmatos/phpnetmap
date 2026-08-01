<?php

use PHPUnit\Framework\TestCase;

class McpDiagnosticsToolsTest extends TestCase
{
    private $hostId;

    protected function setUp(): void
    {
        // type 'unknown' (not TYPE_SWITCH) with no snmp_template_id set —
        // confirmed against protected/models/Host.php: loadCamTable() early-
        // returns array() when type != TYPE_SWITCH && no template;
        // loadArpTable() early-returns array() whenever there's no
        // template, regardless of type; loadPortsInfo() has no such guard,
        // but PNMSnmp::walk() itself falls through to `return array()`
        // whenever $host->snmpTemplate isn't a real SnmpTemplate instance.
        // So all three are safe, empty-result, no-SNMP-attempted calls here
        // — enough to prove the tool wraps the Host model methods
        // correctly and shapes their output, without needing a reachable
        // SNMP device in the test environment.
        $created = McpHostTools::createHost(array('name' => 'mcp-test-diag-host', 'type' => Host::TYPE_UNKNOWN));
        $this->hostId = $created['id'];
    }

    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
    }

    public function testGetCamTableReturnsArrayForHostWithoutSnmp()
    {
        $result = McpDiagnosticsTools::getCamTable(array('host_id' => $this->hostId));
        $this->assertIsArray($result);
    }

    public function testGetArpTableReturnsArrayForHostWithoutSnmp()
    {
        $result = McpDiagnosticsTools::getArpTable(array('host_id' => $this->hostId));
        $this->assertIsArray($result);
    }

    public function testListPortsReturnsArrayForHostWithoutSnmp()
    {
        $result = McpDiagnosticsTools::listPorts(array('host_id' => $this->hostId));
        $this->assertIsArray($result);
    }

    public function testGetCamTableThrowsForUnknownHost()
    {
        $this->expectException(McpToolException::class);
        McpDiagnosticsTools::getCamTable(array('host_id' => 999999));
    }

    public function testGetCamTableThrowsForNonScalarHostId()
    {
        $this->expectException(McpToolException::class);
        McpDiagnosticsTools::getCamTable(array('host_id' => array('unexpected' => 'shape')));
    }

    public function testGetCamTableThrowsToolExceptionForUnsupportedSnmpVersion()
    {
        $template = new SnmpTemplate;
        $template->name = 'mcp-test-diag-badversion';
        $template->version = '9';
        $template->save();

        $host = McpHostTools::createHost(array(
            'name' => 'mcp-test-diag-badversion-host',
            'type' => Host::TYPE_SWITCH,
            'snmp_template_id' => $template->id,
        ));

        try {
            $this->expectException(McpToolException::class);
            McpDiagnosticsTools::getCamTable(array('host_id' => $host['id']));
        } finally {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$host['id']}")->execute();
            Yii::app()->db->createCommand("DELETE FROM snmp_template WHERE id = {$template->id}")->execute();
        }
    }
}
