<?php
/* @var $this McpTokenController */
/* @var $model McpToken */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form = $this->beginWidget('CActiveForm', array(
    'id' => 'mcp-token-form',
    'enableAjaxValidation' => false,
)); ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <?php echo $form->labelEx($model, 'description'); ?>
        <?php echo $form->textField($model, 'description', array('size' => 60, 'maxlength' => 255)); ?>
        <?php echo $form->error($model, 'description'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'expires_at'); ?>
        <?php echo $form->textField($model, 'expires_at', array('type' => 'date')); ?>
        <?php echo $form->error($model, 'expires_at'); ?>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton('➕ Create', array('class' => 'btn btn-primary')); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- form -->
