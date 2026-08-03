<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */

$this->breadcrumbs=array(
	'Snmp Templates'=>array('admin'),
	'Manage',
);

$this->menu=array(
    array('label'=>'List SnmpTemplate', 'url'=>array('index')),
    array('label'=>'Create SnmpTemplate', 'url'=>array('create')),
    array('label'=>'Manage Snmp Fields', 'url'=>array('snmpField/admin')),
);

Yii::app()->clientScript->registerScript('search', "
$('#snmp-template-search form').submit(function(){
	$('#snmp-template-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<div class="card mb-3">
	<div class="card-header p-0">
		<button class="btn btn-link text-decoration-none text-body w-100 text-start d-flex justify-content-between align-items-center px-3 py-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#snmp-template-search" aria-expanded="false" aria-controls="snmp-template-search">
			<span>🔍 Advanced Search</span>
			<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
		</button>
	</div>
	<div class="collapse" id="snmp-template-search">
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
			'template' => '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
			'buttons' => array(
				// Não copia community/auth_passphrase/priv_passphrase
				// (credenciais) nem name — só config não sensível.
				'copy' => array(
					'label' => 'Copy SnmpTemplate',
					'imageUrl' => Yii::app()->request->baseUrl.'/images/copy.gif',
					'url' => '$this->grid->controller->createUrl("snmpTemplate/create", array("version" => $data->version, "security_name" => $data->security_name, "security_level" => $data->security_level, "auth_protocol" => $data->auth_protocol, "priv_protocol" => $data->priv_protocol, "timeout" => $data->timeout, "retries" => $data->retries))',
					'options' => array('class' => 'copy'),
				),
			),
		),
	),
)); ?>
