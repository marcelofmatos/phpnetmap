<?php

/**
 * SearchForm class.
 * SearchForm is the data structure for search objects on Host list
 */
class SearchForm extends CFormModel {

    public $type;
    public $hosts;
    public $query;
    public $exact_match;
    public $exclude_link_ports;
    public $showErrorSummary = true;

    public static function getSearchTypes() {
        /**
         * Search types for form
         *  <search_table>:<search_field>
         * where:
         * search_table: list type to search
         * search_field: reference for field to search with $this->query
         */
        return array(
            'camtable:mac' => 'MAC on switches CAM tables',
            'camtable:vlan_tag' => 'VLAN tag on switches CAM tables',
            'arptable:mac' => 'MAC on hosts ARP tables',
            'arptable:ip' => 'IP on hosts ARP tables',
            'portsinfo:ifAlias' => 'Description Alias on hosts interfaces',
        );
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules() {
        return array(
            // type and query are required
            array('type, hosts, query, exact_match, exclude_link_ports', 'required'),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'type' => 'Type',
        );
    }

    /**
     * Consults ARP or CAM table for search query
     * @return array result for list
     */
    public function searchResult() {
        $result = array();
       
        $hosts = Host::model()->findAllByAttributes(array('id' => $this->hosts));
        
        list($search_table,$search_field) = explode(':', $this->type);

        if (count($hosts)) {
            if($search_table == 'camtable') {
                /* @var $host Host */                
                foreach ($hosts as $host) {

                    $host->loadCamTable();

                    foreach ($host->cam_table as $row) {

                        if ($this->exact_match) {
                                $found = $row[$search_field] == $this->query;
                        } else {
                                $found = strpos($row[$search_field], $this->query) !== FALSE;
                        }

                        if ($found) {

                            $hostDst = $host->getHostOnPort($row['port']);

                            if ($this->exclude_link_ports && !empty($hostDst)) {
                                continue;
                            }

                            $row['host'] = $host;
                            $row['hostDst'] = $hostDst instanceof Host ? $hostDst : Host::model()->findByAttributes(array('mac' => $row['mac']));
                            $row['vlan'] = Vlan::model()->findByAttributes(array('tag' => $row['vlan_tag']));
                            $result[] = $row;
                        }
                    }
                }
            
            } else if ($search_table == 'arptable') {
                foreach ($hosts as $host) {

                    $host->loadArpTable();

                    foreach ($host->arp_table as $mac => $ip) {

                        if ($this->exact_match) {
                                $found = $$search_field == $this->query;
                        } else {
                                $found = strpos($$search_field, $this->query) !== FALSE;
                        }

                        if ($found) {
                            $row['host'] = $host;
                            $row['mac'] = $mac;
                            $row['ip'] = $ip;
                            $row['hostDst'] = Host::model()->findByAttributes(array('mac' => $mac));
                            $result[] = $row;
                        }
                    }
                }
                
            } else if ($search_table == 'portsinfo') {
                foreach ($hosts as $host) {

                    $host->loadPortsInfo(array($search_field));

                    foreach ($host->ports as $index => $port) {
                        
                        $hostDst = $host->getHostOnPort($index);

                        if ($this->exact_match) {
                                $found = $port[$search_field] == $this->query;
                        } else {
                                $found = strpos($port[$search_field], $this->query) !== FALSE;
                        }

                        if ($found) {
                            $row['host'] = $host;
                            $row['hostDst'] = $hostDst;
                            $row['port'] = $index;
                            $row['info'] = $port[$search_field];
                            $result[] = $row;
                        }
                    }
                }
            }
            
        }
        
        return $result;
    }

}
