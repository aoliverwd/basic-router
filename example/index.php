<?php

use AOWD\Router;

include_once dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();

function echoMessage(string $message)
{
    echo $message;
}

$router->register404(fn() => echoMessage('page not found'));
$router->register('get', '/test', fn() => echoMessage('get test'));
$router->register('put', '/test', fn() => echoMessage('put test'));
$router->register('post', '/test', fn() => echoMessage('post test'));
$router->register('delete', '/test', fn() => echoMessage('delete test'));
$router->register('get', '/querystring', fn() => echoMessage($_GET['foo'] ?? ''));
$router->register('get', '/test/[0-9]+/foo', fn() => echoMessage('regex'));

$router->run();
