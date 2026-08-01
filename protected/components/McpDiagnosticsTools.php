<?php

class McpDiagnosticsTools
{
    use McpToolHelperTrait;

    public static function definitions()
    {
        return array(
            'get_cam_table' => array(
                'mode' => 'readonly',
                'description' => 'Get the live CAM (MAC address) table of a host',
                'inputSchema' => self::hostIdSchema(),
                'handler' => array(__CLASS__, 'getCamTable'),
            ),
            'get_arp_table' => array(
                'mode' => 'readonly',
                'description' => 'Get the live ARP table of a host',
                'inputSchema' => self::hostIdSchema(),
                'handler' => array(__CLASS__, 'getArpTable'),
            ),
            'list_ports' => array(
                'mode' => 'readonly',
                'description' => 'List ports of a host with their status',
                'inputSchema' => self::hostIdSchema(),
                'handler' => array(__CLASS__, 'listPorts'),
            ),
        );
    }

    private static function hostIdSchema()
    {
        return array('type' => 'object', 'properties' => array('host_id' => array('type' => 'integer')), 'required' => array('host_id'));
    }

    public static function getCamTable($arguments)
    {
        $host = self::loadHostOr404($arguments);

        try {
            $host->loadCamTable();
        } catch (Exception $e) {
            throw new McpToolException($e->getMessage());
        }

        $result = array();
        foreach ($host->cam_table as $item) {
            $result[] = array('mac' => $item['mac'], 'port' => $item['port'], 'vlan_tag' => $item['vlan_tag']);
        }
        return $result;
    }

    public static function getArpTable($arguments)
    {
        $host = self::loadHostOr404($arguments);

        try {
            $host->loadArpTable();
        } catch (Exception $e) {
            throw new McpToolException($e->getMessage());
        }

        $result = array();
        foreach ($host->arp_table as $mac => $ip) {
            $result[] = array('mac' => $mac, 'ip' => $ip);
        }
        return $result;
    }

    public static function listPorts($arguments)
    {
        $host = self::loadHostOr404($arguments);

        try {
            $host->loadPortsInfo(array('ifDescr', 'ifAlias', 'ifOperStatus', 'ifAdminStatus', 'dot1dStpPortState'));
        } catch (Exception $e) {
            throw new McpToolException($e->getMessage());
        }

        $result = array();
        foreach ($host->ports as $index => $port) {
            $result[] = array(
                'index' => $index,
                'ifDescr' => isset($port['ifDescr']) ? $port['ifDescr'] : null,
                'ifAlias' => isset($port['ifAlias']) ? $port['ifAlias'] : null,
                'ifOperStatus' => isset($port['ifOperStatus']) ? $port['ifOperStatus'] : null,
                'ifAdminStatus' => isset($port['ifAdminStatus']) ? $port['ifAdminStatus'] : null,
                'dot1dStpPortState' => isset($port['dot1dStpPortState']) ? $port['dot1dStpPortState'] : null,
            );
        }
        return $result;
    }

    private static function loadHostOr404($arguments)
    {
        $id = self::extractId($arguments, 'host_id');
        $host = $id ? Host::model()->findByPk($id) : null;
        if ($host === null) {
            throw new McpToolException('Host not found: ' . $id);
        }
        return $host;
    }
}
