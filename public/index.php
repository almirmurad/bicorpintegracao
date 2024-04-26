<?php

use Dotenv\Dotenv;

session_start();
require '../vendor/autoload.php';
require '../src/routes.php';

$dotenv = Dotenv::createImmutable('../', '.env');
$dotenv->load();

$router->run( $router->routes );