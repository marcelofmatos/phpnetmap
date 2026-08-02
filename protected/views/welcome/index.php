<?php
/* @var $this WelcomeController */

$this->breadcrumbs = array(
    'Welcome',
);
?>

<div class="pnm-steps">
    <span class="pnm-step is-active"><span class="pnm-step-num">1</span> Welcome</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step"><span class="pnm-step-num">2</span> Configuration</span>
    <span class="pnm-step-sep"></span>
    <span class="pnm-step"><span class="pnm-step-num">3</span> Tour</span>
</div>

<div class="pnm-hero">
    <span class="pnm-eyebrow">First run</span>
    <h1>Welcome to PHPNetMap</h1>

    <p>Hi, <strong><?php echo CHtml::encode(Yii::app()->user->name); ?></strong> — this looks like a fresh install: no hosts have been added yet.</p>

    <p>
        PHPNetMap is software for monitoring Layer 2 and Layer 3 network equipment
        with SNMP v(1/2c/3) protocol — it maps your hosts, VLANs, and how everything
        is connected.
    </p>

    <p>
        Before you dive in, let's do two quick things: review the basic
        configuration, then a short tour of what each part of the app does.
    </p>

    <div class="pnm-hero-actions">
        <?php echo CHtml::link('Get Started', array('/config/index', 'wizard' => 1), array('class' => 'btn btn-primary')); ?>
        <?php echo CHtml::link('Skip for now', array('/map/index'), array('class' => 'pnm-skip')); ?>
    </div>
</div>
