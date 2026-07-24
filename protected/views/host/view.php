<?php
/* @var $this HostController */
/* @var $model Host */

$this->breadcrumbs=array(
	'Hosts'=>array('admin'),
	$model->name,
);

$this->pageTitle = $model ." ". $this->pageTitle;

?>
<?php $this->renderPartial('/host/_header', array('model' => $model)); ?>
<?php

$this->renderPartial('/map/_view', array(
    'height' => 300,
    'width' => 800,
    'navigation' => true,
    'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
));

$this->renderPartial('/host/_ports', array('model' => $model));

$this->widget('bootstrap.widgets.TbTabs', array(
    // 'type' não precisa ser passado — o default de TbTabs já é 'tabs' (ver
    // protected/extensions/bootstrap/widgets/TbTabs.php:19).
    'tabs' => array(
        array(
            'label' => 'Visão Geral',
            'active' => true,
            'view' => '/host/_tabOverview',
        ),
        array(
            'label' => 'Documentação',
            'view' => '/host/_tabDocs',
        ),
        array(
            'label' => 'Histórico',
            'view' => '/host/_tabHistory',
        ),
        array(
            'label' => 'Diagnóstico',
            'view' => '/host/_tabDiagnostics',
        ),
    ),
    'viewData' => array('model' => $model),
));

?>
