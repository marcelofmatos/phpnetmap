<?php
/* @var $this VlanController */
/* @var $data Vlan */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('tag')); ?>:</b>
	<?php echo CHtml::encode($data->tag); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('description')); ?>:</b>
	<?php echo CHtml::encode($data->description); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('background_color')); ?>:</b>
	<?php echo CHtml::encode($data->background_color); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('font_color')); ?>:</b>
	<?php echo CHtml::encode($data->font_color); ?>
	<br />


</div>