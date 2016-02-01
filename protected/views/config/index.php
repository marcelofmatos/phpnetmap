<div class="form">
    <h1><?php echo Yii::t('app', 'Configuration'); ?></h1>
 
    <?php if(Yii::app()->user->hasFlash('config')):?>
    <div class="info">
        <?php echo Yii::app()->user->getFlash('config'); ?>
    </div>
    <?php endif; ?>
    
    <?php echo $form ?>
</div>