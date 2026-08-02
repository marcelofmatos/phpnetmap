<?php

$this->breadcrumbs=array(
	'Configuration'
);

$wizard = isset($_GET['wizard']);

?>
<?php if($wizard): ?>
<div class="pnm-steps">
    <span class="pnm-step is-done"><span class="pnm-step-num">1</span> Welcome</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step is-active"><span class="pnm-step-num">2</span> Configuration</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step"><span class="pnm-step-num">3</span> Tour</span>
</div>
<?php endif; ?>
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