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
    public $sysDescr;
    public $sysName;
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
            array('id, name, type, mac, ip, snmp_template_id, host_face_id, snmpTemplate', 'safe', 'on' => 'search'),
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
        $criteria->compare('t.id', $this->id);
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.type', $this->type, true);
        $criteria->compare('t.mac', $this->mac, true);
        $criteria->compare('t.ip', $this->ip, true);
        $criteria->compare('t.snmp_template_id', $this->snmp_template_id);
        $criteria->compare('host_face_id', $this->host_face_id);
        
        $criteria->with = array('snmpTemplate');
        $criteria->addSearchCondition('snmpTemplate.name',$this->snmpTemplate);
        
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

            // dot1dBasePortIfIndex: traduz o número de porta do Bridge-MIB
            // (dot1dBasePort, usado pela tabela CAM abaixo) pro ifIndex real
            // — o mesmo usado em todo o resto do app (Host Face,
            // Connection::host_src_port, etc.). Em switches simples (ex.:
            // HP, 3com) esse mapeamento costuma ser a identidade, o que
            // mascarava a falta dessa tradução; em switches com numeração
            // diferente (ex.: Huawei) os dois números divergem, e sem essa
            // tradução a porta da tabela CAM nunca casa com a porta clicada
            // na Host Face. Se o equipamento não implementar essa tabela,
            // cai pro valor cru (comportamento anterior).
            $bridgePortToIfIndex = array();
            $resBasePortIfIndex = PNMSnmp::walk($this, '.1.3.6.1.2.1.17.1.4.1.2', Yii::app()->params['cacheTtlCam']);
            if (is_array($resBasePortIfIndex)) {
                foreach ($resBasePortIfIndex as $key => $data) {
                    $dt = str_replace('.1.3.6.1.2.1.17.1.4.1.2.', '', $key);
                    $bridgePort = (int) $dt;
                    $ifIndex = (int) str_replace('INTEGER: ', '', $data);
                    $bridgePortToIfIndex[$bridgePort] = $ifIndex;
                }
            }

            // Q-BRIDGE-MIB (dot1qTpFdbPort): tabela CAM com VLAN, suportada pela maioria
            // dos switches gerenciáveis.
            $res = PNMSnmp::walk($this, '.1.3.6.1.2.1.17.7.1.2.2.1.2', Yii::app()->params['cacheTtlCam']);

            if (is_array($res) && $res) {
                foreach ($res as $key => $data) {
                    $dt = str_replace('.1.3.6.1.2.1.17.7.1.2.2.1.2.', '', $key);
                    $str = explode('.', $dt);

                    $vlan_tag = $str[0];
                    $port = (int) str_replace('INTEGER: ', '', $data);
                    $port = isset($bridgePortToIfIndex[$port]) ? $bridgePortToIfIndex[$port] : $port;
                    $mac = $this->macFromOidSuffix($str, 1);

                    @$this->cam_table[] = array('port' => $port, 'vlan_tag' => $vlan_tag, 'mac' => $mac);
                }
            } else {
                // Alguns equipamentos (ex.: switches Huawei) não implementam o
                // Q-BRIDGE-MIB. Nesse caso cai para o BRIDGE-MIB clássico
                // (dot1dTpFdbPort), que não tem VLAN no índice.
                $res = PNMSnmp::walk($this, '.1.3.6.1.2.1.17.4.3.1.2', Yii::app()->params['cacheTtlCam']);

                // dot1qPvid (VLAN nativa/access configurada em cada porta):
                // sem a tabela CAM com VLAN por MAC, é a aproximação possível
                // — certa pra porta de acesso (só uma VLAN), mas pode não
                // bater em porta trunk com VLANs marcadas, já que aí mostra
                // sempre a nativa da porta, não a VLAN real de cada MAC.
                $ifIndexPvid = array();
                $resPvid = PNMSnmp::walk($this, '.1.3.6.1.2.1.17.7.1.4.5.1.1', Yii::app()->params['cacheTtlCam']);
                if (is_array($resPvid)) {
                    foreach ($resPvid as $keyPvid => $dataPvid) {
                        $bridgePortPvid = (int) str_replace('.1.3.6.1.2.1.17.7.1.4.5.1.1.', '', $keyPvid);
                        $pvid = (int) str_replace('Gauge32: ', '', $dataPvid);
                        if (isset($bridgePortToIfIndex[$bridgePortPvid])) {
                            $ifIndexPvid[$bridgePortToIfIndex[$bridgePortPvid]] = $pvid;
                        }
                    }
                }

                // Alguns equipamentos (ex.: Extreme EXOS) não respondem nem
                // ao dot1qPvid — a VLAN nativa por porta só sai pelo MIB
                // proprietário da Extreme (EXTREME-VLAN-MIB). Mesma
                // aproximação e mesma ressalva de porta trunk do PVID acima.
                if (empty($ifIndexPvid)) {
                    $ifIndexPvid = $this->loadExtremeUntaggedVlanByIfIndex($bridgePortToIfIndex);
                }

                if (is_array($res)) {
                    foreach ($res as $key => $data) {
                        $dt = str_replace('.1.3.6.1.2.1.17.4.3.1.2.', '', $key);
                        $str = explode('.', $dt);

                        $port = (int) str_replace('INTEGER: ', '', $data);
                        $port = isset($bridgePortToIfIndex[$port]) ? $bridgePortToIfIndex[$port] : $port;
                        $mac = $this->macFromOidSuffix($str, 0);
                        $vlan_tag = isset($ifIndexPvid[$port]) ? $ifIndexPvid[$port] : null;

                        @$this->cam_table[] = array('port' => $port, 'vlan_tag' => $vlan_tag, 'mac' => $mac);
                    }
                }
            }

            array_multisort($this->cam_table);
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }
    }

    /**
     * Aproxima a VLAN nativa/sem tag de cada porta em switches Extreme EXOS,
     * via EXTREME-VLAN-MIB: extremeVlanIfVlanId dá o ID de cada VLAN (indexada
     * por um ifIndex interno da Extreme, sem relação com o ifIndex real da
     * porta), e extremeVlanOpaqueUntaggedPorts dá, por VLAN, uma bitmask das
     * portas nativas/sem tag — confirmado (EXTREME-BASE-MIB) que segue a
     * mesma convenção PortList do Q-BRIDGE-MIB: bit mais significativo de
     * cada octeto é a porta mais baixa daquele octeto.
     * @param array $bridgePortToIfIndex mapa dot1dBasePort => ifIndex
     * @return array ifIndex => VLAN tag
     */
    private function loadExtremeUntaggedVlanByIfIndex($bridgePortToIfIndex) {
        $ifIndexVlan = array();

        $vlanIdByExtremeIfIndex = array();
        $resVlanId = PNMSnmp::walk($this, '.1.3.6.1.4.1.1916.1.2.1.2.1.10', Yii::app()->params['cacheTtlCam']);
        if (is_array($resVlanId)) {
            foreach ($resVlanId as $key => $data) {
                $extremeIfIndex = (int) str_replace('.1.3.6.1.4.1.1916.1.2.1.2.1.10.', '', $key);
                $vlanIdByExtremeIfIndex[$extremeIfIndex] = (int) str_replace('INTEGER: ', '', $data);
            }
        }
        if (empty($vlanIdByExtremeIfIndex)) {
            return $ifIndexVlan;
        }

        $resUntagged = PNMSnmp::walk($this, '.1.3.6.1.4.1.1916.1.2.6.1.1.2', Yii::app()->params['cacheTtlCam']);
        if (!is_array($resUntagged)) {
            return $ifIndexVlan;
        }
        foreach ($resUntagged as $key => $data) {
            $dt = str_replace('.1.3.6.1.4.1.1916.1.2.6.1.1.2.', '', $key);
            $extremeIfIndex = (int) explode('.', $dt)[0];
            if (!isset($vlanIdByExtremeIfIndex[$extremeIfIndex])) {
                continue;
            }
            $vlanTag = $vlanIdByExtremeIfIndex[$extremeIfIndex];
            foreach ($this->extremePortListToPortNumbers($data) as $portNumber) {
                if (isset($bridgePortToIfIndex[$portNumber])) {
                    $ifIndexVlan[$bridgePortToIfIndex[$portNumber]] = $vlanTag;
                }
            }
        }

        return $ifIndexVlan;
    }

    /**
     * Decodifica um PortList (Hex-STRING do net-snmp, ex.: "Hex-STRING: 40 00
     * 09 00 ...") pros números de porta com o bit ligado, na convenção
     * "bit mais significativo de cada octeto = porta mais baixa daquele
     * octeto" (octeto 0 = portas 1-8, octeto 1 = portas 9-16, ...).
     * @param string $hexString valor cru retornado pelo walk do net-snmp
     * @return array números de porta (1-based)
     */
    private function extremePortListToPortNumbers($hexString) {
        $hex = trim(preg_replace('/^[^:]+:\s*/', '', $hexString));
        $hex = preg_replace('/\s+/', ' ', $hex);
        $bytes = array_values(array_filter(explode(' ', $hex), function ($b) { return $b !== ''; }));

        $ports = array();
        foreach ($bytes as $byteIndex => $byteHex) {
            $byteVal = hexdec($byteHex);
            for ($bit = 7; $bit >= 0; $bit--) {
                if ($byteVal & (1 << $bit)) {
                    $ports[] = $byteIndex * 8 + (7 - $bit) + 1;
                }
            }
        }
        return $ports;
    }

    /**
     * Monta um MAC address (aa:bb:cc:dd:ee:ff) a partir dos 6 octetos que ficam
     * no sufixo do OID de uma tabela FDB (Q-BRIDGE-MIB ou BRIDGE-MIB clássico).
     * @param array $oidParts sufixo do OID já explodido por "."
     * @param int $offset posição do primeiro octeto do MAC dentro de $oidParts
     * @return string
     */
    private function macFromOidSuffix($oidParts, $offset) {
        $mac = null;
        for ($i = $offset; $i < $offset + 6; $i++) {
            $mac .= str_pad(dechex($oidParts[$i]), 2, "0", STR_PAD_LEFT) . (($i < $offset + 5) ? ':' : '');
        }
        return strtolower($mac);
    }

    /**
     * Formata um valor obtido via SNMP para exibição na tela do equipamento,
     * deixando claro quando é o próprio equipamento que não relatou aquele
     * dado (em vez do "Not set" genérico do Yii, usado também por atributos
     * de banco vazios como mac/ip).
     * @param mixed $value
     * @return string
     */
    public static function formatSnmpInfo($value) {
        if ($value === null || $value === '') {
            return '<span class="muted">not reported via SNMP</span>';
        }
        return CHtml::encode($value);
    }

    /**
     *  Get ports information by SNMP
     * @param array $keys
     * @param int $cacheTtl segundos de cache (default 2, igual ao
     *   comportamento anterior). Passe 0 pra contador usado em cálculo de
     *   taxa (ex.: tráfego) — ver PNMSnmp::walk().
     * @return array
     * @throws Exception
     */
    public function loadPortsInfo($keys, $cacheTtl = 2) {

        try {

            foreach ($keys as $key) {

                $resSNMPPorts = PNMSnmp::walk($this, PNMSnmp::getOid($key), $cacheTtl);

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
                        case "Counter64":
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
     * Loads sysDescr/sysName via SNMP (scalar OIDs, not walked). Either can
     * come back null if the device doesn't answer — PNMSnmp::get() already
     * returns null on no response instead of throwing.
     */
    public function loadSystemInfo() {
        $this->sysDescr = PNMSnmp::get($this, PNMSnmp::getOid('sysDescr'));
        $this->sysName = PNMSnmp::get($this, PNMSnmp::getOid('sysName'));
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

    /**
     * Direção inversa de getIpInArpTable(): dado um IP, procura o MAC
     * correspondente na tabela ARP deste host (tipicamente o gateway da
     * rede) — usado pra completar o mac de hosts que já têm ip cadastrado.
     * @param string $ip
     * @return string|null
     */
    public function getMacInArpTable($ip) {

        $result = null;

        if (!$this->arp_table) {
            $this->loadArpTable();
        }

        foreach ($this->arp_table as $macArp => $ipArp) {
            if ($ipArp == $ip) {
                $result = $macArp;
            }
        }
        return $result;
    }

    /**
     * Tenta descobrir automaticamente em qual porta DESTE switch está o
     * link físico que liga a $otherHost:$otherPort — cruza a tabela CAM
     * dos dois lados: pega o conjunto de MACs aprendidos por $otherHost
     * naquela porta (tipicamente toda a rede a jusante, num link
     * switch-a-switch) e procura, entre as portas deste host, aquela cujo
     * conjunto de MACs aprendidos tem a maior interseção com esse
     * conjunto. Só retorna um palpite quando essa porta vence com folga
     * (sem empate no topo) — senão devolve null em vez de arriscar uma
     * porta errada.
     * @param Host $otherHost
     * @param int $otherPort ifIndex da porta em $otherHost
     * @return int|null ifIndex da porta detectada neste host, ou null
     */
    public function detectConnectedPort($otherHost, $otherPort) {
        if ($this->type != Host::TYPE_SWITCH || !$this->snmp_template_id) {
            return null;
        }
        if (!($otherHost instanceof Host) || $otherHost->type != Host::TYPE_SWITCH || !$otherHost->snmp_template_id) {
            return null;
        }

        $otherHost->loadCamTable();
        $otherMacs = array();
        foreach ($otherHost->cam_table as $ctItem) {
            if ($ctItem['port'] == $otherPort && !empty($ctItem['mac'])) {
                $otherMacs[$ctItem['mac']] = true;
            }
        }
        if (!$otherMacs) {
            return null;
        }

        $this->loadCamTable();
        $overlapByPort = array();
        foreach ($this->cam_table as $ctItem) {
            if (empty($ctItem['mac']) || !isset($otherMacs[$ctItem['mac']])) {
                continue;
            }
            $overlapByPort[$ctItem['port']] = isset($overlapByPort[$ctItem['port']]) ? $overlapByPort[$ctItem['port']] + 1 : 1;
        }
        if (!$overlapByPort) {
            return null;
        }

        arsort($overlapByPort);
        $ports = array_keys($overlapByPort);
        if (count($ports) > 1 && $overlapByPort[$ports[0]] == $overlapByPort[$ports[1]]) {
            return null; // empate no topo — não arriscar um palpite errado
        }
        return $ports[0];
    }

    public function setSNMPValue($oid, $type, $value) {
        return PNMSnmp::set($this, $oid, $type, $value);
    }
    
    public function save($runValidation = true, $attributes = null) {
        
        if(preg_match('/([0-9A-F]{2})([0-9A-F]{2})-([0-9A-F]{2})([0-9A-F]{2})-([0-9A-F]{2})([0-9A-F]{2})/', $this->mac, $m)) {
            $this->mac = "{$m[1]}:{$m[2]}:{$m[3]}:{$m[4]}:{$m[5]}:{$m[6]}";
        }
        
        $this->mac = strtolower($this->mac);
        
        return parent::save($runValidation, $attributes);
    }

}

function orderConnectionsByPort($a, $b) {
    if ($a->host_src_port == $b->host_src_port) {
        return 0;
    }
    return ($a->host_src_port < $b->host_src_port) ? -1 : 1;
}
