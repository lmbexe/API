<?php
use SLIMAPI\Service\Database;
use Slim\Factory\AppFactory;
use Selective\BasePath\BasePathMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Service/Database.php';
$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);

$db = new Database();


$routes = require_once __DIR__ . '/../app/routes.php';
$routes($app, $db);

$app->run();