<?php
/* @var $this UserController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
    'Users' => array('admin'),
    'Manage',
);

$this->menu = array(
    array('label' => 'Create User', 'url' => array('create')),
);
?>

<h1>Users</h1>

<?php if (Yii::app()->user->hasFlash('userDeleteBlocked')): ?>
<div class="info">
    <?php echo CHtml::encode(Yii::app()->user->getFlash('userDeleteBlocked')); ?>
</div>
<?php endif; ?>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id' => 'user-grid',
    'dataProvider' => $dataProvider,
    'columns' => array(
        'id',
        'username',
        'email',
        array(
            'class' => 'CButtonColumn',
            'template' => '<span style="white-space:nowrap">{update} {delete}</span>',
            'buttons' => array(
                'delete' => array(
                    'visible' => 'strcasecmp($data->username, "admin") !== 0',
                ),
            ),
        ),
    ),
)); ?>
