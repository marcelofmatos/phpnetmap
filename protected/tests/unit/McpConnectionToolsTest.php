<?php

use PHPUnit\Framework\TestCase;

class McpConnectionToolsTest extends TestCase
{
    private $hostAId;
    private $hostBId;

    protected function setUp(): void
    {
        $hostA = McpHostTools::createHost(array('name' => 'mcp-test-conn-a', 'type' => 'switch'));
        $hostB = McpHostTools::createHost(array('name' => 'mcp-test-conn-b', 'type' => 'switch'));
        $this->hostAId = $hostA['id'];
        $this->hostBId = $hostB['id'];
    }

    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM connection WHERE host_src_id = {$this->hostAId} OR host_dst_id = {$this->hostAId}")->execute();
        Yii::app()->db->createCommand("DELETE FROM host WHERE name LIKE 'mcp-test-conn-%'")->execute();
    }

    public function testCreateConnectionThenGetConnectionReturnsIt()
    {
        $created = McpConnectionTools::createConnection(array(
            'host_src_id' => $this->hostAId, 'host_src_port' => 1,
            'host_dst_id' => $this->hostBId, 'host_dst_port' => 2,
        ));

        $fetched = McpConnectionTools::getConnection(array('id' => $created['id']));
        $this->assertSame($this->hostAId, $fetched['host_src_id']);
        $this->assertSame($this->hostBId, $fetched['host_dst_id']);
    }

    public function testCreateConnectionWithoutRequiredFieldsThrows()
    {
        $this->expectException(McpToolException::class);
        McpConnectionTools::createConnection(array('host_src_id' => $this->hostAId));
    }

    public function testUpdateConnectionChangesPort()
    {
        $created = McpConnectionTools::createConnection(array(
            'host_src_id' => $this->hostAId, 'host_src_port' => 1,
            'host_dst_id' => $this->hostBId, 'host_dst_port' => 2,
        ));
        $updated = McpConnectionTools::updateConnection(array('id' => $created['id'], 'host_dst_port' => 5));
        $this->assertSame(5, $updated['host_dst_port']);
    }

    public function testDeleteConnectionRemovesIt()
    {
        $created = McpConnectionTools::createConnection(array(
            'host_src_id' => $this->hostAId, 'host_src_port' => 1,
            'host_dst_id' => $this->hostBId, 'host_dst_port' => 2,
        ));
        $result = McpConnectionTools::deleteConnection(array('id' => $created['id']));
        $this->assertTrue($result['deleted']);

        $this->expectException(McpToolException::class);
        McpConnectionTools::getConnection(array('id' => $created['id']));
    }

    public function testGetConnectionThrowsForNonScalarId()
    {
        $this->expectException(McpToolException::class);
        McpConnectionTools::getConnection(array('id' => array('unexpected' => 'shape')));
    }

    public function testListConnectionsIncludesCreatedConnection()
    {
        $created = McpConnectionTools::createConnection(array(
            'host_src_id' => $this->hostAId, 'host_src_port' => 1,
            'host_dst_id' => $this->hostBId, 'host_dst_port' => 2,
        ));
        $ids = array_column(McpConnectionTools::listConnections(array()), 'id');
        $this->assertContains($created['id'], $ids);
    }

    public function testGetConnectionThrowsForUnknownId()
    {
        $this->expectException(McpToolException::class);
        McpConnectionTools::getConnection(array('id' => 999999));
    }

    public function testGetConnectionReturnsIntegerFieldsAfterFreshRoundTrip()
    {
        $created = McpConnectionTools::createConnection(array(
            'host_src_id' => $this->hostAId, 'host_src_port' => 1,
            'host_dst_id' => $this->hostBId, 'host_dst_port' => 2,
        ));
        $fetched = McpConnectionTools::getConnection(array('id' => $created['id']));
        $this->assertIsInt($fetched['id']);
        $this->assertIsInt($fetched['host_src_id']);
        $this->assertIsInt($fetched['host_src_port']);
        $this->assertIsInt($fetched['host_dst_id']);
        $this->assertIsInt($fetched['host_dst_port']);
    }
}
