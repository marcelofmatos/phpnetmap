<?php

class McpToolRegistry
{
    /**
     * Tool-provider class names, each exposing a static definitions()
     * method. Appended to by each entity's tool-class task.
     * @var array
     */
    public static $classes = array();

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
            throw new McpToolException('Unknown tool: ' . $name);
        }

        $def = $definitions[$name];

        if ($def['mode'] !== 'readonly' && $mode !== 'readwrite') {
            throw new McpToolException('Server is in read-only mode');
        }

        return call_user_func($def['handler'], $arguments);
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
