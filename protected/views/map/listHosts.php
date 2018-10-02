<?php

$array2json = array();

if ($hosts[0] instanceof Host) {
    $array2json['rootHostName'] = $hosts[0]->name;
}

// nodes
foreach ($hosts as $host) {

    if ($host->type == Host::TYPE_SUPPOSED_HUB) {
        $params = array(
            'name' => $hosts[0]->name,
            '#' => $host->name
        );
    } else if(is_null($host->id)) {
        $params = array(
            //'name' => ($host->name != $host->mac) ? $host->name : null, 
            'ip' => $host->ip,
            'mac' => $host->mac
        );
    } else {
        $params = array('name' => $host->name);
    }
    
    $array2json['nodes'][$host->name] = $host;
    $array2json['nodes'][$host->name]['group'] = 1; // TODO: criar grupos de hosts
    $array2json['nodes'][$host->name]['href'] = Yii::app()->createUrl('host/viewByName', $params); 

}

// links
foreach ($hostConnections as $connection) {
    $array2json['links'][] = array(
        'source' => $connection->hostSrc->name,
        'target' => $connection->hostDst->name,
        'srcPort' => $connection->host_src_port,
        'type' => $connection->type,
        'vlan' => $connection->vlan,
        );
}

echo CJSON::encode($array2json);
