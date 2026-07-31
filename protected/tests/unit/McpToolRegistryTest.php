<?php

use PHPUnit\Framework\TestCase;

class McpRegistryFakeReadonlyTool
{
    public static function definitions()
    {
        return array(
            'fake_ping' => array(
                'mode' => 'readonly',
                'description' => 'Fake readonly tool for tests',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'ping'),
            ),
        );
    }

    public static function ping($arguments)
    {
        return array('pong' => true);
    }
}

class McpRegistryFakeReadwriteTool
{
    public static function definitions()
    {
        return array(
            'fake_write' => array(
                'mode' => 'readwrite',
                'description' => 'Fake readwrite tool for tests',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'write'),
            ),
        );
    }

    public static function write($arguments)
    {
        return array('written' => true);
    }
}

class McpToolRegistryTest extends TestCase
{
    private $originalClasses;

    protected function setUp(): void
    {
        $this->originalClasses = McpToolRegistry::$classes;
        McpToolRegistry::$classes = array('McpRegistryFakeReadonlyTool', 'McpRegistryFakeReadwriteTool');
    }

    protected function tearDown(): void
    {
        McpToolRegistry::$classes = $this->originalClasses;
    }

    public function testListToolsIncludesBothToolsInReadwriteMode()
    {
        $names = array_column(McpToolRegistry::listTools('readwrite')['tools'], 'name');
        $this->assertContains('fake_ping', $names);
        $this->assertContains('fake_write', $names);
    }

    public function testListToolsHidesWriteToolsInReadonlyMode()
    {
        $names = array_column(McpToolRegistry::listTools('readonly')['tools'], 'name');
        $this->assertContains('fake_ping', $names);
        $this->assertNotContains('fake_write', $names);
    }

    public function testCallDispatchesToTheMatchingReadonlyTool()
    {
        $result = McpToolRegistry::call('fake_ping', array(), 'readonly', null);
        $this->assertSame(array('pong' => true), $result);
    }

    public function testCallRejectsWriteToolInReadonlyMode()
    {
        $this->expectException(McpToolException::class);
        McpToolRegistry::call('fake_write', array(), 'readonly', null);
    }

    public function testCallThrowsForUnknownTool()
    {
        $this->expectException(McpToolException::class);
        McpToolRegistry::call('does_not_exist', array(), 'readwrite', null);
    }
}
