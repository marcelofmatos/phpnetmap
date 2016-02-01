<?php

/**
 * Cache with APC
 *
 * @author Marcelo Matos
 */
class CacheAPC{

    protected $ttl = 3; // seconds
    protected $prefix = 'PNM_';

    public function __construct($ttl = null, $prefix = null) {
        if( !ini_get( 'apc.enabled' ) ) {
            throw new Exception( 'APC disabled or not installed' );
        }
        if($ttl) {
            $this->ttl = $ttl;
        }
        if($prefix) {
            $this->prefix = $prefix;
        }
        return $this;
    }
    
    public function load($var) {
        $success = true;
        $result = apc_fetch($this->prefix . $var, $success);
        return ($success === false) ? null : $result;
    }

    public function save($var, $value, $ttl = null) {
        if( $ttl === null ) {
            $ttl = $this->ttl;
        }

        if( apc_store($this->prefix . $var, $value, $ttl) ) {
            return $value;
        }

        return null;
    }

    public function delete($var) {
        return apc_delete($this->prefix . $var);
    }

    public function deleteAll() {
        foreach( new APCIterator('user', '/^' . $this->_prefix . '/') as $var ) {
            apc_delete($var);
        }
    }

}

