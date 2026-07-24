<?php
/* @var $this HostController */
/* @var $model Host */
?>
<div class="diagnostic-cards">
    <?php echo CHtml::link(
        '<strong>ARP Table</strong><br/>IP &harr; MAC mapping seen by this host',
        array('host/arpTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>CAM Table</strong><br/>MAC addresses learned on each port',
        array('host/camTable', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Traffic</strong><br/>Traffic graph per port',
        array('host/traffic', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
    <?php echo CHtml::link(
        '<strong>Connections</strong><br/>Hosts connected to this device',
        array('host/connections', 'name' => $model->name),
        array('class' => 'diagnostic-card')
    ); ?>
</div>
