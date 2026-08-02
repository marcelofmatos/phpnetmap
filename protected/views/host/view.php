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

<div class="card">
    <div class="card-body">
        <?php $this->renderPartial('/map/_view', array(
            'height' => 300,
            'navigation' => true,
            'dataUrl' => Yii::app()->createUrl('/map/listHosts?hostId=' . $model->id),
        )); ?>
    </div>
</div>

<div class="card">
    <?php $this->renderPartial('/host/_ports', array('model' => $model)); ?>
</div>

<div class="card">
    <?php $this->widget('bootstrap.widgets.TbTabs', array(
        // 'type' não precisa ser passado — o default de TbTabs já é 'tabs' (ver
        // protected/extensions/bootstrap/widgets/TbTabs.php:19).
        'tabs' => array(
            array(
                'label' => 'Overview',
                'active' => true,
                'view' => '/host/_tabOverview',
            ),
            array(
                'label' => 'Documentation',
                'view' => '/host/_tabDocs',
            ),
            array(
                'label' => 'History',
                'view' => '/host/_tabHistory',
            ),
            array(
                'label' => 'Diagnostics',
                'view' => '/host/_tabDiagnostics',
            ),
        ),
        'viewData' => array('model' => $model),
    )); ?>
</div>
