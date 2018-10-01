<?php


if (is_array($cam_table)) {

// TODO: localizar IP da tabela ARP do gateway da rede
//    foreach ($rows as $k=>$row){
//    		$rows[$k]['options'] = ($row['ip']) ? CHtml::link('View',$row['ip']) : " ";
//    }
  
    $dataProvider = new CArrayDataProvider($cam_table,array(
        'sort'=>array(
            'attributes'=>array(
                 'port', 'vlan', 'mac', 'ip',
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
            array(
                'name' => 'port',
                'value' => 'CHtml::link($data[port], Yii::app()->createUrl("host/viewByName",array("name"=>$data[host_dst]->name)), array("title"=>"Link to: {$data[host_dst]}", "name"=>"port_{$data[port]}", "class"=>"portlabel"))',
                'type' => 'raw',
            ),
            array(
                'name'  => 'vlan',
                'value' => 'CHtml::link($data[vlan]->tag, Yii::app()->createUrl("vlan/viewByTag",array("tag"=>$data[vlan]->tag)),array("title"=>"$data[vlan]", "style"=>"color:#{$data[vlan]->font_color}; background-color:#{$data[vlan]->background_color}","class"=>"vlanlabel"))',
                'type'  => 'raw',
            ),
            'mac',
            array(
                'name'  => 'ip',
                'value' => '( isset($data[host]->ip) ) ? $data[host]->ip : null',
                'type'  => 'raw',
            ),
            array(
                'name'  => 'host',
                'value' => '( isset($data[host]->id) ) ? CHtml::link($data[host]->name, Yii::app()->createUrl("host/viewByName",array("name"=>$data[host]->name)),array("title"=>"$data[host]")) : $data[host]->ip',
                'type'  => 'raw',
                'visible'=>' ! $data[host_dst] instanceof Host && $data[host]->id',
            ),
            array(
                'class'=>'CButtonColumn',
                'template'=> '{create_host}{create_conn}',
		'buttons'=>array(
                    'create_conn'=>array(
                        'label' => 'Create Connection',
                        'imageUrl'=>Yii::app()->request->baseUrl.'/images/connection/add.png',
                        'url'=>'$this->grid->controller->createUrl("connection/create", array("host_src_id"=>'.$model->id.', "host_src_port" => $data[port], "host_dst_id" => $data[host]->id))',
                        'visible'=>' ! $data[host_dst] instanceof Host && $data[host]->id',
                    ),
                    'create_host'=>array(
                        'label' => 'Create Host',
                        'imageUrl' => Yii::app()->request->baseUrl.'/images/host/add.png',
                        'url' => '$this->grid->controller->createUrl("host/create", array("ip" => $data[host]->ip, "mac" => $data[mac]))',
                        'visible' => '! $data[host]->id',
                    ),
                ),
            ),
        ),
   ));
    
}
