<?php
/* @var $this ConnectionController */
/* @var $model Connection */
/* @var $form CActiveForm */

$hostOptions = Host::model()->findAll();
?>
<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'connection-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'host_src_id'); ?>
		<span class="port-combobox" id="Connection_host_src_id_combobox">
			<input type="text" id="Connection_host_src_id_display" placeholder="Type to search..." style="width:200px" value="<?php echo ($model->hostSrc instanceof Host) ? CHtml::encode($model->hostSrc->name) : ''; ?>" />
		</span>
		<?php echo $form->hiddenField($model,'host_src_id'); ?>
		<?php echo $form->error($model,'host_src_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_src_port'); ?>
		<span class="port-combobox" id="Connection_host_src_port_combobox">
			<?php echo $form->textField($model,'host_src_port',array('size'=>4,'maxlength'=>100)); ?>
		</span>
		<span id="Connection_host_src_port_label" class="muted"></span>
		<div id="Connection_host_src_port_discovered" class="discovered-hosts"></div>
		<?php echo $form->error($model,'host_src_port'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_dst_id'); ?>
		<span class="port-combobox" id="Connection_host_dst_id_combobox">
			<input type="text" id="Connection_host_dst_id_display" placeholder="Type to search..." style="width:200px" value="<?php echo ($model->hostDst instanceof Host) ? CHtml::encode($model->hostDst->name) : ''; ?>" />
		</span>
		<?php echo $form->hiddenField($model,'host_dst_id'); ?>
		<?php echo $form->error($model,'host_dst_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'host_dst_port'); ?>
		<span class="port-combobox" id="Connection_host_dst_port_combobox">
			<?php echo $form->textField($model,'host_dst_port',array('size'=>4,'maxlength'=>100)); ?>
		</span>
		<span id="Connection_host_dst_port_label" class="muted"></span>
		<div id="Connection_host_dst_port_discovered" class="discovered-hosts"></div>
		<?php echo $form->error($model,'host_dst_port'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'type'); ?>
        <?php echo $form->dropDownList($model, 'type', $model->getTypes()); ?>
        <?php echo $form->error($model, 'type'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? '➕ Create' : '💾 Save', array('class' => 'btn btn-primary')); ?>
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

// Registrado via registerScript (não uma <script> solta aqui no meio do
// template) pra só rodar depois dos arquivos acima: POS_END junta tudo no
// fim do body, mas Yii sempre renderiza os scriptFiles(POS_END) antes dos
// scripts(POS_END) dessa mesma posição. Uma <script> crua aqui executaria
// na posição em que aparece no template — bem antes disso — com
// PortCombobox ainda indefinido (ReferenceError, os dois combos quebrados).
Yii::app()->clientScript->registerScript('connection-port-combobox-init', '
// Liga um combo de host (texto + lista filtrável, mesmo js/portCombobox.js
// já usado pra porta) no lugar do <select> de Host Src/Dst — o texto
// visível é só o nome escolhido; quem realmente é enviado no submit é o
// campo escondido (Connection_host_src_id / Connection_host_dst_id).
// Disparar um "change" nele mantém o carregamento de portas já ligado por
// attachConnectionPortCombobox funcionando sem duplicar lógica.
function attachConnectionHostCombobox(hiddenId, displayId, comboboxId, hosts) {
    var hidden = document.getElementById(hiddenId);
    var container = document.getElementById(comboboxId);
    var input = document.getElementById(displayId);
    var items = [{id: "", name: "-- clear --", type: ""}].concat(hosts);

    PortCombobox.attach(container, input, function () { return items; }, function (item) {
        hidden.value = item.id;
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("change", true, false);
        hidden.dispatchEvent(evt);
    }, {
        formatLabel: function (item) {
            return item.id && item.type ? item.name + " (" + item.type + ")" : item.name;
        },
        selectValue: function (item) {
            return item.id ? item.name : "";
        },
        filterItems: function (allItems, query) {
            var q = (query || "").trim().toLowerCase();
            if (!q) {
                return allItems;
            }
            return allItems.filter(function (item) {
                return item.name.toLowerCase().indexOf(q) !== -1;
            });
        }
    });
}

// Liga um combo de porta (texto + lista filtrável, ver js/portCombobox.js)
// no campo de porta já existente (Connection_host_src_port /
// Connection_host_dst_port) — o próprio campo continua sendo o valor
// enviado no submit, então digitar um número de porta na mão continua
// funcionando (ex.: switch offline, sem lista SNMP pra escolher).
function attachConnectionPortCombobox(hostSelectId, portInputId, comboboxId, labelId, discoveredId) {
    var ports = [];
    var portInput = document.getElementById(portInputId);
    var combobox = document.getElementById(comboboxId);
    var label = document.getElementById(labelId);
    var discoveredEl = document.getElementById(discoveredId);

    // Mostra o nome/alias da porta atualmente digitada/selecionada, e quem a
    // tabela CAM aponta como estando nela agora (cadastrado ou não, com
    // vlan) — útil tanto ao escolher pela lista quanto pra reconhecer de
    // cara a porta já salva ao abrir o form de edição, e pra conferir antes
    // de criar a Connection que a porta certa foi escolhida.
    function updateLabel(portId) {
        var match = ports.filter(function (p) { return String(p.ifIndex) === String(portId); })[0];
        label.textContent = match ? (match.ifDescr || "") + (match.ifAlias ? " (" + match.ifAlias + ")" : "") : "";
        renderDiscovered(match && match.discoveredHosts ? match.discoveredHosts : []);
    }

    function renderDiscovered(discoveredHosts) {
        discoveredEl.innerHTML = "";
        discoveredHosts.forEach(function (d) {
            var row = document.createElement("div");
            if (d.vlanTag) {
                row.appendChild(document.createTextNode("VLAN " + d.vlanTag + ": "));
            }
            var link = document.createElement("a");
            if (d.host) {
                link.href = ' . CJSON::encode(Yii::app()->baseUrl . '/host/view/') . ' + encodeURIComponent(d.host.name);
                link.textContent = d.host.name;
            } else {
                var params = [];
                if (d.ip) { params.push("ip=" + encodeURIComponent(d.ip)); }
                if (d.mac) { params.push("mac=" + encodeURIComponent(d.mac)); }
                link.href = ' . CJSON::encode(Yii::app()->createUrl('host/viewByName')) . ' + (params.length ? "?" + params.join("&") : "");
                link.className = "text-warning";
                link.textContent = (d.ip ? d.ip + " " : "") + "(" + d.mac + ") — not registered";
            }
            row.appendChild(link);
            discoveredEl.appendChild(row);
        });
    }

    function reload(hostId) {
        ports = [];
        updateLabel(portInput.value);
        if (!hostId) {
            return;
        }
        var portListURL = ' . CJSON::encode(Yii::app()->baseUrl . '/host/loadPortInfo/') . ' + hostId + "?includeNeighbors=1";
        d3.json(portListURL, function (json) {
            if (!json) {
                console.warn("lista vazia de " + portListURL);
                return;
            }
            ports = d3.values(json).map(function (p) {
                return {
                    ifIndex: p.ifIndex,
                    ifDescr: p.ifDescr,
                    ifAlias: p.ifAlias,
                    disabled: !!p.hasConnection,
                    disabledTitle: p.hasConnection ? "Connect to: " + p.hasConnection.name : "",
                    discoveredHosts: p.discoveredHosts || []
                };
            });
            updateLabel(portInput.value);
        });
    }

    PortCombobox.attach(combobox, portInput, function () { return ports; }, function (p) {
        updateLabel(p.ifIndex);
    }, {
        formatLabel: function (p) {
            return p.ifIndex + " - " + (p.ifDescr || "") + (p.ifAlias ? " (" + p.ifAlias + ")" : "");
        },
        selectValue: function (p) {
            return String(p.ifIndex);
        }
    });

    portInput.addEventListener("input", function () {
        updateLabel(portInput.value);
    });

    document.getElementById(hostSelectId).addEventListener("change", function () {
        reload(this.value);
    });
    reload(document.getElementById(hostSelectId).value);
}

var connectionHosts = ' . CJSON::encode(array_map(function ($h) {
    return array('id' => $h->id, 'name' => $h->name, 'type' => $h->type);
}, $hostOptions)) . ';

attachConnectionHostCombobox("Connection_host_src_id", "Connection_host_src_id_display", "Connection_host_src_id_combobox", connectionHosts);
attachConnectionHostCombobox("Connection_host_dst_id", "Connection_host_dst_id_display", "Connection_host_dst_id_combobox", connectionHosts);

attachConnectionPortCombobox("Connection_host_src_id", "Connection_host_src_port", "Connection_host_src_port_combobox", "Connection_host_src_port_label", "Connection_host_src_port_discovered");
attachConnectionPortCombobox("Connection_host_dst_id", "Connection_host_dst_port", "Connection_host_dst_port_combobox", "Connection_host_dst_port_label", "Connection_host_dst_port_discovered");
', CClientScript::POS_END);
?>