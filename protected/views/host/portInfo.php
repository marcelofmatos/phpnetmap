<?php

$labels['ifOperStatus'] = array (
    1 => 'UP(1)',
    2 => 'DOWN(2)',
    );
$labels['ifAdminStatus'] = array (
    1 => 'UP(1)',
    2 => 'DOWN(2)',
    );
$labels['dot1dStpPortState'] = array (
    1 => 'disabled(1)',
    2 => 'blocking(2)',
    3 => 'listening(3)',
    4 => 'learning(4)',
    5 => 'forwarding(5)',
    6 => 'broken(6)',
    );

$stpStatus = $port['dot1dStpPortState'];
$operStatus = $port['ifOperStatus'];
$adminStatus = $port['ifAdminStatus'];

?>
<ul class="menu">
        <li>
            <?php echo $port['ifDescr'] ?>
        </li>
        <li>
            Oper. Status: <?php echo $labels['ifOperStatus'][$operStatus] ?>
        </li>
        <li>
            Admin. Status:
            <form style="display: inline" action="<?php echo Yii::app()->createUrl('host/setSNMP') ?>" method="post">
                <input type="hidden" name="name" value="<?php echo $model->name ?>" />
                <input type="hidden" name="key" value="ifAdminStatus" />
                <input type="hidden" name="port" value="<?php echo $portNumber ?>" />
                <select name="value" onchange="javascript: <?php if ($hostOnPort->name) : ?> if( this.value == 2 && !confirm('Esta porta é a conexão para o host <?php echo $hostOnPort->name; ?>. Há o risco de perder a comunicação com este host. Continuar com a operação?')) this.form.reset(); else <?php endif; ?> $.post(this.form.action, $(this.form).serialize()); if(ajaxLoadStatus==false) document.getElementById('ckbxRefreshStatus').click();">
                    <option class="ifAdminStatus1" value="1" <?php echo ($adminStatus==1) ? 'selected="selected"' : '' ?>>UP(1)</option>
                    <option class="ifAdminStatus2" value="2" <?php echo ($adminStatus==2) ? 'selected="selected"' : '' ?>>DOWN(2)</option>
                </select>
            </form>
        </li>
    <?php if(!is_null($stpStatus)): ?>
        <li>Spanning Tree Status: <?php echo $labels['dot1dStpPortState'][$stpStatus] ?></li>
    <?php endif; ?>
    <?php if($hostOnPort instanceof Host): ?>
        <li><?php echo CHtml::link('Go to '. $hostOnPort->name, Yii::app()->createUrl("host/viewByName",array("name"=>$hostOnPort->name))) ?></li>
    <?php else: ?>
        <li><?php echo CHtml::link('Create Connection', Yii::app()->createUrl("connection/create",array("host_src_id"=>$model->id,"host_src_port"=>$portNumber))); ?></li>
    <?php endif; ?>
        <li>
            <?php echo CHtml::link('MACs List', Yii::app()->createUrl("host/viewTable",array("name"=>$model->name,"#"=>"port_".$portNumber))); ?>
        </li> 
</ul>