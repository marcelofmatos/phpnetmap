<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="host-header">
    <div class="host-header-identity">
        <span class="view host-type <?php echo CHtml::encode($model->type); ?>"><?php echo CHtml::encode($model->name); ?></span>
        <span class="host-header-ip"><?php echo CHtml::encode($model->ip); ?></span>
    </div>
    <div class="btn-group host-header-actions">
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
            Actions
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu pull-right">
            <li><?php echo CHtml::link('Web Config', 'http://' . $model->ip, array('target' => '_blank')); ?></li>
            <li><?php echo CHtml::link('Update Host', array('update', 'id' => $model->id)); ?></li>
            <li class="divider"></li>
            <li><?php echo CHtml::link('Delete Host', '#', array(
                'submit' => array('delete', 'id' => $model->id),
                'confirm' => 'Are you sure you want to delete this item?',
            )); ?></li>
        </ul>
    </div>
</div>
