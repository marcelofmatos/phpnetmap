<?php
/* @var $this HostController */
/* @var $gateway Host */
/* @var $matches array */
/* @var $unmatched Host[] */

$this->breadcrumbs = array(
	'Hosts' => array('admin'),
	'Fill Missing MACs',
);

$this->menu = array(
	array('label' => 'Manage Host', 'url' => array('admin')),
);
?>

<h1>Fill Missing MACs from ARP Table</h1>
<p>Gateway: <b><?php echo CHtml::encode($gateway->name); ?></b> (<?php echo CHtml::encode($gateway->ip); ?>)</p>

<?php if (empty($matches) && empty($unmatched)): ?>
	<p>No hosts with an IP but no MAC were found — nothing to do.</p>
<?php else: ?>

	<?php if (!empty($matches)): ?>
		<h3>Will be updated (<?php echo count($matches); ?>)</h3>
		<table class="table table-striped">
			<thead>
				<tr><th>Host</th><th>IP</th><th>MAC (from ARP)</th></tr>
			</thead>
			<tbody>
				<?php foreach ($matches as $match): ?>
					<tr>
						<td><?php echo CHtml::encode($match['host']->name); ?></td>
						<td><?php echo CHtml::encode($match['host']->ip); ?></td>
						<td><?php echo CHtml::encode($match['mac']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php echo CHtml::form($this->createUrl('fillMacFromArp'), 'post'); ?>
			<?php echo CHtml::submitButton('Confirm and fill ' . count($matches) . ' host(s)', array('name' => 'confirm')); ?>
		<?php echo CHtml::endForm(); ?>
	<?php endif; ?>

	<?php if (!empty($unmatched)): ?>
		<h3>No matching ARP entry (<?php echo count($unmatched); ?>)</h3>
		<p class="note">These hosts have an IP but weren't found in the gateway's current ARP table (they may be offline, or the entry expired).</p>
		<ul>
			<?php foreach ($unmatched as $host): ?>
				<li><?php echo CHtml::encode($host->name); ?> (<?php echo CHtml::encode($host->ip); ?>)</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

<?php endif; ?>
