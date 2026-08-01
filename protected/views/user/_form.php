<?php
/* @var $this UserController */
/* @var $model User */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form = $this->beginWidget('CActiveForm', array(
    'id' => 'user-form',
    'enableAjaxValidation' => false,
)); ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <?php echo $form->labelEx($model, 'username'); ?>
        <?php echo $form->textField($model, 'username', array('size' => 60, 'maxlength' => 128)); ?>
        <?php echo $form->error($model, 'username'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'email'); ?>
        <?php echo $form->textField($model, 'email', array('size' => 60, 'maxlength' => 128)); ?>
        <?php echo $form->error($model, 'email'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'password'); ?>
        <?php echo $form->passwordField($model, 'password', array('size' => 60, 'maxlength' => 128, 'value' => '')); ?>
        <?php echo $form->error($model, 'password'); ?>
        <p class="hint">
            <?php echo $model->isNewRecord ? 'Required.' : 'Leave blank to keep the current password.'; ?>
        </p>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton($model->isNewRecord ? '➕ Create' : '💾 Save', array('class' => 'btn btn-primary')); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- form -->
