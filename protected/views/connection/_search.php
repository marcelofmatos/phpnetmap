<?php
/* @var $this ConnectionController */
/* @var $model Connection */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('TbActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'hostSrc'); ?>
		<?php echo $form->textField($model,'hostSrc'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'host_src_port'); ?>
		<?php echo $form->textField($model,'host_src_port'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'hostDst'); ?>
		<?php echo $form->textField($model,'hostDst'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'host_dst_port'); ?>
		<?php echo $form->textField($model,'host_dst_port'); ?>
	</div>

	<div class="row">
                <?php echo $form->labelEx($model, 'type'); ?>
                <?php echo $form->textField($model,'type'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->