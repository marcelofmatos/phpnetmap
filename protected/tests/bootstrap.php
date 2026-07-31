<?php

$yiit = dirname(__FILE__) . '/../../yii/framework/yiit.php';
$config = dirname(__FILE__) . '/../config/test.php';

require_once($yiit);

Yii::createWebApplication($config);

// PHPUnit builds its "unit" testsuite (named after phpunit.xml's
// <testsuite name="unit">) by probing class_exists('unit', true), which
// falls through to Yii's autoloader. Yii's autoloader include()s the file
// without checking it exists first, so it raises a spurious-but-harmless
// "include(unit.php): failed to open stream" warning on every test run.
// Filter out only that exact warning so it doesn't get mistaken for a
// real failure; every other warning/notice/error still surfaces normally.
set_error_handler(function ($errno, $errstr, $errfile) {
    if (strpos($errfile, 'YiiBase.php') !== false && strpos($errstr, 'unit.php') !== false) {
        return true;
    }
    return false;
}, E_WARNING);
