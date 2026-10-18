<?php

require_once dirname(dirname(__DIR__)).'/system/classes/class_page.php';
$C = (object) array('SITE_URL' => 'http://localhost:8092/');
$page = (new ReflectionClass('page'))->newInstanceWithoutConstructor();
$page->redirect('dashboard');
