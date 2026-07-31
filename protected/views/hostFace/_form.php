<?php
/* @var $this HostFaceController */
/* @var $model HostFace */
/* @var $form CActiveForm */
/* @var $preselectedHost Host|null */

$hostOptions = Host::model()->findAll();
$preselectedHost = isset($preselectedHost) ? $preselectedHost : null;
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'host-face-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'svg'); ?>

		<div id="host-face-editor">
			<div class="card host-face-editor-card">
				<h3>Equipment photo</h3>
				<div class="host-face-editor-image-source">
					<span class="host-face-editor-file-btn">
						<input type="file" id="hfe-image-upload" accept="image/*" />
						<label for="hfe-image-upload" class="btn btn-default btn-sm">🖼️ Upload image</label>
					</span>
					<label class="host-face-editor-field host-face-editor-field-wide">
						<span class="host-face-editor-field-label">or image URL</span>
						<input type="text" id="hfe-image-url" class="form-control input-sm" style="width:300px" placeholder="https://..." />
					</label>
					<button type="button" id="hfe-image-url-load" class="btn btn-default btn-sm">🔗 Load</button>
					<span id="hfe-image-status"></span>
				</div>
			</div>

			<div class="card host-face-editor-card">
				<h3>Ports &amp; Positioning</h3>
				<p class="host-face-editor-empty-hint">Ctrl+Z to undo, Ctrl+Y (or Ctrl+Shift+Z) to redo.</p>

				<div class="host-face-editor-toolbar host-face-editor-svg-toolbar">
					<button type="button" id="hfe-svg-export" class="btn btn-default btn-sm">⬇️ Export SVG</button>
					<span class="host-face-editor-file-btn">
						<input type="file" id="hfe-svg-import" accept=".svg,image/svg+xml" />
						<label for="hfe-svg-import" class="btn btn-default btn-sm">⬆️ Import SVG</label>
					</span>
					<span id="hfe-svg-import-status" class="host-face-editor-empty-hint" aria-live="polite"></span>
				</div>

				<div class="host-face-editor-toolbar">
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Source host (SNMP)</span>
						<span class="port-combobox" id="hfe-host-select-combobox">
							<input type="text" id="hfe-host-select" class="form-control input-sm" placeholder="Type to search..." />
						</span>
					</label>
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Port width (px)</span>
						<input type="number" id="hfe-port-width" class="form-control input-sm" value="22" min="1" />
					</label>
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Port height (px)</span>
						<input type="number" id="hfe-port-height" class="form-control input-sm" value="18" min="1" />
					</label>
				</div>

				<div class="host-face-editor-toolbar host-face-editor-fill-toolbar">
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Rows</span>
						<input type="number" id="hfe-fill-rows" class="form-control input-sm" value="1" min="1" />
					</label>
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Columns</span>
						<input type="number" id="hfe-fill-cols" class="form-control input-sm" value="1" min="1" />
					</label>
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Fill order</span>
						<select id="hfe-fill-order" class="form-control input-sm">
							<option value="row-major">Row by row</option>
							<option value="column-major">Column by column</option>
						</select>
					</label>
					<label class="host-face-editor-field">
						<span class="host-face-editor-field-label">Ports</span>
						<select id="hfe-fill-parity" class="form-control input-sm">
							<option value="all">All</option>
							<option value="odd">Odd only</option>
							<option value="even">Even only</option>
						</select>
					</label>
					<span id="hfe-fill-status" class="host-face-editor-fill-status" aria-live="polite"></span>
				</div>

				<div id="hfe-host-info" class="host-face-editor-host-info" style="display:none">
					<div class="host-face-editor-host-info-row">
						<span class="host-face-editor-host-info-item"><span class="host-face-editor-field-label">System name</span> <span id="hfe-host-info-sysname"></span></span>
						<span class="host-face-editor-host-info-item"><span class="host-face-editor-field-label">IP</span> <span id="hfe-host-info-ip"></span></span>
					</div>
					<a id="hfe-host-info-search" href="#" target="_blank" rel="noopener" class="btn btn-default btn-sm host-face-editor-host-info-search">🔍 Search images for this model</a>
					<p class="host-face-editor-host-info-sysdescr"><span class="host-face-editor-field-label">System description</span> <span id="hfe-host-info-sysdescr"></span></p>
				</div>

				<div id="hfe-canvas" class="host-face-editor-canvas">
					<p class="host-face-editor-empty-hint">Upload or paste the URL of a switch image to get started.</p>
				</div>

				<div class="host-face-editor-palette-wrapper">
					<input type="text" id="hfe-palette-filter" class="host-face-editor-palette-filter" placeholder="Filter by name..." />
					<div id="hfe-palette" class="host-face-editor-palette">
						<p class="host-face-editor-empty-hint">Choose a host above to load the port list.</p>
					</div>
				</div>
			</div>
		</div>

		<div id="hfe-fallback" class="card host-face-editor-fallback" style="display:none">
			<p class="host-face-editor-empty-hint">Could not parse the existing SVG for this face in the visual editor. You can edit the raw SVG below (same as the old text field) — changes here are saved normally.</p>
			<textarea id="hfe-fallback-textarea" rows="10" style="width:100%"></textarea>
		</div>

		<?php echo $form->hiddenField($model,'svg',array('id'=>'hfe-svg-field')); ?>
		<?php echo $form->error($model,'svg'); ?>
	</div>

	<?php
	// ?v=filemtime evita servir JS cacheado de um deploy anterior — o Apache
	// não manda Cache-Control pra esses arquivos estáticos, então o navegador
	// só busca de novo se a URL mudar.
	$hfeWebroot = dirname(Yii::app()->basePath);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceSvg.js?v=' . filemtime($hfeWebroot . '/js/hostFaceSvg.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceGridFill.js?v=' . filemtime($hfeWebroot . '/js/hostFaceGridFill.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceHistory.js?v=' . filemtime($hfeWebroot . '/js/hostFaceHistory.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFacePortFilter.js?v=' . filemtime($hfeWebroot . '/js/hostFacePortFilter.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFacePortStatus.js?v=' . filemtime($hfeWebroot . '/js/hostFacePortStatus.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/portCombobox.js?v=' . filemtime($hfeWebroot . '/js/portCombobox.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceEditor.js?v=' . filemtime($hfeWebroot . '/js/hostFaceEditor.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScript('host-face-editor-init', '
		HostFaceEditor.init({
			loadPortInfoUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadPortInfo/99999999')) . ',
			loadPortStatusUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadPortStatus/99999999')) . ',
			loadSystemInfoUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadSystemInfo/99999999')) . ',
			fetchImageUrl: ' . CJSON::encode(Yii::app()->createUrl('hostFace/fetchImage')) . ',
			existingSvg: ' . CJSON::encode($model->svg) . ',
			hosts: ' . CJSON::encode(array_map(function ($h) {
				return array('id' => $h->id, 'name' => $h->name, 'type' => $h->type);
			}, $hostOptions)) . ',
			associatedHostIds: ' . CJSON::encode(array_map(function ($h) {
				return (string) $h->id;
			}, $model->hosts)) . ',
			preselectedHostId: ' . CJSON::encode($preselectedHost instanceof Host ? (string) $preselectedHost->id : null) . '
		});
	', CClientScript::POS_END);
	?>
        
	<div class="row">
		<?php echo $form->labelEx($model,'hosts'); ?>
                <?php echo $form->dropDownList(
                        $model,
                        'hosts',
                        CHtml::listData($hostOptions, 'id', 'name', 'type'),
                        array(
                            'empty'=>'',
                            'multiple'=>'multiple',
                            'size' => 15,
                            ));
                ?>
		<?php if ($preselectedHost instanceof Host): ?>
			<script>
				// Marca já o host de origem escolhido (ver hostId em
				// hostFace/create) na lista de hosts a associar — senão a
				// face seria criada sem vínculo nenhum com ele.
				document.addEventListener('DOMContentLoaded', function () {
					var opt = document.querySelector('#HostFace_hosts option[value="<?php echo (int) $preselectedHost->id; ?>"]');
					if (opt) { opt.selected = true; }
				});
			</script>
		<?php endif; ?>
		<?php echo $form->error($model,'hosts'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->