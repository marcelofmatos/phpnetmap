<?php

/**
 * ConfigForm class.
 * ConfigForm is the data structure for custom app configs
 * 
 * See protected/config/params.php
 */

class ConfigForm extends CFormModel {

    public $adminEmail = 'root@localhost';
    public $translateCamTable = true;
    public $hostGatewayId = 1;
    public $cache = true;
    public $cacheTtlDefault = 2;
    public $cacheTtlCam = 2;
    public $cacheTtlArp = 20;
    public $cacheTtlGetSnmp = 10;
    public $showErrorSummary = true;


    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules() {
        return array(
            // type and query are required
            array('adminEmail, translateCamTable, hostGatewayId, cache, cacheTtlDefault, cacheTtlCam, cacheTtlArp, cacheTtlGetSnmp', 'required'),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'translateCamTable' => 'Translate CAM Table with Gateway ARP table (SLOW)',
            'hostGatewayId' => 'Gateway',
            'cacheTtlCam' => 'Cache TTL for Cache CAM table',
            'cacheTtlArp' => 'Cache TTL for Cache ARP table',
            'cacheTtlGetSnmp' => 'Cache TTL for SNMP Get consults',
            'cacheTtlDefault' => 'Cache TTL Default',
        );
    }


    public function load() {
        if(!is_readable(PARAMS_INI_FILE_PATH)) {
            throw new Exception("Error accessing ". PARAMS_INI_FILE_PATH);
        }
        foreach(@parse_ini_file(PARAMS_INI_FILE_PATH) as $key => $val) {
            $this->$key = $val;
        }
    }
    
    public function save() {
        if(!is_writeable(PARAMS_INI_FILE_PATH)) {
            throw new Exception("Error accessing ". PARAMS_INI_FILE_PATH);
        }
        
        $res = array(
            'adminEmail' => (string) $this->adminEmail,
            'translateCamTable' => (bool) $this->translateCamTable,
            'hostGatewayId' => (int) $this->hostGatewayId,
            'cache' => (bool) $this->cache,
            'cacheTtlDefault' => (int) $this->cacheTtlDefault,
            'cacheTtlCam' => (int) $this->cacheTtlCam,
            'cacheTtlArp' => (int) $this->cacheTtlArp,
            'cacheTtlGetSnmp' => (int) $this->cacheTtlGetSnmp,
        );
        
        foreach($res as $key => $val) {
            $configToIni[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
        }
        
        file_put_contents(PARAMS_INI_FILE_PATH, implode("\r\n", $configToIni));
    }

}
