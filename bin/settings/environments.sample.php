<?php

use spitfire\core\Environment;

/*
 * Creates a test environment that can be used to store configuration that affects
 * the behavior of an application.
 */
$e = new Environment('test');
$e->set('db', 'mysqlpdo://root:root@localhost/cpool1');

$e->set('debug_mode', true);
$e->set('debugging_mode', $e->get('debug_mode'));

$e->set('SSO', 'http://1488571465:MZQGRvNNCCVlT0aSv0YWu69JxJtzKgLR648ovo9ScNEY@localhost/Auth/');