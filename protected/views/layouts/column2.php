<?php /* @var $this Controller */ ?>
<?php $this->beginContent('//layouts/main'); ?>
<?php if (!empty($this->menu)): ?>
<div class="row g-4 pnm-grid">
	<div class="col-lg-9">
		<?php echo $content; ?>
	</div>
	<div class="col-lg-3">
		<div class="card">
			<div class="card-header">Operations</div>
			<div class="card-body py-2">
				<?php $this->widget('bootstrap.widgets.TbNav', array(
					'items' => $this->menu,
					'htmlOptions' => array('class' => 'flex-column'),
				)); ?>
			</div>
		</div>
	</div>
</div>
<?php else: ?>
	<?php echo $content; ?>
<?php endif; ?>
<?php $this->endContent(); ?>
