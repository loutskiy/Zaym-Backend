<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);
ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
ORM::configure('mysql:host=localhost;dbname=4zaym');
ORM::configure('username', '4zaym');
ORM::configure('password', 'Miha1998');