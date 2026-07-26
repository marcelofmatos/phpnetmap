<?php
$name = isset($name) ? $name : null;
$ip = isset($ip) ? $ip : null;
$mac = isset($mac) ? $mac : null;
$type = isset($type) ? $type : null;

$this->breadcrumbs=array(
	'Hosts',
);

$createParams = array('create');
if ($name) { $createParams['name'] = $name; }
if ($ip) { $createParams['ip'] = $ip; }
if ($mac) { $createParams['mac'] = $mac; }
if ($type) { $createParams['type'] = $type; }

$this->menu=array(
	array('label'=>'Create Host', 'url'=>$createParams),
	array('label'=>'Manage Host', 'url'=>array('admin')),
);

?>

<h1>Host not found</h1>
<div>Host <b><?php echo ($name) ? CHtml::encode($name) : '' ?><?php echo ($ip) ? ' (IP: ' . CHtml::encode($ip) . ')' : '' ?><?php echo ($mac) ? ' (MAC: ' . CHtml::encode($mac) . ')' : '' ?></b> not found in database.</div>
