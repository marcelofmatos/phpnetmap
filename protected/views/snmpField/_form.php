<?php
/* @var $this SnmpFieldController */
/* @var $model SnmpField */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'snmp-field-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'snmp_oid'); ?>
		<?php echo $form->textField($model,'snmp_oid',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'snmp_oid'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'key'); ?>
		<?php echo $form->textField($model,'key',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'key'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'label'); ?>
		<?php echo $form->textField($model,'label',array('size'=>60,'maxlength'=>100)); ?>
		<?php echo $form->error($model,'label'); ?>
	</div>
        
	<div class="row">
                <?php echo $form->labelEx($model, 'templates'); ?>
                <?php
                echo $form->dropDownList(
                        $model, 'templates', CHtml::listData(SnmpTemplate::model()->findAll(), 'id', 'name'), array('multiple' => 'multiple', 'size' => 15)
                        );
                ?>
                <?php echo $form->error($model, 'templates'); ?>

                <?php echo CHtml::link('Create template', array('/snmpTemplate/create')); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->