<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use SLIMAPI\Service\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

define('JWT_SECRET', 'lmbexe');

$checkToken = function (Request $request, RequestHandler $handler) {

    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'token manquant']));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }


    $token = str_replace('Bearer ', '', $authHeader);

    try {

        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $request = $request->withAttribute('user', (array) $decoded);
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Token invalide']));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }


    return $handler->handle($request);
};


return function (App $app, Database $db) use ($checkToken) {
    $app->get('/hello/{name}', function (Request $request, Response $response, $args) {
        $response->getBody()->write("Hello, " . $args['name']);
        return $response;
    })->add($checkToken);

    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write('Hello, World!');
        return $response;
    })->add($checkToken);

    $app->get('/get/{table}', function (Request $request, Response $response, $args) use ($db) {
        $response->getBody()->write(json_encode($db->getTable($args['table']), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($checkToken);

    $app->get('/get/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $response->getBody()->write(json_encode($db->getLigne($args['table'], $args['id']), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($checkToken);

    $app->post('/post/{table}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        $responseData = $db->post($args["table"], $data);
        if ($responseData) {
            $response->getBody()->write("Accepted");

        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    })->add($checkToken);

    $app->delete('/delete/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        if ($db->deleteLigne($args['table'], $args['id'])) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    })->add($checkToken);

    $app->put('/put/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        if ($db->put($args['table'], $args['id'], $data)) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    })->add($checkToken);

    $app->post('/login', function (Request $request, Response $response, $args) use ($db) {
        $data = $request->getParsedBody();

        if (is_null($data) || !isset($data['login']) || !isset($data['mdp'])) {
            $response = $response->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Données de connexion manquantes']));
            return $response;
        }
        $login = $data['login'];
        $mdp = $data['mdp'];

        $user = $db->loginExist($login, $mdp);

        if ($user) {
            $token = JWT::encode([
                'login' => $login,
                'mdp' => $mdp,
                'exp' => time() + 30
            ], "lmbexe", 'HS256');

            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['token' => $token]));
        } else {
            $response = $response->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Identifiants invalides']));
        }
        return $response;
    });
};

