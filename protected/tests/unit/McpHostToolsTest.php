<?php

use PHPUnit\Framework\TestCase;

class McpHostToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM host WHERE name LIKE 'mcp-test-%'")->execute();
    }

    public function testCreateHostThenGetHostReturnsIt()
    {
        $created = McpHostTools::createHost(array('name' => 'mcp-test-host', 'type' => 'switch'));
        $this->assertArrayHasKey('id', $created);

        $fetched = McpHostTools::getHost(array('id' => $created['id']));
        $this->assertSame('mcp-test-host', $fetched['name']);
        $this->assertSame('switch', $fetched['type']);
    }

    public function testCreateHostWithoutRequiredTypeThrows()
    {
        $this->expectException(McpToolException::class);
        McpHostTools::createHost(array('name' => 'mcp-test-invalid'));
    }

    public function testListHostsIncludesCreatedHost()
    {
        McpHostTools::createHost(array('name' => 'mcp-test-list', 'type' => 'switch'));
        $names = array_column(McpHostTools::listHosts(array()), 'name');
        $this->assertContains('mcp-test-list', $names);
    }

    public function testUpdateHostChangesFields()
    {
        $created = McpHostTools::createHost(array('name' => 'mcp-test-update', 'type' => 'switch'));
        $updated = McpHostTools::updateHost(array('id' => $created['id'], 'type' => 'router'));
        $this->assertSame('router', $updated['type']);
    }

    public function testDeleteHostRemovesIt()
    {
        $created = McpHostTools::createHost(array('name' => 'mcp-test-delete', 'type' => 'switch'));
        $result = McpHostTools::deleteHost(array('id' => $created['id']));
        $this->assertTrue($result['deleted']);

        $this->expectException(McpToolException::class);
        McpHostTools::getHost(array('id' => $created['id']));
    }

    public function testGetHostThrowsForUnknownId()
    {
        $this->expectException(McpToolException::class);
        McpHostTools::getHost(array('id' => 999999));
    }

    public function testGetHostThrowsForNonScalarId()
    {
        $this->expectException(McpToolException::class);
        McpHostTools::getHost(array('id' => array('unexpected' => 'shape')));
    }

    public function testDeleteHostThrowsForNonScalarId()
    {
        $this->expectException(McpToolException::class);
        McpHostTools::deleteHost(array('id' => array('unexpected' => 'shape')));
    }
}
