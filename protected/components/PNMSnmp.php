<?php

/*
 * SNMP Class
 * For SNMP connections by SNMP template
 */

class PNMSnmp {
    
    /** TODO: Use MIBs! */
    static $oids = array(
        'ifEntry'           => '.1.3.6.1.2.1.2.2.1',
        'ifIndex'           => '.1.3.6.1.2.1.2.2.1.1',
        'ifDescr'           => '.1.3.6.1.2.1.2.2.1.2',
        'ifAlias'           => '.1.3.6.1.2.1.31.1.1.1.18',
        'ifType'            => '.1.3.6.1.2.1.2.2.1.3',
        'ifMtu'             => '.1.3.6.1.2.1.2.2.1.4',
        'ifSpeed'           => '.1.3.6.1.2.1.2.2.1.5',
        'ifPhysAddress'     => '.1.3.6.1.2.1.2.2.1.6',
        'ifAdminStatus'     => '.1.3.6.1.2.1.2.2.1.7',
        'ifOperStatus'      => '.1.3.6.1.2.1.2.2.1.8',
        'ifLastChange'      => '.1.3.6.1.2.1.2.2.1.9',
        'ifInOctets'        => '.1.3.6.1.2.1.2.2.1.10',
        'ifInUcastPkts'     => '.1.3.6.1.2.1.2.2.1.11',
        'ifInNUcastPkts'    => '.1.3.6.1.2.1.2.2.1.12',
        'ifInDiscards'      => '.1.3.6.1.2.1.2.2.1.13',
        'ifInErrors'        => '.1.3.6.1.2.1.2.2.1.14',
        'ifInUnknownProtos' => '.1.3.6.1.2.1.2.2.1.15',
        'ifOutOctets'       => '.1.3.6.1.2.1.2.2.1.16',
        'ifOutUcastPkts'    => '.1.3.6.1.2.1.2.2.1.17',
        'ifOutNUcastPkts'   => '.1.3.6.1.2.1.2.2.1.18',
        'ifOutDiscards'     => '.1.3.6.1.2.1.2.2.1.19',
        'ifOutErrors'       => '.1.3.6.1.2.1.2.2.1.20',
        'ifOutQLen'         => '.1.3.6.1.2.1.2.2.1.21',
        'ifHCInOctets'      => '.1.3.6.1.2.1.31.1.1.1.6',
        'ifHCOutOctets'     => '.1.3.6.1.2.1.31.1.1.1.10',
        'ifSpecific'        => '.1.3.6.1.2.1.2.2.1.22',
        'dot1dStpPortState' => '.1.3.6.1.2.1.17.2.15.1.3', // spanning Tree
    );
    
//    public function read_mibs() {
//        $mib_path='/var/www/phpnetmap/protected/data/mibs/';   
//        if ($handle = opendir($mib_path))
//        { 
//           while (false !== ($file = readdir($handle)))
//           { 
//               if($file!='.' || $file!='..')
//                   snmp_read_mib($mib_path.$file);
//           }
//       }
//       closedir($handle);
//    }

    static $oid_format = SNMP_OID_OUTPUT_NUMERIC;


    static function walk($host, $object_id, $cache_ttl = null) {
        
        if($cache_ttl) {
            $cache_var = str_replace('.','_',$host->ip.$object_id);
            $cache = new CacheAPC();
            $resultCache = $cache->load($cache_var);
            if($resultCache !== null) {
                return $resultCache;
            }
        }
        
        snmp_set_oid_output_format(self::$oid_format);

        $snmp = $host->snmpTemplate;

        if ($snmp instanceof SnmpTemplate) {
            switch ($snmp->version) {
                case "1":
                    $result = @snmprealwalk($host->ip, $snmp->community, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                case "2":
                case "2c":
                    $result = @snmp2_real_walk($host->ip, $snmp->community, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                case "3":
                    $result = @snmp3_real_walk($host->ip, $snmp->security_name, $snmp->security_level, $snmp->auth_protocol, $snmp->auth_passphrase, $snmp->priv_protocol, $snmp->priv_passphrase, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                default: throw new Exception('SNMP Template not implemented yet');
            }
        }

        if (is_array($result)) {
            if($cache_var && $cache_ttl) {
                $cache->save($cache_var, $result, $cache_ttl);
            }
            return $result;
        } else {
            //throw new Exception("Sem resposta SNMP");
            return array();
        }
    }

    static function get($host, $object_id, $cache_ttl = null) {
        
        if($cache_ttl) {
            $cache_var = str_replace('.','_',$host->ip.$object_id);
            $cache = new CacheAPC();
            $resultCache = $cache->load($cache_var);
            if($resultCache !== null) {
                return $resultCache;
            }
        }
        
        snmp_set_oid_output_format(self::$oid_format);
        
        $snmp = $host->snmpTemplate;

        if ($snmp instanceof SnmpTemplate) {
            switch ($snmp->version) {
                case "1":
                    $result = @snmpget($host->ip, $snmp->community, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                case "2":
                case "2c":
                    $result = @snmp2_get($host->ip, $snmp->community, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                case "3":
                    $result = @snmp3_get($host->ip, $snmp->security_name, $snmp->security_level, $snmp->auth_protocol, $snmp->auth_passphrase, $snmp->priv_protocol, $snmp->priv_passphrase, $object_id, $snmp->timeout, $snmp->retries);
                    break;
                default: throw new Exception('SNMP Template not implemented yet');
            }
        }
        
        if ($result) {
            // retira 'STRING: ' do inicio do texto
            $result = trim(preg_replace('/^[^:]+: ?/','',$result));
            if($cache_var && $cache_ttl) {
                $cache->save($cache_var, $result, $cache_ttl);
            }
            return $result;
        } else {
            //throw new Exception("Sem resposta SNMP");
            return null;
        }
    }
    /**
     * Change SNMP value
     * The "type" parameter must be one of the following, depending on the type of variable to set on the SNMP host:
     * i    INTEGER
     * u    unsigned INTEGER
     * t    TIMETICKS
     * a    IPADDRESS
     * o    OBJID
     * s    STRING
     * x    HEX STRING
     * d    DECIMAL STRING
     * n    NULLOBJ
     * b    BITS 
     * @param Host $host
     * @param string $object_id
     * @param string $type
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public static function set($host, $object_id, $type, $value) {
        
        snmp_set_oid_output_format(self::$oid_format);
        
        $snmp = $host->snmpTemplate;
        
        if(is_null($type)) throw new Exception('type not set');

        if ($snmp instanceof SnmpTemplate) {
            switch ($snmp->version) {
                case "1":
                    $result = snmpset($host->ip, $snmp->community, $object_id, $type, $value, $snmp->timeout, $snmp->retries);
                    break;
                case "2":
                case "2c":
                    $result = snmp2_set($host->ip, $snmp->community, $object_id, $type, $value, $snmp->timeout, $snmp->retries);
                    break;
                case "3":
                    $result = snmp3_set($host->ip, $snmp->security_name, $snmp->security_level, $snmp->auth_protocol, $snmp->auth_passphrase, $snmp->priv_protocol, $snmp->priv_passphrase, $object_id, $type, $value, $snmp->timeout, $snmp->retries);
                    break;
                default: throw new Exception('SNMP Template not implemented yet');
            }
        }
        
        return $result;
    }
    
    public static function getOid($key) {
        $oid = self::$oids[$key];
        return $oid ? $oid : $key;
    }

    public static function getKey($oid) {
        $key = array_search($oid,self::$oids);
        return $key ? $key : $oid;
    }
}
?>
