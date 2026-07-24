<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="diagnostic-cards">
    <?php echo CHtml::link(
        '<strong>ARP Table</strong><br/>Mapeamento IP ↔ MAC visto por este host',
        array('host/arpTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>CAM Table</strong><br/>MACs aprendidos em cada porta',
        array('host/camTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Tráfego</strong><br/>Gráfico de tráfego por porta',
        array('host/traffic', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Conexões</strong><br/>Hosts conectados a este equipamento',
        array('host/connections', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
</div>
