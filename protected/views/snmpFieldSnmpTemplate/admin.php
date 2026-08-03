<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Field Snmp Templates'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List SnmpFieldSnmpTemplate', 'url'=>array('index')),
	array('label'=>'Create SnmpFieldSnmpTemplate', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('#snmp-field-snmp-template-search form').submit(function(){
	$('#snmp-field-snmp-template-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<div class="card mb-3">
	<div class="card-header p-0">
		<button class="btn btn-link text-decoration-none text-body w-100 text-start d-flex justify-content-between align-items-center px-3 py-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#snmp-field-snmp-template-search" aria-expanded="false" aria-controls="snmp-field-snmp-template-search">
			<span>🔍 Advanced Search</span>
			<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
		</button>
	</div>
	<div class="collapse" id="snmp-field-snmp-template-search">
		<div class="card-body">
			<p class="note">
				You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
				or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
			</p>
			<?php $this->renderPartial('_search',array(
				'model'=>$model,
			)); ?>
		</div>
	</div>
</div>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id'=>'snmp-field-snmp-template-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'snmp_field_id',
		'snmp_template_id',
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
