<?php
/* @var $this ConnectionController */
/* @var $model Connection */
/* @var $form CActiveForm */
?>
<div class="form">

<?php $form=$this->beginWidget('TbActiveForm', array(
	'id'=>'connection-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'host_src_id'); ?>
                <?php echo $form->dropDownList(
                        $model,
                        'host_src_id', 
                        CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'), 
                        array('empty'=>'')); 
                ?>
		<?php echo $form->error($model,'host_src_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_src_port'); ?>
		<?php echo $form->textField($model,'host_src_port',array('size'=>4,'maxlength'=>100)); ?>
		<?php echo $form->error($model,'host_src_port'); ?>

                <select id="Connection_host_src_id_port_list">
                    <option> -- selecione <?php echo $form->labelEx($model,'host_src_id'); ?> -- </option>
                </select>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_dst_id'); ?>
                <?php echo $form->dropDownList(
                        $model,
                        'host_dst_id', 
                        CHtml::listData(Host::model()->findAll(), 'id', 'name', 'type'), 
                        array('empty'=>'')); 
                ?>
		<?php echo $form->error($model,'host_dst_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_dst_port'); ?>
		<?php echo $form->textField($model,'host_dst_port',array('size'=>4,'maxlength'=>100)); ?>
		<?php echo $form->error($model,'host_dst_port'); ?>
            
                <select id="Connection_host_dst_id_port_list">
                    <option> -- selecione <?php echo $form->labelEx($model,'host_dst_id'); ?> -- </option>
                </select>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'type'); ?>
        <?php echo $form->dropDownList($model, 'type', $model->getTypes()); ?>
        <?php echo $form->error($model, 'type'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->
<script>

function getHostPortList(obj,portListId,portInputId) {
    var selectHostList = d3.select(portListId);
    var hostPortInput = d3.select(portInputId);
    var hostId = obj.options[obj.selectedIndex].value;
    
    if(hostId) {
        portListURL = '<?php echo Yii::app()->baseUrl; ?>/host/loadPortInfo/'+ hostId;
        
        selectHostList.selectAll("option").remove();
        selectHostList.append("option").text('-- Carregando... --');
        
        
        d3.json(portListURL, function(json) {
            
            if(!json) {
                console.warn('lista vazia de '+ portListURL);
            }
            
            selectHostList.selectAll("option").remove();
            //TODO: adicionar option vazia no inicio
            //selectHostList.append("option").text('-- Selecione: --'); 
            
            selectHostList.selectAll("option")
                .data(d3.values(json))
                .enter()
                .append("option")
                    .text(function (d) {
                        return d.ifIndex + ' - ' + d.ifDescr + (d.ifAlias ? ' ('+ d.ifAlias +')' : ''); 
                    })
                    .attr("value", function (d) { 
                        return d.ifIndex; 
                    })
                    .property("selected", function (d) {
                        return d.ifIndex == hostPortInput.property('value');
                    })
                    .property("disabled", function (d) {
                        return d.hasOwnProperty('hasConnection');
                    })
                    .attr("title", function (d) {
                        return d.hasConnection ? 'Connect to: '+ d.hasConnection.name : '';
                    })
        });
    }
}

function setHostPortField(obj,hostPortId) {
    if(hostPortId) d3.select(hostPortId).property('value',obj.options[obj.selectedIndex].value);
}

function setHostPortList(obj,hostPortListId) {
    if(hostPortListId) d3.select(hostPortListId).property('value',obj.value);
}

d3.select("#Connection_host_src_id").on("change", function() { getHostPortList(this,'#Connection_host_src_id_port_list','#Connection_host_src_port') });
d3.select("#Connection_host_src_port").on("keyup", function() { setHostPortList(this,'#Connection_host_src_id_port_list') });
d3.select("#Connection_host_src_id_port_list").on("change", function() { setHostPortField(this,'#Connection_host_src_port') });

d3.select("#Connection_host_dst_id").on("change", function() { getHostPortList(this,'#Connection_host_dst_id_port_list','#Connection_host_dst_port') });
d3.select("#Connection_host_dst_port").on("keyup", function() { setHostPortList(this,'#Connection_host_dst_id_port_list') });
d3.select("#Connection_host_dst_id_port_list").on("change", function() { setHostPortField(this,'#Connection_host_dst_port') });

getHostPortList(document.getElementById('Connection_host_src_id'),'#Connection_host_src_id_port_list','#Connection_host_src_port')
getHostPortList(document.getElementById('Connection_host_dst_id'),'#Connection_host_dst_id_port_list','#Connection_host_dst_port')
</script>