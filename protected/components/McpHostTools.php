<?php

class McpHostTools
{
    use McpToolHelperTrait;

    public static function definitions()
    {
        $fieldsSchema = array(
            'name' => array('type' => 'string'),
            'type' => array('type' => 'string'),
            'ip' => array('type' => 'string'),
            'mac' => array('type' => 'string'),
            'snmp_template_id' => array('type' => 'integer'),
            'host_face_id' => array('type' => 'integer'),
        );

        return array(
            'list_hosts' => array(
                'mode' => 'readonly',
                'description' => 'List all hosts',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'listHosts'),
            ),
            'get_host' => array(
                'mode' => 'readonly',
                'description' => 'Get a single host by id',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'getHost'),
            ),
            'create_host' => array(
                'mode' => 'readwrite',
                'description' => 'Create a new host',
                'inputSchema' => array('type' => 'object', 'properties' => $fieldsSchema, 'required' => array('type')),
                'handler' => array(__CLASS__, 'createHost'),
            ),
            'update_host' => array(
                'mode' => 'readwrite',
                'description' => 'Update an existing host',
                'inputSchema' => array('type' => 'object', 'properties' => array_merge(array('id' => array('type' => 'integer')), $fieldsSchema), 'required' => array('id')),
                'handler' => array(__CLASS__, 'updateHost'),
            ),
            'delete_host' => array(
                'mode' => 'readwrite',
                'description' => 'Delete a host',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'deleteHost'),
            ),
        );
    }

    public static function listHosts($arguments)
    {
        $result = array();
        foreach (Host::model()->findAll() as $host) {
            $result[] = self::toArray($host);
        }
        return $result;
    }

    public static function getHost($arguments)
    {
        return self::toArray(self::loadOr404($arguments));
    }

    public static function createHost($arguments)
    {
        $host = new Host;
        $host->attributes = $arguments;
        if (!$host->save()) {
            throw new McpToolException(self::errorsToMessage($host));
        }
        return self::toArray($host);
    }

    public static function updateHost($arguments)
    {
        $host = self::loadOr404($arguments);
        $host->attributes = $arguments;
        if (!$host->save()) {
            throw new McpToolException(self::errorsToMessage($host));
        }
        return self::toArray($host);
    }

    public static function deleteHost($arguments)
    {
        $host = self::loadOr404($arguments);
        $host->delete();
        return array('deleted' => true, 'id' => $host->id);
    }

    private static function loadOr404($arguments)
    {
        $id = self::extractId($arguments);
        $host = $id ? Host::model()->findByPk($id) : null;
        if ($host === null) {
            throw new McpToolException('Host not found: ' . $id);
        }
        return $host;
    }

    private static function toArray($host)
    {
        return array(
            'id' => $host->id,
            'name' => $host->name,
            'type' => $host->type,
            'ip' => $host->ip,
            'mac' => $host->mac,
            'snmp_template_id' => $host->snmp_template_id,
            'host_face_id' => $host->host_face_id,
        );
    }
}
