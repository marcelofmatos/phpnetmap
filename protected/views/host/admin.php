<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Host', 'url'=>array('index')),
	array('label'=>'Create Host', 'url'=>array('create')),
        array('label'=>'Manage Host Faces', 'url' => array('hostFace/admin')),
        array('label'=>'Fill Missing MACs', 'url' => array('fillMacFromArp')),
);

Yii::app()->clientScript->registerScript('search', "
$('#host-search form').submit(function(){
	$('#host-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<button class="btn btn-link text-decoration-none text-body p-0 mb-3 d-inline-flex align-items-center gap-2 pnm-search-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#host-search" aria-expanded="false" aria-controls="host-search">
	<span>🔍 Advanced Search</span>
	<span class="pnm-search-chevron text-body-secondary" aria-hidden="true">▾</span>
</button>
<div class="collapse mb-3" id="host-search">
	<p class="note">
		You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
		or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
	</p>
	<?php $this->renderPartial('_search',array(
		'model'=>$model,
	)); ?>
</div>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id'=>'host-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
                array(
                    'name' => 'name',
                    'value' => 'CHtml::link($data->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data->name)), array("class"=>"view host-type ". $data->type))',
                    'type' => 'raw',
                    'visible' => '$data->id',
                ),
		'type',
		'mac',
		'ip',
                'snmpTemplate',
		array(
                    'class' => 'CButtonColumn',
                    'template' => '<span style="white-space:nowrap">{view} {update} {delete} {copy}</span>',
                    'buttons' => array(
                        'view' => array(
                            'url' => 'Yii::app()->controller->createUrl("host/viewByName",array("name"=>$data->name))',
                            ),
                        'copy' => array(
                            'label' => 'Copy Host',
                            'imageUrl' => Yii::app()->request->baseUrl.'/images/copy.gif',
                            'url' => '$this->grid->controller->createUrl("host/create", array("type" => $data->type, "snmp_template_id" => $data->snmp_template_id))',
                            'options' => array('class' => 'copy'),
                        ),
                    ),
                ),
        ),
));
?>
