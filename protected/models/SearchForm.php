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
            'camtable_mac' => 'MAC on switches CAM tables',
            'camtable_vlan' => 'VLAN tag on switches CAM tables',
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
     * Logs in the user using the given username and password in the model.
     * @return boolean whether login is successful
     */
    public function searchResult() {
        $result = array();
        $hosts = Host::model()->findAllByAttributes(array('id' => $this->hosts));

        switch ($this->type) {
            case "camtable_mac":
                $search_table = 'camtable';
                $search_field = 'mac';
                break;
            case "camtable_vlan":
                $search_table = 'camtable';
                $search_field = 'vlan_tag';
                break;
        }

        if (count($hosts) && $search_table == 'camtable') {
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

                        if ($this->exclude_link_ports && !empty($hostDst))
                            continue;

                        $row['host'] = $host;
                        $row['hostDst'] = $hostDst;
                        $row['vlan'] = Vlan::model()->findByAttributes(array('tag' => $row['vlan_tag']));
                        $result[] = $row;
                    }
                }
            }
        }
        
        return $result;
    }

}
