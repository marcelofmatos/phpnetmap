<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');
// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'PHPNetMap',
    'defaultController' => 'map/index',
    // preloading 'log' component
    'preload' => array('log'),
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
        'bootstrap.helpers.*',
        'bootstrap.behaviors.*',
        'bootstrap.widgets.*',
    ),
    'modules' => array(
        // uncomment the following to enable the Gii tool
        'gii' => array(
            'generatorPaths' => array('bootstrap.gii'),
            'class' => 'system.gii.GiiModule',
            'password' => 'g11',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters' => array('192.168.56.*', '::1'),
        ),
    ),
    // application components
    'components' => array(
        'user' => array(
            // enable cookie-based authentication
            'allowAutoLogin' => true,
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'showScriptName' => false,
            'urlFormat' => 'path',
            'rules' => array(
                'site/page/<view:\w+>' => 'site/page',
                'host/view/<name:[a-zA-Z].+>' => 'host/viewByName',
                'host/<action:\w+>/<name:[a-zA-Z].+>' => 'host/<action>',
                'map/<action:\w+>/<hostId:[0-9]+>' => 'map/<action>',
                'vlan/tag/<tag:[0-9]+>' => 'vlan/viewByTag',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),
        // database settings are configured in database.php
        'db' => require(dirname(__FILE__) . '/database.php'),
        'errorHandler' => array(
            // use 'site/error' action to display errors
            'errorAction' => 'site/error',
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            // uncomment the following to show log messages on web pages
            /*
              array(
              'class'=>'CWebLogRoute',
              ),
             */
            ),
        ),
        'bootstrap' => array(
            'class' => 'bootstrap.components.TbApi',
        ),
    ),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => require(dirname(__FILE__).'/params.php'),
    'aliases' => array(
        'bootstrap' => realpath(__DIR__ . '/../extensions/bootstrap'),
    ),
);