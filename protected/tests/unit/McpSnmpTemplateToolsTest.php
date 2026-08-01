<?php

use PHPUnit\Framework\TestCase;

class McpSnmpTemplateToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Yii::app()->db->createCommand("DELETE FROM snmp_template WHERE name LIKE 'mcp-test-%'")->execute();
    }

    public function testCreateSnmpTemplateThenGetSnmpTemplateExcludesCredentials()
    {
        $created = McpSnmpTemplateTools::createSnmpTemplate(array(
            'name' => 'mcp-test-template',
            'version' => 'v2c',
            'community' => 'super-secret',
        ));

        $fetched = McpSnmpTemplateTools::getSnmpTemplate(array('id' => $created['id']));

        $this->assertSame('mcp-test-template', $fetched['name']);
        $this->assertArrayNotHasKey('community', $fetched);
        $this->assertArrayNotHasKey('security_name', $fetched);
        $this->assertArrayNotHasKey('auth_passphrase', $fetched);
        $this->assertArrayNotHasKey('priv_passphrase', $fetched);
    }

    public function testListSnmpTemplatesExcludesCredentials()
    {
        $created = McpSnmpTemplateTools::createSnmpTemplate(array('name' => 'mcp-test-list-template', 'community' => 'secret'));
        $list = McpSnmpTemplateTools::listSnmpTemplates(array());
        $ids = array_column($list, 'id');
        $this->assertContains($created['id'], $ids);
        foreach ($list as $item) {
            $this->assertArrayNotHasKey('community', $item);
        }
    }

    public function testUpdateSnmpTemplateChangesTimeout()
    {
        $created = McpSnmpTemplateTools::createSnmpTemplate(array('name' => 'mcp-test-update-template'));
        $updated = McpSnmpTemplateTools::updateSnmpTemplate(array('id' => $created['id'], 'timeout' => 5));
        $this->assertSame(5, $updated['timeout']);
    }

    public function testDeleteSnmpTemplateRemovesIt()
    {
        $created = McpSnmpTemplateTools::createSnmpTemplate(array('name' => 'mcp-test-delete-template'));
        $result = McpSnmpTemplateTools::deleteSnmpTemplate(array('id' => $created['id']));
        $this->assertTrue($result['deleted']);

        $this->expectException(McpToolException::class);
        McpSnmpTemplateTools::getSnmpTemplate(array('id' => $created['id']));
    }

    public function testGetSnmpTemplateThrowsForUnknownId()
    {
        $this->expectException(McpToolException::class);
        McpSnmpTemplateTools::getSnmpTemplate(array('id' => 999999));
    }

    public function testGetSnmpTemplateThrowsForNonScalarId()
    {
        $this->expectException(McpToolException::class);
        McpSnmpTemplateTools::getSnmpTemplate(array('id' => array('unexpected' => 'shape')));
    }

    public function testGetSnmpTemplateReturnsIntegerFieldsAfterFreshRoundTrip()
    {
        $created = McpSnmpTemplateTools::createSnmpTemplate(array('name' => 'mcp-test-int-check', 'timeout' => 3, 'retries' => 2));
        $fetched = McpSnmpTemplateTools::getSnmpTemplate(array('id' => $created['id']));
        $this->assertIsInt($fetched['id']);
        $this->assertIsInt($fetched['timeout']);
        $this->assertIsInt($fetched['retries']);
    }
}
