<?php

use PHPUnit\Framework\TestCase;

class McpVlanToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM vlan WHERE name LIKE 'mcp-test-%'")->execute();
    }

    public function testCreateVlanThenGetVlanReturnsIt()
    {
        $created = McpVlanTools::createVlan(array('tag' => 999, 'name' => 'mcp-test-vlan'));
        $fetched = McpVlanTools::getVlan(array('id' => $created['id']));
        $this->assertSame(999, $fetched['tag']);
        $this->assertSame('mcp-test-vlan', $fetched['name']);
    }

    public function testCreateVlanWithoutRequiredFieldsThrows()
    {
        $this->expectException(McpToolException::class);
        McpVlanTools::createVlan(array('name' => 'mcp-test-invalid'));
    }

    public function testListVlansIncludesCreatedVlan()
    {
        $created = McpVlanTools::createVlan(array('tag' => 996, 'name' => 'mcp-test-vlan-list'));
        $ids = array_column(McpVlanTools::listVlans(array()), 'id');
        $this->assertContains($created['id'], $ids);
    }

    public function testUpdateVlanChangesDescription()
    {
        $created = McpVlanTools::createVlan(array('tag' => 998, 'name' => 'mcp-test-vlan-update'));
        $updated = McpVlanTools::updateVlan(array('id' => $created['id'], 'description' => 'updated'));
        $this->assertSame('updated', $updated['description']);
    }

    public function testDeleteVlanRemovesIt()
    {
        $created = McpVlanTools::createVlan(array('tag' => 997, 'name' => 'mcp-test-vlan-delete'));
        $result = McpVlanTools::deleteVlan(array('id' => $created['id']));
        $this->assertTrue($result['deleted']);

        $this->expectException(McpToolException::class);
        McpVlanTools::getVlan(array('id' => $created['id']));
    }

    public function testGetVlanThrowsForUnknownId()
    {
        $this->expectException(McpToolException::class);
        McpVlanTools::getVlan(array('id' => 999999));
    }

    public function testGetVlanThrowsForNonScalarId()
    {
        $this->expectException(McpToolException::class);
        McpVlanTools::getVlan(array('id' => array('unexpected' => 'shape')));
    }
}
