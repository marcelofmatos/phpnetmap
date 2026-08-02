<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name . ' - About';
$this->breadcrumbs=array(
	'About',
);

// Gravado no build da imagem Docker (Dockerfile ARG VERSION, preenchido
// pelo workflow de release com a tag criada) — não vem do .git, que o
// .dockerignore exclui do build context. Sem esse arquivo (build local
// sem o build-arg, ou instalação standalone sem Docker), mostra
// "development" em vez de quebrar.
$versionFile = Yii::app()->basePath . '/../VERSION';
$version = is_file($versionFile) ? trim(file_get_contents($versionFile)) : '';
?>
<h1>About</h1>

<h3>PHPNetMap</h3>

<p class="text-muted">Version <?php echo CHtml::encode($version !== '' ? $version : 'development'); ?></p>

<p>Software for monitoring Layer 2 and Layer 3 network equipment with SNMP v(1/2c/3) protocol</p>

<p>Tested with 3Com/HP, Procurve, Dell and Extreme switches. Some models with SNMP support work correctly.</p>

<p>Framework <a href="www.yiiframework.com">yii</a> with yiistrap, colorpiker, CAdvancedArBehavior extensions.</p>

<p>Using <a href="http://d3js.org/">D3</a> JavaScript library for map.</p>

