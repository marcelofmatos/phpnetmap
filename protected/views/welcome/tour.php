<?php
/* @var $this WelcomeController */

$this->breadcrumbs = array(
    'Welcome' => array('/welcome/index'),
    'Tour',
);

$pnmIcons = require(Yii::app()->basePath . '/config/pnmIcons.php');

// 'url' isn't rendered as a link in this task (the cards are informational
// only, by design — see docs/superpowers/plans/2026-08-01-welcome-wizard.md
// Task 2), but is kept accurate to each section's real top-nav route
// (protected/views/layouts/main.php) in case a future change wires it in.
// Icons come from protected/config/pnmIcons.php (shared with the sidebar
// nav in layouts/main.php) — trusted, hand-authored inline SVG (not
// request-derived), so it's echoed raw below rather than through
// CHtml::encode().
$tourCards = array(
    array(
        'label' => 'SNMP Templates',
        'url' => array('/snmpTemplate/admin'),
        'description' => 'Reusable SNMP v1/2c/3 credentials (community string or v3 security settings) that hosts reference, so you set them up once instead of on every device.',
        'icon' => $pnmIcons['snmpTemplate'],
    ),
    array(
        'label' => 'Hosts',
        'url' => array('/host/admin'),
        'description' => 'Your monitored switches and routers — inventory, port status, traffic graphs, and CAM/ARP tables for each device.',
        'icon' => $pnmIcons['host'],
    ),
    array(
        'label' => 'Vlans',
        'url' => array('/vlan/admin'),
        'description' => 'The VLANs on your network, used to color and group ports and hosts on the map.',
        'icon' => $pnmIcons['vlan'],
    ),
    array(
        'label' => 'Connections',
        'url' => array('/connection/admin'),
        'description' => 'The links between hosts (which port connects to which) — this is what PHPNetMap draws as your network topology map.',
        'icon' => $pnmIcons['connection'],
    ),
    array(
        'label' => 'Search',
        'url' => array('/search/index'),
        'description' => 'Quickly find a host, MAC address, or IP across your whole inventory.',
        'icon' => $pnmIcons['search'],
    ),
    array(
        'label' => 'MCP Tokens',
        'url' => array('/mcpToken/admin'),
        'description' => "API tokens for connecting AI assistants to PHPNetMap's MCP server. Whether they're read-only or read-write is a single site-wide setting on the Configuration screen, not chosen per token.",
        'icon' => $pnmIcons['mcpToken'],
    ),
    array(
        'label' => 'Users',
        'url' => array('/user/admin'),
        'description' => 'Manage who can log in to PHPNetMap.',
        'icon' => $pnmIcons['user'],
    ),
    array(
        'label' => 'Configuration',
        'url' => array('/config/index'),
        'description' => 'The settings you just reviewed — admin email, caching, the SNMP gateway host, and the MCP server\'s read-only/read-write mode.',
        'icon' => $pnmIcons['config'],
    ),
);
?>

<div class="pnm-steps">
    <span class="pnm-step is-done"><span class="pnm-step-num">1</span> Welcome</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step is-done"><span class="pnm-step-num">2</span> Configuration</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step is-active"><span class="pnm-step-num">3</span> Tour</span>
</div>

<h1>You're all set — here's a quick tour</h1>

<p class="pnm-tour-intro">
    Here's what each part of PHPNetMap does. You'll always find these in the top menu.
    <br>
    <?php echo CHtml::link('Skip tour', array('/map/index'), array('class' => 'pnm-skip')); ?>
</p>

<div class="pnm-tour-grid">
    <?php foreach ($tourCards as $i => $card): ?>
    <div class="pnm-tour-card" style="animation-delay: <?php echo $i * 0.05; ?>s">
        <span class="pnm-tour-card-icon"><?php echo $card['icon']; ?></span>
        <h4><?php echo CHtml::encode($card['label']); ?></h4>
        <p><?php echo CHtml::encode($card['description']); ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="pnm-tour-footer">
    <?php echo CHtml::link('Finish', array('/map/index'), array('class' => 'btn btn-primary')); ?>
</div>
