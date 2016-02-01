<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */
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
		<?php echo $form->label($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>50,'maxlength'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'version'); ?>
		<?php echo $form->textField($model,'version',array('size'=>3,'maxlength'=>3)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'community'); ?>
		<?php echo $form->textField($model,'community',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'security_name'); ?>
		<?php echo $form->textField($model,'security_name',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'security_level'); ?>
		<?php echo $form->textField($model,'security_level',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'auth_protocol'); ?>
		<?php echo $form->textField($model,'auth_protocol',array('size'=>50,'maxlength'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'auth_passphrase'); ?>
		<?php echo $form->textField($model,'auth_passphrase',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'priv_protocol'); ?>
		<?php echo $form->textField($model,'priv_protocol',array('size'=>50,'maxlength'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'priv_passphrase'); ?>
		<?php echo $form->textField($model,'priv_passphrase',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'timeout'); ?>
		<?php echo $form->textField($model,'timeout'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'retries'); ?>
		<?php echo $form->textField($model,'retries'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->