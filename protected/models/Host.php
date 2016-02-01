<?php

/**
 * This is the model class for table "host".
 *
 * The followings are the available columns in table 'host':
 * @property integer $id
 * @property string $name
 * @property string $type
 * @property string $mac
 * @property string $ip
 * @property integer $snmp_template_id
 * @property integer $host_face_id
 *
 * The followings are the available model relations:
 * @property Connection[] $connections_src
 * @property Connection[] $connections_dst
 * @property SnmpTemplate $snmpTemplate
 */
class Host extends CActiveRecord {

    private $ifNameExceptionsRegex = '/(NULL|Vlan|Tunnel|ipfw0|pfsync0|pflog0|enc0|console|loopback|meth)/i';

    const TYPE_UNKNOWN = 'unknown';
    const TYPE_SWITCH = 'switch';
    const TYPE_HUB = 'hub';
    const TYPE_ROUTER = 'router';
    const TYPE_WIFI_ROUTER = 'wifi_router';
    const TYPE_SERVER = 'server';
    const TYPE_DESKTOP = 'desktop';
    const TYPE_SUPPOSED_HUB = "supposed_hub";
    const TYPE_SECURITY_CAMERA = "security_camera";

    public $ports = array();
    public $arp_table = array();
    public $cam_table = array();
    public $connections = array();
    private $types_by_mac_prefix = array(
        '00:40:8c' => self::TYPE_SECURITY_CAMERA,
        'e4:aa:5d' => self::TYPE_WIFI_ROUTER,
        '84:b2:61' => self::TYPE_WIFI_ROUTER,
    );

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Host the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'host';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('type', 'required'),
            array('snmp_template_id, host_face_id', 'numerical', 'integerOnly' => true),
            array('name, type', 'length', 'max' => 50),
            array('mac', 'length', 'max' => 17),
            array('ip', 'length', 'max' => 15),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, type, mac, ip, snmp_template_id, host_face_id', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'dst_connections' => array(self::HAS_MANY, 'Connection', 'host_dst_id'),
            'src_connections' => array(self::HAS_MANY, 'Connection', 'host_src_id'),
            'snmpTemplate' => array(self::BELONGS_TO, 'SnmpTemplate', 'snmp_template_id'),
            'hostFace' => array(self::BELONGS_TO, 'HostFace', 'host_face_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'type' => 'Type',
            'mac' => 'Mac',
            'ip' => 'Ip',
            'snmp_template_id' => 'Snmp Template',
            'host_face_id' => 'Host Face',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria();
        $criteria->with = array('snmpTemplate');
        $criteria->compare('t.id', $this->id);
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.type', $this->type, true);
        $criteria->compare('t.mac', $this->mac, true);
        $criteria->compare('t.ip', $this->ip, true);
        $criteria->compare('t.snmp_template_id', $this->snmp_template_id);
        $criteria->compare('snmp_template.name', $this->snmpTemplate, true);
        $criteria->compare('host_face_id', $this->host_face_id);

        return new CActiveDataProvider('Host', array(
            'criteria' => $criteria,
            'sort' => array(
                'attributes' => array(
                    'snmpTemplate' => array(
                        'asc' => 'snmpTemplate.name',
                        'desc' => 'snmpTemplate.name DESC',
                    ),
                    '*',
                ),
            ),
        ));
    }

    /**
     * type labels
     * @return array
     */
    public static function getTypes() {
        return array(
            self::TYPE_SWITCH => 'Switch',
            self::TYPE_ROUTER => 'Router',
            self::TYPE_WIFI_ROUTER => 'WiFi Router',
            self::TYPE_HUB => 'Hub',
            self::TYPE_SERVER => 'Server',
            self::TYPE_DESKTOP => 'Desktop',
            self::TYPE_UNKNOWN => 'Unknown',
        );
    }

    /**
     * yii method for default ordening
     * @return array
     */
    public function defaultScope() {
        return array(
            'order' => $this->getTableAlias(false, false) . '.name'
        );
    }

