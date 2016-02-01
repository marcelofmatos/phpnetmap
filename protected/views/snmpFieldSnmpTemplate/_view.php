<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $data SnmpFieldSnmpTemplate */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('snmp_field_id')); ?>:</b>
	<?php echo CHtml::encode($data->snmp_field_id); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('snmp_template_id')); ?>:</b>
	<?php echo CHtml::encode($data->snmp_template_id); ?>
	<br />


</div>