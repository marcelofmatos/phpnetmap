<?php
/* @var $this McpTokenController */
/* @var $model McpToken */
/* @var $form CActiveForm */

$this->breadcrumbs = array(
    'MCP Tokens' => array('admin'),
    $model->description => array('admin'),
    'Update Mode',
);

$this->menu = array(
    array('label' => 'Create MCP Token', 'url' => array('create')),
    array('label' => 'Manage MCP Tokens', 'url' => array('admin')),
);
?>

<h1>Update Mode: <?php echo CHtml::encode($model->description); ?></h1>

<div class="form">

<?php $form = $this->beginWidget('CActiveForm', array(
    'id' => 'mcp-token-update-form',
    'enableAjaxValidation' => false,
)); ?>

    <p class="note">Only the mode can be changed after a token is created — the token value itself is never re-shown or re-derivable.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <label>Description:</label>
        <div><?php echo CHtml::encode($model->description); ?></div>
    </div>

    <div class="row">
        <label>Token Prefix:</label>
        <div><?php echo CHtml::encode($model->token_prefix); ?></div>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'mode'); ?>
        <?php echo $form->dropDownList($model, 'mode', array('readonly' => 'Read-only', 'readwrite' => 'Read-write')); ?>
        <?php echo $form->error($model, 'mode'); ?>
    </div>

    <div class="row buttons">
        <?php echo CHtml::submitButton('💾 Save', array('class' => 'btn btn-primary')); ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- form -->
