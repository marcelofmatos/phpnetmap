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
$('#snmp-field-search form').submit(function(){
	$('#snmp-field-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<button class="btn btn-link text-decoration-none text-body p-0 mb-3 d-inline-flex align-items-center gap-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#snmp-field-search" aria-expanded="false" aria-controls="snmp-field-search">
	<span>🔍 Advanced Search</span>
	<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
</button>
<div class="collapse mb-3" id="snmp-field-search">
	<p class="note">
		You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
		or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
	</p>
	<?php $this->renderPartial('_search',array(
		'model'=>$model,
	)); ?>
</div>

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
			'template' => '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
			'buttons' => array(
				'copy' => array(
					'label' => 'Copy SnmpField',
					'imageUrl' => Yii::app()->request->baseUrl.'/images/copy.gif',
					'url' => '$this->grid->controller->createUrl("snmpField/create", array("snmp_oid" => $data->snmp_oid, "key" => $data->key, "label" => $data->label))',
					'options' => array('class' => 'copy'),
				),
			),
		),
	),
)); ?>
