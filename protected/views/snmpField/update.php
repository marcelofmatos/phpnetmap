<?php
/* @var $this SnmpFieldController */
/* @var $model SnmpField */

$this->breadcrumbs=array(
	'Snmp Fields'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List SnmpField', 'url'=>array('index')),
	array('label'=>'Create SnmpField', 'url'=>array('create')),
	array('label'=>'View SnmpField', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage SnmpField', 'url'=>array('admin')),
);
?>

<h1>Update SnmpField <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>