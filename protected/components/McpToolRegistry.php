<?php

class McpToolRegistry
{
    /**
     * Tool-provider class names, each exposing a static definitions()
     * method. Appended to by each entity's tool-class task.
     * @var array
     */
    public static $classes = array('McpHostTools', 'McpConnectionTools', 'McpVlanTools', 'McpSnmpTemplateTools', 'McpDiagnosticsTools');

    public static function listTools($mode)
    {
        $tools = array();
        foreach (self::allDefinitions() as $name => $def) {
            if ($def['mode'] !== 'readonly' && $mode !== 'readwrite') {
                continue;
            }
            $tools[] = array(
                'name' => $name,
                'description' => $def['description'],
                'inputSchema' => $def['inputSchema'],
            );
        }
        return array('tools' => $tools);
    }

    public static function call($name, $arguments, $mode, $token)
    {
        $definitions = self::allDefinitions();

        if (!isset($definitions[$name])) {
            throw new McpUnknownToolException('Unknown tool: ' . $name);
        }

        $def = $definitions[$name];

        if ($def['mode'] !== 'readonly' && $mode !== 'readwrite') {
            throw new McpToolException('Server is in read-only mode');
        }

        $result = call_user_func($def['handler'], $arguments);

        if ($def['mode'] !== 'readonly') {
            self::recordAudit($token, $name, $arguments);
        }

        return $result;
    }

    private static function recordAudit($token, $toolName, $arguments)
    {
        try {
            Yii::app()->db->createCommand()->insert('mcp_audit_log', array(
                'mcp_token_id' => $token->id,
                'tool_name' => $toolName,
                'params_json' => json_encode(self::redactSensitiveFields($arguments)),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        } catch (Exception $e) {
            Yii::log('Failed to record MCP audit log entry: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.mcp');
        }
    }

    /**
     * Strips credential fields before they're persisted to the audit log —
     * mirrors the exclusion already applied to SnmpTemplate read output
     * (McpSnmpTemplateTools::toArray()), since audit rows are append-only
     * and outlive credential rotation.
     */
    private static function redactSensitiveFields($arguments)
    {
        $sensitiveKeys = array('community', 'security_name', 'auth_passphrase', 'priv_passphrase');
        foreach ($sensitiveKeys as $key) {
            if (isset($arguments[$key])) {
                $arguments[$key] = '[REDACTED]';
            }
        }
        return $arguments;
    }

    private static function allDefinitions()
    {
        $all = array();
        foreach (self::$classes as $class) {
            $all = array_merge($all, call_user_func(array($class, 'definitions')));
        }
        return $all;
    }
}
