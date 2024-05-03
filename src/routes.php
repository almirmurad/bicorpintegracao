<?php
use core\Router;

$router = new Router();

$router->get('/', 'HomeController@index');

//login-logout no painel
$router->get('/login', 'LoginController@signin'); 
$router->post('/login', 'LoginController@signinAction');
$router->get('/logout', 'LoginController@signout'); 
//webhooks
$router->get('/integrar', 'IntegracaoController@index');
$router->post('/integrar', 'IntegracaoController@integraAction');
$router->get('/getAll', 'IntegracaoController@getAll');
$router->get('/delHook/{id}', 'IntegracaoController@delHook');
//Deals
$router->get('/deals', 'DealController@index');
$router->post('/winDeal', 'DealController@winDeal');
//Interactions
$router->get('/interactions', 'InteractionController@index');
$router->post('/newInteraction', 'InteractionController@createInteraction');
//Configurações
$router->get('/configs', 'ConfigController@index');
$router->post('/define', 'ConfigController@defineConfig');
//Users
$router->get('/users', 'UserController@listUsers');
$router->get('/addUser','UserController@addUser');
$router->post('/addUser','UserController@addUserAction');
$router->get('/delUser/{id}', 'UserController@delUser');


