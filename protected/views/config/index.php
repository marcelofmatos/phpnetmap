<?php

$this->breadcrumbs=array(
	'Configuration'=>array('index')
);

?>
<div class="form">
    <?php if(Yii::app()->user->hasFlash('config')):?>
    <div class="info">
        <?php echo Yii::app()->user->getFlash('config'); ?>
    </div>
    <?php endif; ?>
    
    <?php echo $form ?>
</div>