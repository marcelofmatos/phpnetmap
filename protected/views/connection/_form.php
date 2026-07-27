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
		<span class="port-combobox" id="Connection_host_src_port_combobox">
			<?php echo $form->textField($model,'host_src_port',array('size'=>4,'maxlength'=>100)); ?>
		</span>
		<?php echo $form->error($model,'host_src_port'); ?>
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
		<span class="port-combobox" id="Connection_host_dst_port_combobox">
			<?php echo $form->textField($model,'host_dst_port',array('size'=>4,'maxlength'=>100)); ?>
		</span>
		<?php echo $form->error($model,'host_dst_port'); ?>
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
<?php
// ?v=filemtime evita servir JS cacheado de um deploy anterior — o Apache
// não manda Cache-Control pra esses arquivos estáticos, então o navegador
// só busca de novo se a URL mudar.
$connFormWebroot = dirname(Yii::app()->basePath);
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFacePortFilter.js?v=' . filemtime($connFormWebroot . '/js/hostFacePortFilter.js'), CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/portCombobox.js?v=' . filemtime($connFormWebroot . '/js/portCombobox.js'), CClientScript::POS_END);
?>
<script>

// Liga um combo de porta (texto + lista filtrável, ver js/portCombobox.js)
// no campo de porta já existente (Connection_host_src_port /
// Connection_host_dst_port) — o próprio campo continua sendo o valor
// enviado no submit, então digitar um número de porta na mão continua
// funcionando (ex.: switch offline, sem lista SNMP pra escolher).
function attachConnectionPortCombobox(hostSelectId, portInputId, comboboxId) {
    var ports = [];
    var portInput = document.getElementById(portInputId);
    var combobox = document.getElementById(comboboxId);

    function reload(hostId) {
        ports = [];
        if (!hostId) {
            return;
        }
        var portListURL = '<?php echo Yii::app()->baseUrl; ?>/host/loadPortInfo/' + hostId;
        d3.json(portListURL, function (json) {
            if (!json) {
                console.warn('lista vazia de ' + portListURL);
                return;
            }
            ports = d3.values(json).map(function (p) {
                return {
                    ifIndex: p.ifIndex,
                    ifDescr: p.ifDescr,
                    ifAlias: p.ifAlias,
                    disabled: !!p.hasConnection,
                    disabledTitle: p.hasConnection ? 'Connect to: ' + p.hasConnection.name : ''
                };
            });
        });
    }

    PortCombobox.attach(combobox, portInput, function () { return ports; }, function () {}, {
        formatLabel: function (p) {
            return p.ifIndex + ' - ' + (p.ifDescr || '') + (p.ifAlias ? ' (' + p.ifAlias + ')' : '');
        },
        selectValue: function (p) {
            return String(p.ifIndex);
        }
    });

    document.getElementById(hostSelectId).addEventListener('change', function () {
        reload(this.value);
    });
    reload(document.getElementById(hostSelectId).value);
}

attachConnectionPortCombobox('Connection_host_src_id', 'Connection_host_src_port', 'Connection_host_src_port_combobox');
attachConnectionPortCombobox('Connection_host_dst_id', 'Connection_host_dst_port', 'Connection_host_dst_port_combobox');
</script>