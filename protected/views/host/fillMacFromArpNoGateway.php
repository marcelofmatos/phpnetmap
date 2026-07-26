<?php
/* @var $this HostController */

$this->breadcrumbs = array(
	'Hosts' => array('admin'),
	'Fill Missing MACs',
);

$this->menu = array(
	array('label' => 'Manage Host', 'url' => array('admin')),
);
?>

<h1>Fill Missing MACs from ARP Table</h1>
<p>No gateway with an SNMP template configured. Set one on the <?php echo CHtml::link('Configuration page', array('/config/index')); ?> first.</p>
