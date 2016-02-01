<?php
/* @var $this ConnectionController */
/* @var $data Connection */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('host_src_id')); ?>:</b>
	<?php echo CHtml::encode($data->host_src_id); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('host_src_port')); ?>:</b>
	<?php echo CHtml::encode($data->host_src_port); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('host_dst_id')); ?>:</b>
	<?php echo CHtml::encode($data->host_dst_id); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('host_dst_port')); ?>:</b>
	<?php echo CHtml::encode($data->host_dst_port); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('type')); ?>:</b>
	<?php echo CHtml::encode($data->type); ?>
	<br />


</div>