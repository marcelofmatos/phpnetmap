<?php /* @var $this Controller */ ?>
<?php Yii::app()->bootstrap->register(); ?>
<?php
	// ?v=filemtime evita servir CSS cacheado de um deploy anterior — Apache
	// não manda Cache-Control pra esses arquivos estáticos, então o navegador
	// só busca de novo se a URL mudar (mesmo esquema já usado pros JS, ver
	// hostFace/_form.php).
	$layoutWebroot = dirname(Yii::app()->basePath);
	$cssVersion = function ($file) use ($layoutWebroot) {
		return '?v=' . filemtime($layoutWebroot . '/css/' . $file);
	};
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="language" content="en">

	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/screen.css<?php echo $cssVersion('screen.css'); ?>" media="screen, projection">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/print.css<?php echo $cssVersion('print.css'); ?>" media="print">
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/ie.css<?php echo $cssVersion('ie.css'); ?>" media="screen, projection">
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/main.css<?php echo $cssVersion('main.css'); ?>">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/form.css<?php echo $cssVersion('form.css'); ?>">

        <link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/map.css<?php echo $cssVersion('map.css'); ?>" type="text/css" />
        <script type="text/javascript" src="<?php echo Yii::app()->baseUrl; ?>/js/d3/d3.min.js"></script>

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>

<body>



        <div id="mainmenu" class="navbar navbar-fixed-top navbar-inner">
            <div id="logo"><a href="<?php echo Yii::app()->getBaseUrl(true); ?>"><?php echo CHtml::encode(Yii::app()->name); ?></a></div>
            <?php $this->widget('bootstrap.widgets.TbNav',array(
                    'items'=>array(
                            array('label' => 'SNMP Templates', 'url' => array('/snmpTemplate/admin')),
                            array('label' => 'Hosts', 'url' => array('/host/admin')),
                            array('label' => 'Vlans', 'url' => array('/vlan/admin')),
                            array('label' => 'Connections', 'url' => array('/connection/admin')),
                            array('label' => 'Search', 'url' => array('/search/index')),
                            array('label' => 'Configuration', 'url' => array('/config/index')),
                            array('label' => 'About', 'url' => array('/site/page', 'view' => 'about')),
//				array('label'=>'Login', 'url'=>array('/site/login'), 'visible'=>Yii::app()->user->isGuest),
//				array('label'=>'Logout ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest)
                    ),
            )); ?>
	</div><!-- mainmenu -->
	<?php if(isset($this->breadcrumbs)):?>
		<?php $this->widget('bootstrap.widgets.TbBreadcrumb', array(
			'links'=>$this->breadcrumbs,
		)); ?><!-- breadcrumbs -->
	<?php endif?>

	<?php echo $content; ?>

	<div class="clear"></div>

	<div id="footer">
            <?php echo CHtml::encode(Yii::app()->name); ?> <?php echo date('Y'); ?>
	</div><!-- footer -->



</body>
</html>
