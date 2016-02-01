<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'snmp_field_id'); ?>
		<?php echo $form->textField($model,'snmp_field_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'snmp_template_id'); ?>
		<?php echo $form->textField($model,'snmp_template_id'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->