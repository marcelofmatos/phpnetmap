<style media="print">
    a:link:after, a:visited:after {content:"" !important;}
</style>
<?php
$this->breadcrumbs = array(
    'Search',
);
?>

<div class="form">
    <?php echo $form ?>
</div>

<?php if ($result) : ?>
    <div class="result">
    <?php
    
    switch ($searchModel->type) {

        case "camtable_mac" :
        case "camtable_vlan" :

            $dataProvider = new CArrayDataProvider($result, array(
                        'sort' => array(
                            'attributes' => array(
                                'host', 'port', 'port_dst_host', 'vlan', 'mac',
                            ),
                        ),
                        'pagination' => array(
                            'pageSize' => 100000,
                        ),
                    ));

            $this->widget('bootstrap.widgets.TbGridView', array(
                'id' => 'cam-grid',
                'dataProvider' => $dataProvider,
                'columns' => array(
                    array(
                        'name' => 'host',
                        'value' => 'CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)))',
                        'type' => 'raw',
                    ),
                    array(
                        'name' => 'port',
                        'value' => 'CHtml::link($data[port], Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)), array("name"=>"port_{$data[port]}", "class"=>"portlabel"))',
                        'type' => 'raw',
                    ),
                    array(
                        'name' => 'vlan',
                        'value' => 'CHtml::link($data[vlan]->tag, Yii::app()->createUrl("vlan/tag",array("id"=>$data[vlan]->tag)),array("title"=>"$data[vlan]", "style"=>"color:#{$data[vlan]->font_color}; background-color:#{$data[vlan]->background_color}","class"=>"vlanlabel"))',
                        'type' => 'raw',
                    ),
                    array(
                        'name' => 'hostDst',
                        'value' => 'CHtml::link($data[hostDst]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostDst]->name)))',
                        'type' => 'raw',
                        'visible' => '$data[hostDst] instanceof Host',
                    ),

                    'mac',
                ),
            ));
            break;
        default:
            echo 'unknown search type';
    }
    ?>
    </div>
<?php endif; ?>

