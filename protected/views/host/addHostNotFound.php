<?php
$this->breadcrumbs=array(
	'Hosts',
);

$this->menu=array(
	array('label'=>'Create Host ' . $name, 'url'=>array("create?name=$name&ip=$ip&mac=$mac")),
	array('label'=>'Manage Host', 'url'=>array('admin')),
);

?>

<h1>Host not found</h1>
<div>Host <b><?php echo ($name) ? $name : '' ?><?php echo ($ip) ? " (IP: $ip)" : "" ?><?php echo ($mac) ? " (MAC: $mac)" : "" ?></b> not found in database.</div>
