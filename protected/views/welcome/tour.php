<?php
/* @var $this WelcomeController */

$this->breadcrumbs = array(
    'Welcome' => array('/welcome/index'),
    'Tour',
);

$tourCards = array(
    array(
        'label' => 'SNMP Templates',
        'url' => array('/snmpTemplate/index'),
        'description' => 'Reusable SNMP v1/2c/3 credentials (community string or v3 security settings) that hosts reference, so you set them up once instead of on every device.',
    ),
    array(
        'label' => 'Hosts',
        'url' => array('/host/index'),
        'description' => 'Your monitored switches and routers — inventory, port status, traffic graphs, and CAM/ARP tables for each device.',
    ),
    array(
        'label' => 'Vlans',
        'url' => array('/vlan/index'),
        'description' => 'The VLANs on your network, used to color and group ports and hosts on the map.',
    ),
    array(
        'label' => 'Connections',
        'url' => array('/connection/index'),
        'description' => 'The links between hosts (which port connects to which) — this is what PHPNetMap draws as your network topology map.',
    ),
    array(
        'label' => 'Search',
        'url' => array('/search/index'),
        'description' => 'Quickly find a host, MAC address, or IP across your whole inventory.',
    ),
    array(
        'label' => 'MCP Tokens',
        'url' => array('/mcpToken/admin'),
        'description' => "API tokens for connecting AI assistants to PHPNetMap's MCP server, in read-only or read-write mode.",
    ),
    array(
        'label' => 'Users',
        'url' => array('/user/admin'),
        'description' => 'Manage who can log in to PHPNetMap.',
    ),
    array(
        'label' => 'Configuration',
        'url' => array('/config/index'),
        'description' => 'The settings you just reviewed — admin email, caching, the SNMP gateway host, and the MCP server.',
    ),
);
?>

<h1>You're all set — here's a quick tour</h1>

<p>Here's what each part of PHPNetMap does. You'll always find these in the top menu.</p>

<p><?php echo CHtml::link('Skip tour', array('/map/index')); ?></p>

<div class="diagnostic-cards">
    <?php foreach ($tourCards as $card): ?>
    <div class="diagnostic-card">
        <h4><?php echo CHtml::encode($card['label']); ?></h4>
        <p><?php echo CHtml::encode($card['description']); ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="row buttons">
    <?php echo CHtml::link('Finish', array('/map/index'), array('class' => 'btn btn-primary')); ?>
</div>
