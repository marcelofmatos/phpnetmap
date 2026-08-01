<?php
/* @var $this UserController */
/* @var $model User */

$this->breadcrumbs = array(
    'Users' => array('admin'),
    $model->username => array('admin'),
    'Update',
);

$this->menu = array(
    array('label' => 'Create User', 'url' => array('create')),
    array('label' => 'Manage Users', 'url' => array('admin')),
);
?>

<h1>Update User <?php echo CHtml::encode($model->username); ?></h1>

<?php echo $this->renderPartial('_form', array('model' => $model)); ?>
