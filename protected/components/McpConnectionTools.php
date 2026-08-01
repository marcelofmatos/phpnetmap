<?php

class McpConnectionTools
{
    use McpToolHelperTrait;

    public static function definitions()
    {
        $fieldsSchema = array(
            'host_src_id' => array('type' => 'integer'),
            'host_src_port' => array('type' => 'integer'),
            'host_dst_id' => array('type' => 'integer'),
            'host_dst_port' => array('type' => 'integer'),
            'type' => array('type' => 'string'),
        );

        return array(
            'list_connections' => array(
                'mode' => 'readonly',
                'description' => 'List all connections',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'listConnections'),
            ),
            'get_connection' => array(
                'mode' => 'readonly',
                'description' => 'Get a single connection by id',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'getConnection'),
            ),
            'create_connection' => array(
                'mode' => 'readwrite',
                'description' => 'Create a new connection between two host ports',
                'inputSchema' => array('type' => 'object', 'properties' => $fieldsSchema, 'required' => array('host_src_id', 'host_src_port', 'host_dst_id', 'host_dst_port')),
                'handler' => array(__CLASS__, 'createConnection'),
            ),
            'update_connection' => array(
                'mode' => 'readwrite',
                'description' => 'Update an existing connection',
                'inputSchema' => array('type' => 'object', 'properties' => array_merge(array('id' => array('type' => 'integer')), $fieldsSchema), 'required' => array('id')),
                'handler' => array(__CLASS__, 'updateConnection'),
            ),
            'delete_connection' => array(
                'mode' => 'readwrite',
                'description' => 'Delete a connection',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'deleteConnection'),
            ),
        );
    }

    public static function listConnections($arguments)
    {
        $result = array();
        foreach (Connection::model()->findAll() as $connection) {
            $result[] = self::toArray($connection);
        }
        return $result;
    }

    public static function getConnection($arguments)
    {
        return self::toArray(self::loadOr404($arguments));
    }

    public static function createConnection($arguments)
    {
        $connection = new Connection;
        $connection->attributes = $arguments;
        if (!$connection->save()) {
            throw new McpToolException(self::errorsToMessage($connection));
        }
        return self::toArray($connection);
    }

    public static function updateConnection($arguments)
    {
        $connection = self::loadOr404($arguments);
        $connection->attributes = $arguments;
        if (!$connection->save()) {
            throw new McpToolException(self::errorsToMessage($connection));
        }
        return self::toArray($connection);
    }

    public static function deleteConnection($arguments)
    {
        $connection = self::loadOr404($arguments);
        $connection->delete();
        return self::castIntFields(array('deleted' => true, 'id' => $connection->id), array('id'));
    }

    private static function loadOr404($arguments)
    {
        $id = self::extractId($arguments);
        $connection = $id ? Connection::model()->findByPk($id) : null;
        if ($connection === null) {
            throw new McpToolException('Connection not found: ' . $id);
        }
        return $connection;
    }

    private static function toArray($connection)
    {
        return self::castIntFields(array(
            'id' => $connection->id,
            'host_src_id' => $connection->host_src_id,
            'host_src_port' => $connection->host_src_port,
            'host_dst_id' => $connection->host_dst_id,
            'host_dst_port' => $connection->host_dst_port,
            'type' => $connection->type,
        ), array('id', 'host_src_id', 'host_src_port', 'host_dst_id', 'host_dst_port'));
    }
}
