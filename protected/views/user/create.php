<?php
/* @var $this UserController */
/* @var $model User */

$this->breadcrumbs = array(
    'Users' => array('admin'),
    'Create',
);

$this->menu = array(
    array('label' => 'Manage Users', 'url' => array('admin')),
);
?>

<h1>Create User</h1>

<?php echo $this->renderPartial('_form', array('model' => $model)); ?>
