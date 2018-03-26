<?php

use spitfire\core\Environment;

/*
 * Creates a test environment that can be used to store configuration that affects
 * the behavior of an application.
 */
$e = new Environment('test');
$e->set('db', 'mysqlpdo://root:@localhost/cloudy');

$e->set('debug_mode', true);
$e->set('debugging_mode', $e->get('debug_mode'));