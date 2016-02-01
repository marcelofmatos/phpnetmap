<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Templates'=>array('index'),
	'Manage',
);

$this->menu=array(
    array('label'=>'List SnmpTemplate', 'url'=>array('index')),
    array('label'=>'Create SnmpTemplate', 'url'=>array('create')),
    array('label'=>'Manage Snmp Fields', 'url'=>array('snmpField/admin')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#snmp-template-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>
<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id'=>'snmp-template-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'name',
		'version',
		'community',
		'security_name',
		'security_level',
		/*
		'auth_protocol',
		'auth_passphrase',
		'priv_protocol',
		'priv_passphrase',
		'timeout',
		'retries',
		*/
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
