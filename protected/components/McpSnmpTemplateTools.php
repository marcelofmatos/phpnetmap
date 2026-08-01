<?php

class McpSnmpTemplateTools
{
    use McpToolHelperTrait;

    public static function definitions()
    {
        $writeFieldsSchema = array(
            'name' => array('type' => 'string'),
            'version' => array('type' => 'string'),
            'community' => array('type' => 'string'),
            'security_name' => array('type' => 'string'),
            'security_level' => array('type' => 'string'),
            'auth_protocol' => array('type' => 'string'),
            'auth_passphrase' => array('type' => 'string'),
            'priv_protocol' => array('type' => 'string'),
            'priv_passphrase' => array('type' => 'string'),
            'timeout' => array('type' => 'integer'),
            'retries' => array('type' => 'integer'),
        );

        return array(
            'list_snmp_templates' => array(
                'mode' => 'readonly',
                'description' => 'List all SNMP templates (credentials excluded)',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'listSnmpTemplates'),
            ),
            'get_snmp_template' => array(
                'mode' => 'readonly',
                'description' => 'Get a single SNMP template by id (credentials excluded)',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'getSnmpTemplate'),
            ),
            'create_snmp_template' => array(
                'mode' => 'readwrite',
                'description' => 'Create a new SNMP template',
                'inputSchema' => array('type' => 'object', 'properties' => $writeFieldsSchema, 'required' => array('name')),
                'handler' => array(__CLASS__, 'createSnmpTemplate'),
            ),
            'update_snmp_template' => array(
                'mode' => 'readwrite',
                'description' => 'Update an existing SNMP template',
                'inputSchema' => array('type' => 'object', 'properties' => array_merge(array('id' => array('type' => 'integer')), $writeFieldsSchema), 'required' => array('id')),
                'handler' => array(__CLASS__, 'updateSnmpTemplate'),
            ),
            'delete_snmp_template' => array(
                'mode' => 'readwrite',
                'description' => 'Delete an SNMP template',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'deleteSnmpTemplate'),
            ),
        );
    }

    public static function listSnmpTemplates($arguments)
    {
        $result = array();
        foreach (SnmpTemplate::model()->findAll() as $template) {
            $result[] = self::toArray($template);
        }
        return $result;
    }

    public static function getSnmpTemplate($arguments)
    {
        return self::toArray(self::loadOr404($arguments));
    }

    public static function createSnmpTemplate($arguments)
    {
        $template = new SnmpTemplate;
        $template->attributes = $arguments;
        if (!$template->save()) {
            throw new McpToolException(self::errorsToMessage($template));
        }
        return self::toArray($template);
    }

    public static function updateSnmpTemplate($arguments)
    {
        $template = self::loadOr404($arguments);
        $template->attributes = $arguments;
        if (!$template->save()) {
            throw new McpToolException(self::errorsToMessage($template));
        }
        return self::toArray($template);
    }

    public static function deleteSnmpTemplate($arguments)
    {
        $template = self::loadOr404($arguments);
        $template->delete();
        return self::castIntFields(array('deleted' => true, 'id' => $template->id), array('id'));
    }

    private static function loadOr404($arguments)
    {
        $id = self::extractId($arguments);
        $template = $id ? SnmpTemplate::model()->findByPk($id) : null;
        if ($template === null) {
            throw new McpToolException('SnmpTemplate not found: ' . $id);
        }
        return $template;
    }

    /**
     * Deliberately excludes community, security_name, auth_passphrase and
     * priv_passphrase — credential material is never returned by a read
     * tool, even though it's accepted as write input (see definitions()).
     */
    private static function toArray($template)
    {
        return self::castIntFields(array(
            'id' => $template->id,
            'name' => $template->name,
            'version' => $template->version,
            'security_level' => $template->security_level,
            'auth_protocol' => $template->auth_protocol,
            'priv_protocol' => $template->priv_protocol,
            'timeout' => $template->timeout,
            'retries' => $template->retries,
        ), array('id', 'timeout', 'retries'));
    }
}
