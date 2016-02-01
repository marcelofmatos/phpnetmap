<?php

if (is_array($arp_table)) {

       
        $dataProvider = new CArrayDataProvider($arp_table,array(
            'sort'=>array(
                'attributes'=>array(
                     'mac', 'ip',
                ),
            ),
            'pagination'=>array(
                'pageSize'=>1000,
            ),
        ));


        $this->widget('bootstrap.widgets.TbGridView', array(
            'id' => 'cam-grid',
            'dataProvider' => $dataProvider,
            'columns' => array(
                            'mac',
                            'ip',
                            array(
                                'name'  => 'host',
                                'value' => '( $data[host] instanceof Host ) ? CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)),array("title"=>"$data[host]")) : ""',
                                'type'  => 'raw',
                            ),
                            array(
                                'class'=>'CButtonColumn',
            //                    'template'=> '{create_host}{show_host}',
                                'template'=> '{create_host}',
                                'buttons'=>array(
                                    'create_host'=>array(
                                        'label' => 'Create Host',
                                        'imageUrl'=>Yii::app()->request->baseUrl.'/images/host/add.png',
                                        'url'=>'$this->grid->controller->createUrl("host/create", array("ip" => $data[ip], "mac" => $data[mac]))',
                                        'visible'=>' ! $data[host] instanceof Host',
                                    ),
                                ),
                        ),
                    ),
            ));
}

