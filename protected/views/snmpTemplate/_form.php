<?php
/* @var $this SnmpTemplateController */
/* @var $model SnmpTemplate */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'snmp-template-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'version'); ?>
		<?php echo $form->textField($model,'version',array('size'=>3,'maxlength'=>3)); ?>
		<?php echo $form->error($model,'version'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'community'); ?>
		<?php echo $form->textField($model,'community',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'community'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'security_name'); ?>
		<?php echo $form->textField($model,'security_name',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'security_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'security_level'); ?>
		<?php echo $form->textField($model,'security_level',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'security_level'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'auth_protocol'); ?>
		<?php echo $form->textField($model,'auth_protocol',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'auth_protocol'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'auth_passphrase'); ?>
		<?php echo $form->textField($model,'auth_passphrase',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'auth_passphrase'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'priv_protocol'); ?>
		<?php echo $form->textField($model,'priv_protocol',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'priv_protocol'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'priv_passphrase'); ?>
		<?php echo $form->textField($model,'priv_passphrase',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'priv_passphrase'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'timeout'); ?>
		<?php echo $form->textField($model,'timeout'); ?>
		<?php echo $form->error($model,'timeout'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'retries'); ?>
		<?php echo $form->textField($model,'retries'); ?>
		<?php echo $form->error($model,'retries'); ?>
	</div>
        
	<div class="row">
                <?php echo $form->labelEx($model, 'fields'); ?>
                <?php
                echo $form->dropDownList(
                        $model, 'fields', CHtml::listData(SnmpField::model()->findAll(), 'id', 'label'), array('multiple' => 'multiple', 'size' => 15)
                        );
                ?>
                <?php echo $form->error($model, 'fields'); ?>

                <?php echo CHtml::link('Create field', array('/SnmpField/create')); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->