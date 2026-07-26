<?php
/* @var $this HostController */
/* @var $updated int */
/* @var $total int */

$this->breadcrumbs = array(
	'Hosts' => array('admin'),
	'Fill Missing MACs',
);

$this->menu = array(
	array('label' => 'Manage Host', 'url' => array('admin')),
);
?>

<h1>Fill Missing MACs from ARP Table</h1>
<p><?php echo $updated; ?> of <?php echo $total; ?> host(s) updated with a MAC from the gateway's ARP table.</p>
<p><?php echo CHtml::link('Back to Manage Host', array('admin')); ?></p>
