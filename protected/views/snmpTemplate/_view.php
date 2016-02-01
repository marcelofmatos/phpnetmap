<?php
/* @var $this SnmpTemplateController */
/* @var $data SnmpTemplate */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('version')); ?>:</b>
	<?php echo CHtml::encode($data->version); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('community')); ?>:</b>
	<?php echo CHtml::encode($data->community); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('security_name')); ?>:</b>
	<?php echo CHtml::encode($data->security_name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('security_level')); ?>:</b>
	<?php echo CHtml::encode($data->security_level); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('auth_protocol')); ?>:</b>
	<?php echo CHtml::encode($data->auth_protocol); ?>
	<br />

	<?php /*
	<b><?php echo CHtml::encode($data->getAttributeLabel('auth_passphrase')); ?>:</b>
	<?php echo CHtml::encode($data->auth_passphrase); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('priv_protocol')); ?>:</b>
	<?php echo CHtml::encode($data->priv_protocol); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('priv_passphrase')); ?>:</b>
	<?php echo CHtml::encode($data->priv_passphrase); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('timeout')); ?>:</b>
	<?php echo CHtml::encode($data->timeout); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('retries')); ?>:</b>
	<?php echo CHtml::encode($data->retries); ?>
	<br />

	*/ ?>

</div>