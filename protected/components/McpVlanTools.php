<?php

class McpVlanTools
{
    use McpToolHelperTrait;

    public static function definitions()
    {
        $fieldsSchema = array(
            'tag' => array('type' => 'integer'),
            'name' => array('type' => 'string'),
            'description' => array('type' => 'string'),
            'background_color' => array('type' => 'string'),
            'font_color' => array('type' => 'string'),
        );

        return array(
            'list_vlans' => array(
                'mode' => 'readonly',
                'description' => 'List all vlans',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'listVlans'),
            ),
            'get_vlan' => array(
                'mode' => 'readonly',
                'description' => 'Get a single vlan by id',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'getVlan'),
            ),
            'create_vlan' => array(
                'mode' => 'readwrite',
                'description' => 'Create a new vlan',
                'inputSchema' => array('type' => 'object', 'properties' => $fieldsSchema, 'required' => array('tag', 'name')),
                'handler' => array(__CLASS__, 'createVlan'),
            ),
            'update_vlan' => array(
                'mode' => 'readwrite',
                'description' => 'Update an existing vlan',
                'inputSchema' => array('type' => 'object', 'properties' => array_merge(array('id' => array('type' => 'integer')), $fieldsSchema), 'required' => array('id')),
                'handler' => array(__CLASS__, 'updateVlan'),
            ),
            'delete_vlan' => array(
                'mode' => 'readwrite',
                'description' => 'Delete a vlan',
                'inputSchema' => self::idSchema(),
                'handler' => array(__CLASS__, 'deleteVlan'),
            ),
        );
    }

    public static function listVlans($arguments)
    {
        $result = array();
        foreach (Vlan::model()->findAll() as $vlan) {
            $result[] = self::toArray($vlan);
        }
        return $result;
    }

    public static function getVlan($arguments)
    {
        return self::toArray(self::loadOr404($arguments));
    }

    public static function createVlan($arguments)
    {
        $vlan = new Vlan;
        $vlan->attributes = $arguments;
        if (!$vlan->save()) {
            throw new McpToolException(self::errorsToMessage($vlan));
        }
        return self::toArray($vlan);
    }

    public static function updateVlan($arguments)
    {
        $vlan = self::loadOr404($arguments);
        $vlan->attributes = $arguments;
        if (!$vlan->save()) {
            throw new McpToolException(self::errorsToMessage($vlan));
        }
        return self::toArray($vlan);
    }

    public static function deleteVlan($arguments)
    {
        $vlan = self::loadOr404($arguments);
        $vlan->delete();
        return array('deleted' => true, 'id' => $vlan->id);
    }

    private static function loadOr404($arguments)
    {
        $id = self::extractId($arguments);
        $vlan = $id ? Vlan::model()->findByPk($id) : null;
        if ($vlan === null) {
            throw new McpToolException('Vlan not found: ' . $id);
        }
        return $vlan;
    }

    private static function toArray($vlan)
    {
        return self::castIntFields(array(
            'id' => $vlan->id,
            'tag' => $vlan->tag,
            'name' => $vlan->name,
            'description' => $vlan->description,
            'background_color' => $vlan->background_color,
            'font_color' => $vlan->font_color,
        ), array('id', 'tag'));
    }
}