    /**
     * 
     * @return array
     */
    public function getConnections() {

        if (!count($this->connections)) {

            $this->connections = $this->src_connections;

            foreach ($this->dst_connections as $k => $dstConn) {
                $srcConn = clone $dstConn;
                $srcConn->hostDst = $dstConn->hostSrc;
                $srcConn->host_dst_port = $dstConn->host_src_port;
                $srcConn->hostSrc = $dstConn->hostDst;
                $srcConn->host_src_port = $dstConn->host_dst_port;
                array_push($this->connections, $srcConn);
            }
            usort($this->connections, "orderConnectionsByPort");
        }

        return $this->connections;
    }

    /**
     * Consulta tabela ARP por SNMP
     * @return array lista com MAC e IP
     */
    public function loadArpTable() {

        if (!$this->snmp_template_id)
            return array();



        if ($this->arp_table) {
            return $this->arp_table;
        }

        try {

            $res = PNMSnmp::walk($this, '.1.3.6.1.2.1.4.22.1.2', Yii::app()->params['cacheTtlArp']);
            
            

            while (list($key, $data) = each($res)) {
                #$ip = preg_replace('/IP-MIB::ipNetToMediaPhysAddress\.[0-9]+\./', '', $key);
                $ip = preg_replace('/.1.3.6.1.2.1.4.22.1.2\.[0-9]+\./', '', $key);
                #$mac_snmp = strtolower(str_replace('STRING: ', '', $data));
                $mac_snmp = strtolower(str_replace('Hex-STRING: ', '', $data));

                /*
                  $str = explode(':', $mac_snmp);
                  $mac = null;
                  for ($i = 0; $i < 6; $i++) {
                  @$mac .= str_pad($str[$i], 2, "0", STR_PAD_LEFT) . (($str[$i + 1] !== null) ? ':' : '');
                  }
                 */

                $mac = str_replace(' ', ':', trim($mac_snmp));

                $this->arp_table[$mac] = $ip;
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }

        return $this->arp_table;
    }

    /**
     * Retorna uma lista com os MACs com as portas
     * e as vlans nas quais estao conectados
     * 
     * @return array lista com porta, mac e IP se houver na base de dados
     */
    public function loadCamTable() {

        if ($this->type != Host::TYPE_SWITCH && !$this->snmp_template_id) {
            return array();
        }

        if ($this->cam_table) {
            return $this->cam_table;
        }

        try {

            $res = PNMSnmp::walk($this, '.1.3.6.1.2.1.17.7.1.2.2.1.2', Yii::app()->params['cacheTtlCam']);

            if (is_array($res)) {
                while (list($key, $data) = each($res)) {
                    #$dt = explode('::', $key);
                    #$dt[1] = str_replace('mib-2.17.7.1.2.2.1.2.', '', $dt[1]);
                    // TODO: trocar OIDs por campos das MIBs a carregar
                    $dt = str_replace('.1.3.6.1.2.1.17.7.1.2.2.1.2.', '', $key);
                    $str = explode('.', $dt);

                    // vlan
                    $vlan_tag = $str[0];

                    // port
                    $port = (int) str_replace('INTEGER: ', '', $data);

                    // mac
                    $mac = null;
                    for ($i = 1; $i < 7; $i++) {
                        @$mac .= str_pad(dechex($str[$i]), 2, "0", STR_PAD_LEFT) . (($str[$i + 1] !== null) ? ':' : '');
                    }

                    @$this->cam_table[] = array('port' => $port, 'vlan_tag' => $vlan_tag, 'mac' => strtolower($mac));
                }
            }

            array_multisort($this->cam_table);
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }
    }

    /**
     *  Get ports information by SNMP
     * @param array $keys
     * @return array 
     * @throws Exception
     */
    public function loadPortsInfo($keys) {

        try {

            foreach ($keys as $key) {
                
                $resSNMPPorts = PNMSnmp::walk($this, PNMSnmp::getOid($key), 2);

                foreach ($resSNMPPorts as $oid => $res) {

                    preg_match('/(.+[.][0-9]+)[.]([0-9]+)/', $oid, $match);
                    $portIndex = (int) $match[2];
                    //if( ! isset($this->ports[$portIndex]) ) {
                    $this->ports[$portIndex]['ifIndex'] = $portIndex;
                    //}
                    preg_match('/([^:]+): "?([^"]+)"?/', $res, $values_match);

                    switch($values_match[1]) {
                        case "INTEGER":
                        case "Gauge32":
                        case "Counter32":
                            $this->ports[$portIndex][$key] = (int) $values_match[2];
                            break;
                        default:
                            $this->ports[$portIndex][$key] = $values_match[2];
                    }
                    
                    
                    $hostConn = $this->getConnectionOnPort($portIndex);    
                    if ($hostConn instanceof Connection) {
                        $this->ports[$portIndex]['hasConnection'] = $hostConn->hostDst;
                    }
                    
                }
                
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }
        
        foreach ($this->ports as $k => $port) {
                    if (preg_match($this->ifNameExceptionsRegex, $port['ifDescr'])) {
                        unset($this->ports[$k]);
                    }
        }

        return $this->ports;
    }

    /**
     * Return host connected on port number
     * @param integer $port
     * @return mixed
     * @throws Exception
     */
    public function getHostOnPort($port) {

        try {

            foreach ($this->getConnections() as $k => $r) {
                if ($port == $r->host_src_port) {
                    return $r->hostDst;
                }
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }

        return null;
    }

    public function getConnectionOnPort($port) {

        try {

            foreach ($this->getConnections() as $k => $r) {
                if ($port == $r->host_src_port) {
                    return $r;
                }
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }

        return null;
    }

    public function __toString() {
        return ($this->name) ? $this->name : $this->ip;
    }

    public function getSNMPField($key) {

        //$fields = $this->snmpTemplate->fields;
        //return $value;
    }

    public function getInfoSystem() {

        return PNMSnmp::get($this, '.1.3.6.1.2.1.1.1.0', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function getInfoUptime() {

        return PNMSnmp::get($this, '.1.3.6.1.2.1.1.3.0', 2);
    }

    public function getInfoContact() {

        return PNMSnmp::get($this, '.1.3.6.1.2.1.1.4.0', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function getInfoHostName() {

        return PNMSnmp::get($this, '.1.3.6.1.2.1.1.5.0', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function getInfoLocation() {

        return PNMSnmp::get($this, '.1.3.6.1.2.1.1.6.0', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function getInfoSerialNumber() {

        //TODO: retornar o serial do equipamento dependendo da marca
        //
        // 3COM
        return PNMSnmp::get($this, '1.3.6.1.2.1.47.1.1.1.1.11.1', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function getInfoModel() {

        //TODO: retornar o serial do equipamento dependendo da marca
        //
        // 3COM
        return PNMSnmp::get($this, '1.3.6.1.2.1.47.1.1.1.1.13.1', Yii::app()->params['cacheTtlGetSnmp']);
    }

    public function setTypeByMAC() {
        // TODO: detectar tipo de host pelo prefixo MAC
        if (!empty($this->mac)) {
            $mac_suffix = substr($this->mac, 0, 8);
            $this->type = (isset($this->types_by_mac_prefix[$mac_suffix])) ? $this->types_by_mac_prefix[$mac_suffix] : self::TYPE_UNKNOWN;
        }
    }
    
    public function getIpInArpTable($macHost) {
        
        $result = null;
        
        if(!$this->arp_table) {
            $this->loadArpTable();
        }
        
        foreach($this->arp_table as $macArp => $ip) {
            if ($macArp == $macHost) {
                $result = $ip; 
            }
        }
        return $result;
    }

    public function setSNMPValue($oid, $type, $value) {
        return PNMSnmp::set($this, $oid, $type, $value);
    }

}

function orderConnectionsByPort($a, $b) {
    if ($a->host_src_port == $b->host_src_port) {
        return 0;
    }
    return ($a->host_src_port < $b->host_src_port) ? -1 : 1;
}
