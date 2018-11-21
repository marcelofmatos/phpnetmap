<style media="print">
    a:link:after, a:visited:after {content:"" !important;}
</style>
<?php
$this->breadcrumbs = array(
    'Search',
);
?>

<div class="form span3">
    <?php echo $form ?>
</div>

<?php if ($result) : ?>
    <div class="result span9">
        <?php
        switch ($searchModel->type) {

            case "camtable:mac" :
            case "camtable:vlan_tag" :

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
                            'value' => 'CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)), array("class"=>"view host-type ". $data[host]->type))',
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
                        'mac',
                        array(
                            'name' => 'hostDst',
                            'value' => '$data[hostDst] instanceof Host ? CHtml::link($data[hostDst]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostDst]->name)), array("style"=>"float:left", "class"=>"view host-type ". $data[hostDst]->type))." ".CHtml::link(CHtml::image(Yii::app()->request->baseUrl."/images/search.png","Search in this host"), "#", array("onclick"=>"$(\'#SearchForm_hosts\').val({$data[hostDst]->id});document.forms[\'yw0\'].submit.click()")) : ""',
                            'type' => 'raw',
                        ),
                        array(
                            'class' => 'CButtonColumn',
                            //                    'template'=> '{create_host}{show_host}',
                            'template' => '{create_conn}{create_host}',
                            'buttons' => array(
                                'create_conn'=>array(
                                    'label' => 'Create Connection',
                                    'imageUrl' => Yii::app()->request->baseUrl.'/images/connection/add.png',
                                    'url' => '$this->grid->controller->createUrl("connection/create", array("host_src_id" => $data[host]->id, "host_src_port" => $data[port], "host_dst_id" => $data[hostDst]->id))',
                                    'visible' => '! $data[host]->getHostOnPort($data[port]) && $data[hostDst] instanceof Host',
                                ),
                                'create_host' => array(
                                    'label' => 'Create Host',
                                    'imageUrl' => Yii::app()->request->baseUrl . '/images/host/add.png',
                                    'url' => '$this->grid->controller->createUrl("host/create", array("ip" => $data[ip], "mac" => $data[mac]))',
                                    'visible' => '! $data[hostDst] instanceof Host',
                                ),
                            ),
                        ),
                    ),
                ));
                break;


            case 'arptable:mac':
            case 'arptable:ip':

                $dataProvider = new CArrayDataProvider($result, array(
                    'sort' => array(
                        'attributes' => array(
                            'mac', 'ip', 'host',
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
                            'value' => 'CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)), array("class"=>"view host-type ". $data[host]->type))',
                            'type' => 'raw',
                        ),
                        'mac',
                        'ip',
                        array(
                            'name' => 'hostDst',
                            'value' => '$data[hostDst] instanceof Host ? CHtml::link($data[hostDst]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostDst]->name)), array("class"=>"view host-type ". $data[hostDst]->type)) : ""',
                            'type' => 'raw',
                        ),
                        array(
                            'class' => 'CButtonColumn',
                            //                    'template'=> '{create_host}{show_host}',
                            'template' => '{create_host}',
                            'buttons' => array(
                                'create_host' => array(
                                    'label' => 'Create Host',
                                    'imageUrl' => Yii::app()->request->baseUrl . '/images/host/add.png',
                                    'url' => '$this->grid->controller->createUrl("host/create", array("ip" => $data[ip], "mac" => $data[mac]))',
                                    'visible' => ' ! $data[hostDst] instanceof Host',
                                ),
                            ),
                        ),
                    ),
                ));
                break;

            case 'portsinfo:ifAlias':

                $dataProvider = new CArrayDataProvider($result, array(
                    'sort' => array(
                        'attributes' => array(
                            'host', 'port', 'info',
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
                            'value' => 'CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)), array("class"=>"view host-type ". $data[host]->type))',
                            'type' => 'raw',
                        ),
                        'port',
                        array(
                            'name' => 'hostDst',
                            'value' => '$data[hostDst] instanceof Host ? CHtml::link($data[hostDst]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[hostDst]->name)), array("class"=>"view host-type ". $data[hostDst]->type)) : ""',
                            'type' => 'raw',
                        ),
                        'info',
                    ),
                ));
                break;

            default:
                echo 'unknown search type';
        }
        ?>
    </div>
    <?php endif; ?>
