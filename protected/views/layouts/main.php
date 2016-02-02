<?php /* @var $this Controller */ ?>
<?php Yii::app()->bootstrap->register(); ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="language" content="en">

	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/screen.css" media="screen, projection">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/print.css" media="print">
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/ie.css" media="screen, projection">
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/main.css">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/form.css">

        <link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/map.css" type="text/css" />
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
                            array('label' => 'Search', 'url' => array('/search')),
                            array('label' => 'Configuration', 'url' => array('/config')),
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
