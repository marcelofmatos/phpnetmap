<?php
/* @var $this SnmpFieldController */
/* @var $model SnmpField */

$this->breadcrumbs=array(
	'Snmp Fields'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List SnmpField', 'url'=>array('index')),
	array('label'=>'Create SnmpField', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#snmp-field-grid').yiiGridView('update', {
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
	'id'=>'snmp-field-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'snmp_oid',
		'key',
		'label',
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
