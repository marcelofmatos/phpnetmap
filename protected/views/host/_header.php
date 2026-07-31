<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="host-header">
    <div class="host-header-identity">
        <span class="view host-type <?php echo CHtml::encode($model->type); ?>"><?php echo CHtml::encode($model->name); ?></span>
        <span class="host-header-ip"><?php echo CHtml::encode($model->ip); ?></span>
    </div>
</div>
