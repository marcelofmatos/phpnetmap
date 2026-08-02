<?php
/* @var $this WelcomeController */

$this->breadcrumbs = array(
    'Welcome' => array('/welcome/index'),
    'Tour',
);

// 'url' isn't rendered as a link in this task (the cards are informational
// only, by design — see docs/superpowers/plans/2026-08-01-welcome-wizard.md
// Task 2), but is kept accurate to each section's real top-nav route
// (protected/views/layouts/main.php) in case a future change wires it in.
// 'icon' is trusted, hand-authored inline SVG (not request-derived), so it's
// echoed raw below rather than through CHtml::encode().
$tourCards = array(
    array(
        'label' => 'SNMP Templates',
        'url' => array('/snmpTemplate/admin'),
        'description' => 'Reusable SNMP v1/2c/3 credentials (community string or v3 security settings) that hosts reference, so you set them up once instead of on every device.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"></circle><path d="M11 12l8-8"></path><path d="M16 7l2.5 2.5"></path><path d="M13 10l2.2 2.2"></path></svg>',
    ),
    array(
        'label' => 'Hosts',
        'url' => array('/host/admin'),
        'description' => 'Your monitored switches and routers — inventory, port status, traffic graphs, and CAM/ARP tables for each device.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="17" height="6" rx="1"></rect><rect x="3.5" y="14.5" width="17" height="6" rx="1"></rect><circle cx="7" cy="6.5" r="0.9" fill="currentColor" stroke="none"></circle><circle cx="7" cy="17.5" r="0.9" fill="currentColor" stroke="none"></circle></svg>',
    ),
    array(
        'label' => 'Vlans',
        'url' => array('/vlan/admin'),
        'description' => 'The VLANs on your network, used to color and group ports and hosts on the map.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5-8 4.5-8-4.5z"></path><path d="M4 12.5l8 4.5 8-4.5"></path><path d="M4 16.5l8 4.5 8-4.5"></path></svg>',
    ),
    array(
        'label' => 'Connections',
        'url' => array('/connection/admin'),
        'description' => 'The links between hosts (which port connects to which) — this is what PHPNetMap draws as your network topology map.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="6" r="2.2"></circle><circle cx="19" cy="6" r="2.2"></circle><circle cx="12" cy="18" r="2.2"></circle><path d="M6.8 7.3L11 16"></path><path d="M17.2 7.3L13 16"></path></svg>',
    ),
    array(
        'label' => 'Search',
        'url' => array('/search/index'),
        'description' => 'Quickly find a host, MAC address, or IP across your whole inventory.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"></circle><path d="M20 20l-4.8-4.8"></path></svg>',
    ),
    array(
        'label' => 'MCP Tokens',
        'url' => array('/mcpToken/admin'),
        'description' => "API tokens for connecting AI assistants to PHPNetMap's MCP server. Whether they're read-only or read-write is a single site-wide setting on the Configuration screen, not chosen per token.",
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="1.5"></rect><path d="M12 3.5v3"></path><path d="M12 17.5v3"></path><path d="M3.5 12h3"></path><path d="M17.5 12h3"></path><path d="M6 6l1.8 1.8"></path><path d="M16.2 16.2L18 18"></path><path d="M18 6l-1.8 1.8"></path><path d="M7.8 16.2L6 18"></path></svg>',
    ),
    array(
        'label' => 'Users',
        'url' => array('/user/admin'),
        'description' => 'Manage who can log in to PHPNetMap.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.3"></circle><path d="M5 19.5c1.2-3.6 4-5.4 7-5.4s5.8 1.8 7 5.4"></path></svg>',
    ),
    array(
        'label' => 'Configuration',
        'url' => array('/config/index'),
        'description' => 'The settings you just reviewed — admin email, caching, the SNMP gateway host, and the MCP server\'s read-only/read-write mode.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M12 3.5v2.4"></path><path d="M12 18.1v2.4"></path><path d="M3.5 12h2.4"></path><path d="M18.1 12h2.4"></path><path d="M6 6l1.7 1.7"></path><path d="M16.3 16.3L18 18"></path><path d="M18 6l-1.7 1.7"></path><path d="M7.7 16.3L6 18"></path></svg>',
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
