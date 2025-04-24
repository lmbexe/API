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
        'patient',
        'infirmiere'
    ],
    'infChef' => [
        'visite',
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

    $app->post('/login', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();

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

    $app->post('/post/{table}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        $jwt = $request->getAttribute('JWT');
        $fonction = $jwt['fonction'];
        $jwtId = $jwt['id'];
        $table = $args['table'];

        if ($fonction === 'infirmiere' || $fonction === 'patient') {
            if ($table == "visite") {
                if (!isset($data['infirmiere']) || $data['infirmiere'] != $jwtId || $fonction === 'patient') {
                    $response = $response->withStatus(403)
                        ->withHeader('Content-Type', 'application/json');
                    $response->getBody()->write(json_encode(['error' => "Vous n'avez pas les droits pour poster dans cette table"]));
                    return $response;
                }
            } else {
                $response = $response->withStatus(403)
                    ->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['error' => "Vous n'avez pas les droits pour poster dans cette table"]));
                return $response;
            }
        }

        $responseData = $db->post($table, $data);
        if ($responseData) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("Failed");
        }
        return $response;
    })->add($checkToken);

    $app->delete('/delete/{table}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        $jwt = $request->getAttribute('JWT');
        $fonction = $jwt['fonction'];
        $jwtId = $jwt['id'];
        $table = $args['table'];
        $id = $data['id'];


        if ($fonction === 'infirmiere' || $fonction === 'patient') {
            if ($table == "visite") {
                if (!isset($data['infirmiere']) || $data['infirmiere'] != $jwtId || $fonction === 'patient') {
                    $response = $response->withStatus(403)
                        ->withHeader('Content-Type', 'application/json');
                    $response->getBody()->write(json_encode(['error' => "Vous n'avez pas les droits de suppression dans cette table"]));
                    return $response;
                }
            } else {
                $response = $response->withStatus(403)
                    ->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['error' => "Vous n'avez pas les droits de suppression dans cette table"]));
                return $response;
            }
        }

        if ($db->deleteLigne($table, $id)) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("Failed");
        }
        return $response;
    })->add($checkToken);

    $app->put('/put/{table}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        $jwt = $request->getAttribute('JWT');
        $fonction = $jwt['fonction'];
        $jwtId = $jwt['id'];
        $table = $args['table'];
        $id = $data['id'];

        if ($fonction === 'infirmiere' || $fonction === 'patient') {
            if ($table = "visite") {
                if ($db->isPostByThisInfi($id, $jwtId) == false || $fonction === 'patient') {
                    $response = $response->withStatus(403)
                        ->withHeader('Content-Type', 'application/json');
                    $response->getBody()->write(json_encode(['error' => "Vous n'avez pas les droits de modification pour cette table"]));
                    return $response;
                }
            }
        }

        if ($db->put($table, $id, $data)) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("Failed");
        }
        return $response;
    })->add($checkToken);


};

