<?php
$tag = isset($tag) ? $tag : null;

$this->breadcrumbs=array(
	'Vlans'=>array('index'),
);

$createParams = array('create');
if ($tag) { $createParams['tag'] = $tag; }

$this->menu=array(
	array('label'=>'Create Vlan', 'url'=>$createParams),
	array('label'=>'List Vlan', 'url'=>array('index')),
	array('label'=>'Manage Vlan', 'url'=>array('admin')),
);

?>

<h1>Vlan not found</h1>
<div>Vlan <b><?php echo ($tag) ? 'tag ' . CHtml::encode($tag) : '' ?></b> not found in database.</div>
