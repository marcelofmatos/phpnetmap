<?php
/* @var $this VlanController */
/* @var $model Vlan */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'vlan-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'tag'); ?>
		<?php echo $form->textField($model,'tag'); ?>
		<?php echo $form->error($model,'tag'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name'); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'description'); ?>
		<?php echo $form->textField($model,'description'); ?>
		<?php echo $form->error($model,'description'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'background_color'); ?>
		<?php $this->widget('application.extensions.colorpicker.EColorPicker', 
                  array(
                        'name'=>'Vlan[background_color]',
                        'value'=> ($model->background_color) ? $model->background_color : 'ffffff',
                       )
                 ); ?>
		<?php echo $form->error($model,'background_color'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'font_color'); ?>
		<?php $this->widget('application.extensions.colorpicker.EColorPicker', 
                  array(
                        'name'=>'Vlan[font_color]',
                        'value'=> ($model->font_color) ? $model->font_color : '000000',
                       )
                 ); ?>
		<?php echo $form->error($model,'font_color'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->