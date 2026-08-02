<?php

$this->breadcrumbs=array(
	'Configuration'
);

$wizard = isset($_GET['wizard']);

?>
<div class="form">
    <?php if($wizard): ?>
    <div class="info">
        <strong>Step 2 of 3 &mdash; Configuration.</strong>
        The defaults are fine to start with. Save to continue to the guided tour, or
        <?php echo CHtml::link('skip for now', array('/map/index')); ?>.
    </div>
    <?php endif; ?>

    <?php if(Yii::app()->user->hasFlash('config')):?>
    <div class="info">
        <?php echo Yii::app()->user->getFlash('config'); ?>
    </div>
    <?php endif; ?>
    
    <?php echo $form ?>
</div>