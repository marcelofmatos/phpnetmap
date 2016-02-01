<style media="print">
    a:link:after, a:visited:after {content:"" !important;}
</style>
<?php

$this->pageTitle = $model ." ". $this->pageTitle;

$this->breadcrumbs = array(
    'Hosts' => array('admin'),
    $model->name => array('viewByName','name'=>$model->name),
    'Connections'
);

$this->menu = array(
    array('label' => 'Create Host Connection', 'url' => array('/connection/create?host_src_id='.$model->id)),
    array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
    array('label' => 'View Host', 'url' => array('viewByName', 'name' => $model->name)),
);


$ports = $model->loadPortsInfo(array('ifDescr'));
$connections = $model->getConnections();

?>

<h3>Ports connections</h3>

<?php if (count($connections)): ?>
    <table class="host-connection-list">
        <tr>
            <th width="20">Port</th>
            <th width="20">Description</th>
            <th>Destination Host</th>
            <th>Destination Port</th>
        </tr>
        <?php foreach ($connections as $connection): 
            $host_dst_ports = $connection->hostDst->loadPortsInfo(array('ifDescr'));
            ?>
            <tr>
                <td><a href="#" class="portlabel"><?php echo $connection->host_src_port; ?></a></td>
                <td><?php echo $model->ports[$connection->host_src_port]['ifDescr']; ?></td>
                <td><?php echo CHtml::link($connection->hostDst->name, Yii::app()->createUrl("host/viewByName",array("name"=>$connection->hostDst->name))) ?></td>
                <td>
                <?php if($connection->host_dst_port): ?>
                    <?php echo $connection->host_dst_port; ?>
                    <?php echo 
                            ($host_dst_ports[$connection->host_dst_port]['ifDescr'] && $host_dst_ports[$connection->host_dst_port]['ifDescr'] != $connection->host_dst_port) 
                            ? '('.$host_dst_ports[$connection->host_dst_port]['ifDescr'].')' 
                            : ''; 
                    ?>
                <?php else: ?>
                    <?php // do link for create ?>
                <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    No connections on this host. <?php echo CHtml::link('Create Host Connection', Yii::app()->createUrl("connection/create",array("host_src_id"=>$model->id))); ?>.
<?php endif; ?>