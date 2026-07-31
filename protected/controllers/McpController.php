<?php

class McpController extends Controller
{
    public $layout = false;

    public function filters()
    {
        return array();
    }

    public function accessRules()
    {
        return array(
            array('allow', 'users' => array('*')),
        );
    }

    public function actionIndex()
    {
        header('Content-Type: application/json');

        $config = new ConfigForm;
        $config->load();

        if (!$config->mcpEnabled) {
            header('HTTP/1.1 404 Not Found');
            Yii::app()->end();
        }

        $token = $this->resolveToken();
        if ($token === null) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Bearer');
            echo json_encode(array('error' => 'Invalid or missing bearer token'));
            Yii::app()->end();
        }
        $token->touchLastUsed();

        $rpcRequest = McpJsonRpc::parseRequest(file_get_contents('php://input'));
        if ($rpcRequest === null) {
            echo json_encode(McpJsonRpc::error(null, -32600, 'Invalid Request'));
            Yii::app()->end();
        }

        echo json_encode($this->dispatch($rpcRequest, $config, $token));
        Yii::app()->end();
    }

    /**
     * Pure dispatch, split out from actionIndex() so it's testable without
     * a real HTTP request (php://input can't be faked from PHPUnit).
     */
    public function dispatch($rpcRequest, $config, $token)
    {
        $id = $rpcRequest['id'];

        switch ($rpcRequest['method']) {
            case 'initialize':
                return McpJsonRpc::result($id, $this->buildInitializeResult());
            case 'tools/list':
                return McpJsonRpc::result($id, McpToolRegistry::listTools($config->mcpMode));
            case 'tools/call':
                return McpJsonRpc::result($id, $this->callTool($rpcRequest['params'], $config, $token));
            default:
                return McpJsonRpc::error($id, -32601, 'Method not found');
        }
    }

    public function buildInitializeResult()
    {
        $versionFile = Yii::app()->basePath . '/../VERSION';
        $version = is_file($versionFile) ? trim(file_get_contents($versionFile)) : 'development';

        return array(
            'protocolVersion' => '2025-06-18',
            'serverInfo' => array('name' => 'phpnetmap', 'version' => $version),
            'capabilities' => array('tools' => new stdClass()),
        );
    }

    private function callTool($params, $config, $token)
    {
        $toolName = isset($params['name']) ? $params['name'] : null;
        $arguments = isset($params['arguments']) ? $params['arguments'] : array();

        try {
            $result = McpToolRegistry::call($toolName, $arguments, $config->mcpMode, $token);
            return array('content' => array(array('type' => 'text', 'text' => json_encode($result))));
        } catch (McpToolException $e) {
            return array(
                'isError' => true,
                'content' => array(array('type' => 'text', 'text' => $e->getMessage())),
            );
        }
    }

    private function resolveToken()
    {
        $header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (stripos($header, 'Bearer ') !== 0) {
            return null;
        }
        return McpToken::findValidByRawToken(substr($header, 7));
    }
}
