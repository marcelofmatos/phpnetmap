<?php
/* @var $this VlanController */
/* @var $model Vlan */

$this->breadcrumbs=array(
	'Vlans'=>array('index'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

$this->menu=array(
	array('label'=>'List Vlan', 'url'=>array('index')),
	array('label'=>'Create Vlan', 'url'=>array('create')),
	array('label'=>'Update Vlan', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Vlan', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Vlan', 'url'=>array('admin')),
);
?>

<h1>View <?php echo $model; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'tag',
		'name',
		'description',
		array(
                    'name' => 'colors',
                    'type' => 'raw',
                    'value' => CHtml::tag("span",array("class" => "vlanlabel", "style"=>"width:200px; background-color:#{$model->background_color};color:#{$model->font_color}"),$model),
                ),
	),
)); ?>
