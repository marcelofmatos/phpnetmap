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
        return array(
            'camtable:mac' => 'MAC on switches CAM tables',
            'camtable:vlan_tag' => 'VLAN tag on switches CAM tables',
            'arptable:mac' => 'MAC on hosts ARP tables',
            'arptable:ip' => 'IP on hosts ARP tables',
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
                            $row['hostDst'] = $hostDst;
                            $row['vlan'] = Vlan::model()->findByAttributes(array('tag' => $row['vlan_tag']));
                            $result[] = $row;
                        }
                    }
                }
            
            } else if ($search_table == 'arptable') {
                foreach ($hosts as $host) {

                    $host->loadArpTable();

                    foreach ($host->arp_table as $mac => $ip) {
var_dump($$search_field);
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
            }
            
        }
        
        return $result;
    }

}
