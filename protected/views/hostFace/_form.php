<?php
/* @var $this HostFaceController */
/* @var $model HostFace */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'host-face-form',
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
		<?php echo $form->labelEx($model,'svg'); ?>
		<?php echo $form->textArea($model,'svg',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'svg'); ?>
	</div>
        
	<div class="row">
		<?php echo $form->labelEx($model,'hosts'); ?>
                <?php echo $form->dropDownList(
                        $model,
                        'hosts', 
                        CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'), 
                        array(
                            'empty'=>'',
                            'multiple'=>'multiple',
                            'size' => 15,
                            )); 
                ?>
		<?php echo $form->error($model,'hosts'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->