<?php

use Firebase\JWT\ExpiredException;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use SLIMAPI\Service\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

define('ACCES', [
    'admin' => [
        'visite',
        'soins',
        'personne',
        'patient',
        'infirmiere'
    ],
    'infChef' => [
        'visite',
        'soins',
        'personne',
        'patient',
        'infirmiere'
    ],
    'infirmiere' => [
        'visite',
        'infirmiere'
    ],
    'patient' => [
        'visite',
        'patient'
    ]
]);


define('JWT_SECRET', 'lmbexe');

$checkToken = function (Request $request, RequestHandler $handler) {
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Authorization header manquant']));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    $token = str_replace('Bearer ', '', $authHeader);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));

        $request = $request->withAttribute('JWT', (array) $decoded);
    } catch (ExpiredException $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Token expiré']));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Token invalide']));
        return $response->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
};


function createJWT($expTime, $id, $fonction)
{
    $token = JWT::encode([
        'id' => $id,
        'fonction' => $fonction,
        'exp' => $expTime
    ], JWT_SECRET, 'HS256');
    return $token;
}

return function (App $app, Database $db) use ($checkToken) {


    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write('Hello, World!');
        return $response;
    })->add($checkToken);


    $app->get('/get/{table}', function (Request $request, Response $response, $args) use ($db) {
        $fonction = $request->getAttribute('JWT')['fonction'];
        $id = $request->getAttribute('JWT')['id'];

        $table = $args['table'];

        if (isset(ACCES[$fonction]) && in_array($table, ACCES[$fonction])) {
            if ($fonction == 'patient') {
                $response->getBody()->write(json_encode($db->getVisitesPatient($id, $table), JSON_PRETTY_PRINT));
            } elseif ($fonction == 'infirmiere') {
                $response->getBody()->write(json_encode($db->getVisitesInfirmiere($id, $table), JSON_PRETTY_PRINT));
            } else {
                $response->getBody()->write(json_encode($db->getTable($table), JSON_PRETTY_PRINT));
            }
        } else {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['Error' => "vous n'avez pas les droits"]));
        }





        return $response->withHeader('Content-Type', 'application/json');
    })->add($checkToken);


    $app->get('/get/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $fonction = $request->getAttribute('JWT')['fonction'];
        $id = $args['id'];
        $jwtId = $request->getAttribute('JWT')['id'];
        $table = $args['table'];

        if (isset(ACCES[$fonction]) && in_array($table, ACCES[$fonction])) {
            if ($fonction == 'patient') {
                $reponsePatient = $db->getVisitesPatient($id, $table);
                $ids = array_column($reponsePatient, 'id');
                if (!empty($reponsePatient) && in_array($jwtId, $ids)) {

                    $response->getBody()->write(json_encode($reponsePatient, JSON_PRETTY_PRINT));
                } else {
                    $response = $response->withStatus(403);
                    $response->getBody()->write(json_encode(['Error' => "vous n'avez pas les droits ou ça n'existe pas"]));
                }

            } elseif ($fonction == 'infirmiere') {
                $reponseInfirmiere = $db->getVisitesInfirmiere($id, $table);
                $ids = array_column($reponseInfirmiere, 'id');
                if (!empty($reponseInfirmiere) && in_array($jwtId, $ids)) {
                    $response->getBody()->write(json_encode($reponseInfirmiere, JSON_PRETTY_PRINT));
                } else {
                    $response = $response->withStatus(403);
                    $response->getBody()->write(json_encode(['Error' => "vous n'avez pas les droits ou ça n'existe pas"]));
                }

            } else {
                $response->getBody()->write(json_encode($db->getLigne($table, $id), JSON_PRETTY_PRINT));
            }
        } else {
            $response = $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['Error' => "vous n'avez pas les droits ou ça n'existe pas"]));
        }

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
        $id = $db->loginExist($login, $mdp);
        $fonction = $db->checkId($id);


        $expTime = time() + 3600;

        if ($id) {
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['token' => createJWT($expTime, $id, $fonction), 'fonction' => $fonction]));
        } else {
            $response = $response->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['error' => 'Identifiants invalides']));
        }
        return $response;
    });
};

