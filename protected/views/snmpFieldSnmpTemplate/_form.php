<?php
/* @var $this SnmpFieldSnmpTemplateController */
/* @var $model SnmpFieldSnmpTemplate */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'snmp-field-snmp-template-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'snmp_template_id'); ?>
		<?php //echo $form->textField($model,'snmp_template_id'); ?>
                <?php
                echo $form->dropDownList(
                        $model, 'snmp_template_id', CHtml::listData(SnmpTemplate::model()->findAll(), 'id', 'name'), array('empty' => ''));
                ?>
		<?php echo $form->error($model,'snmp_template_id'); ?>
	</div>
        
	<div class="row">
		<?php echo $form->labelEx($model,'snmp_field_id'); ?>
		<?php //echo $form->textField($model,'snmp_field_id'); ?>
                <?php
                echo $form->dropDownList(
                        $model, 'snmp_field_id', CHtml::listData(SnmpField::model()->findAll(), 'id', 'label'), array('empty' => ''));
                ?>
		<?php echo $form->error($model,'snmp_field_id'); ?>
	</div>


	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->