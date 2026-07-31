<?php

use PHPUnit\Framework\TestCase;

class McpControllerTest extends TestCase
{
    private $originalClasses;

    protected function setUp(): void
    {
        $this->originalClasses = McpToolRegistry::$classes;
        McpToolRegistry::$classes = array();
    }

    protected function tearDown(): void
    {
        McpToolRegistry::$classes = $this->originalClasses;
    }

    private function config($enabled, $mode)
    {
        $config = new ConfigForm;
        $config->mcpEnabled = $enabled;
        $config->mcpMode = $mode;
        return $config;
    }

    public function testParseRequestReturnsNullForMalformedJson()
    {
        $this->assertNull(McpJsonRpc::parseRequest('not json'));
    }

    public function testParseRequestReturnsNullWhenMethodMissing()
    {
        $this->assertNull(McpJsonRpc::parseRequest(json_encode(array('id' => 1))));
    }

    public function testParseRequestReturnsStructuredRequest()
    {
        $parsed = McpJsonRpc::parseRequest(json_encode(array('id' => 7, 'method' => 'initialize', 'params' => array('a' => 1))));
        $this->assertSame(7, $parsed['id']);
        $this->assertSame('initialize', $parsed['method']);
        $this->assertSame(array('a' => 1), $parsed['params']);
    }

    public function testDispatchHandlesInitialize()
    {
        $controller = new McpController('mcp');
        $response = $controller->dispatch(array('id' => 1, 'method' => 'initialize', 'params' => array()), $this->config(true, 'readonly'), null);

        $this->assertSame('2.0', $response['jsonrpc']);
        $this->assertSame('phpnetmap', $response['result']['serverInfo']['name']);
    }

    public function testDispatchHandlesToolsListFilteredByMode()
    {
        $controller = new McpController('mcp');
        $response = $controller->dispatch(array('id' => 2, 'method' => 'tools/list', 'params' => array()), $this->config(true, 'readonly'), null);

        $this->assertSame(array(), $response['result']['tools']);
    }

    public function testDispatchReturnsMethodNotFoundForUnknownMethod()
    {
        $controller = new McpController('mcp');
        $response = $controller->dispatch(array('id' => 3, 'method' => 'bogus/method', 'params' => array()), $this->config(true, 'readonly'), null);

        $this->assertSame(-32601, $response['error']['code']);
    }
}
