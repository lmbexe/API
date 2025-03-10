<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use SLIMAPI\Service\Database;





return function (App $app, Database $db) {
    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write('Hello, World!');
        return $response;
    });

    $app->get('/get/{table}', function (Request $request, Response $response, $args) use ($db) {
        $response->getBody()->write(json_encode($db->getTable($args['table']), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/get/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $response->getBody()->write(json_encode($db->getLigne($args['table'], $args['id']), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/post/{table}', function (Request $request, Response $response, $args) use ($db) {
        $table = $args["table"];
        $data = (array) $request->getParsedBody();
        $responseData = $db->post($table, $data);
        if ($responseData) {
            $response->getBody()->write("Accepted");

        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    });

    $app->delete('/delete/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        if ($db->deleteLigne($args['table'], $args['id'])) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    });
};

