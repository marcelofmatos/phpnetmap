<?php
/* @var $this SnmpFieldController */
/* @var $model SnmpField */

$this->breadcrumbs=array(
	'Snmp Fields'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List SnmpField', 'url'=>array('index')),
	array('label'=>'Manage SnmpField', 'url'=>array('admin')),
);
?>

<h1>Create SnmpField</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>