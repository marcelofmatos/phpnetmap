<?php
/* @var $this HostFaceController */
/* @var $model HostFace */
/* @var $form CActiveForm */

$hostOptions = Host::model()->findAll();
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
				<h3>Ports (via SNMP)</h3>
				<div class="host-face-editor-toolbar">
					<label>Source host (SNMP):
						<select id="hfe-host-select">
							<option value="">-- select --</option>
							<?php foreach ($hostOptions as $hostOption): ?>
								<option value="<?php echo $hostOption->id; ?>"><?php echo CHtml::encode($hostOption->name); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>Port width (px): <input type="number" id="hfe-port-width" value="22" min="1" style="width:60px" /></label>
					<label>Port height (px): <input type="number" id="hfe-port-height" value="18" min="1" style="width:60px" /></label>
				</div>

				<div class="host-face-editor-toolbar host-face-editor-fill-toolbar">
					<label>Rows: <input type="number" id="hfe-fill-rows" value="1" min="1" style="width:50px" /></label>
					<label>Columns: <input type="number" id="hfe-fill-cols" value="1" min="1" style="width:50px" /></label>
					<label>Fill order:
						<select id="hfe-fill-order">
							<option value="row-major">Row by row</option>
							<option value="column-major">Column by column</option>
						</select>
					</label>
					<label>Ports:
						<select id="hfe-fill-parity">
							<option value="all">All</option>
							<option value="odd">Odd only</option>
							<option value="even">Even only</option>
						</select>
					</label>
					<span id="hfe-fill-status" class="host-face-editor-fill-status" aria-live="polite"></span>
				</div>

				<div id="hfe-host-info" class="host-face-editor-host-info" style="display:none">
					<p><strong>System name:</strong> <span id="hfe-host-info-sysname"></span></p>
					<p><strong>System description:</strong> <span id="hfe-host-info-sysdescr"></span></p>
					<p><strong>IP:</strong> <span id="hfe-host-info-ip"></span></p>
					<a id="hfe-host-info-search" href="#" target="_blank" rel="noopener">Search images for this model</a>
				</div>
			</div>

			<div class="card host-face-editor-card">
				<h3>Equipment photo</h3>
				<div class="host-face-editor-image-source">
					<label>Upload image: <input type="file" id="hfe-image-upload" accept="image/*" /></label>
					<label>or image URL: <input type="text" id="hfe-image-url" style="width:300px" placeholder="https://..." /></label>
					<button type="button" id="hfe-image-url-load">Load</button>
					<span id="hfe-image-status"></span>
				</div>
			</div>

			<div class="card host-face-editor-card">
				<h3>Position the ports</h3>
				<p class="host-face-editor-empty-hint">Ctrl+Z to undo, Ctrl+Y (or Ctrl+Shift+Z) to redo.</p>
				<div class="host-face-editor-body">
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
	Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/hostFaceEditor.js?v=' . filemtime($hfeWebroot . '/js/hostFaceEditor.js'), CClientScript::POS_END);
	Yii::app()->clientScript->registerScript('host-face-editor-init', '
		HostFaceEditor.init({
			loadPortInfoUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadPortInfo/99999999')) . ',
			loadSystemInfoUrlTemplate: ' . CJSON::encode(Yii::app()->createUrl('host/loadSystemInfo/99999999')) . ',
			fetchImageUrl: ' . CJSON::encode(Yii::app()->createUrl('hostFace/fetchImage')) . ',
			existingSvg: ' . CJSON::encode($model->svg) . '
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
		<?php echo $form->error($model,'hosts'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->