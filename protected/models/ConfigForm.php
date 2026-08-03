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
    public $mcpEnabled = false;
    public $authMode = 'yii';


    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules() {
        return array(
            // type and query are required
            array('adminEmail, translateCamTable, hostGatewayId, cache, cacheTtlDefault, cacheTtlCam, cacheTtlArp, cacheTtlGetSnmp, mcpEnabled, authMode', 'required'),
            array('authMode', 'in', 'range' => array('htpasswd', 'yii')),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'translateCamTable' => 'Translate CAM Table with Gateway ARP table (Be careful! Consults can be slow)',
            'hostGatewayId' => 'Gateway',
            'cacheTtlCam' => 'TTL for CAM table Cache (seconds)',
            'cacheTtlArp' => 'TTL for ARP table Cache (seconds)',
            'cacheTtlGetSnmp' => 'TTL for SNMP Get Cache (seconds)',
            'cacheTtlDefault' => 'TTL Default Cache (seconds)',
            'mcpEnabled' => 'Enable MCP Server (/mcp)',
            'authMode' => 'Authentication Mode',
        );
    }

    /**
     * Path to the marker file .htaccess itself checks for (plain file-exists
     * test, no content read) to decide whether to enforce HTTP Basic Auth.
     * Lives alongside params.ini in the same persistent volume.
     */
    private static function getAuthModeMarkerPath() {
        return dirname(PARAMS_INI_FILE_PATH) . '/.auth_mode_htpasswd';
    }

    /**
     * True until save() has been called at least once. load() only ever
     * touch()es an empty placeholder file into existence when missing —
     * save() is the only code path that writes real content — so a
     * missing or empty file reliably means setup was never completed.
     * Used by SiteController to gate the first-run welcome wizard.
     * @return bool
     */
    public static function isUnconfigured() {
        return !is_file(PARAMS_INI_FILE_PATH) || filesize(PARAMS_INI_FILE_PATH) === 0;
    }

    public function load() {
        if(!is_readable(PARAMS_INI_FILE_PATH)) {
            try {
                touch(PARAMS_INI_FILE_PATH);
            } catch(Exception $e) {
                throw new Exception("Config file is not readable: ". PARAMS_INI_FILE_PATH);
            }
        }
        foreach(@parse_ini_file(PARAMS_INI_FILE_PATH) as $key => $val) {
            $this->$key = $val;
        }
        // parse_ini_file always returns quoted values as strings, so coerce
        // back to a real bool (mirrors the (bool) cast already applied in save()).
        $this->mcpEnabled = (bool) $this->mcpEnabled;

        // authMode is deliberately NOT stored in params.ini — it's derived
        // straight from the same marker file .htaccess itself reads, so this
        // form can never show a value that isn't what's actually enforced.
        $this->authMode = file_exists(self::getAuthModeMarkerPath()) ? 'htpasswd' : 'yii';
    }

    public function save() {
        if(!is_writeable(PARAMS_INI_FILE_PATH)) {
            throw new Exception("Config file is not writable: ". PARAMS_INI_FILE_PATH);
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
            'mcpEnabled' => (bool) $this->mcpEnabled,
        );

        foreach($res as $key => $val) {
            $configToIni[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
        }

        file_put_contents(PARAMS_INI_FILE_PATH, implode("\r\n", $configToIni));

        // authMode isn't persisted above (see load()) — sync the marker file
        // .htaccess reads directly instead, so Apache's enforced auth mode
        // always matches exactly what was just saved here, with no drift
        // possible between what the UI shows and what's actually enforced.
        $markerPath = self::getAuthModeMarkerPath();
        if ($this->authMode === 'htpasswd') {
            if (!touch($markerPath)) {
                throw new Exception("Could not create auth mode marker file: " . $markerPath);
            }
        } elseif (file_exists($markerPath)) {
            if (!unlink($markerPath)) {
                throw new Exception("Could not remove auth mode marker file: " . $markerPath);
            }
        }

        return true;
    }

}
