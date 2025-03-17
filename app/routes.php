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
    // Récupération initiale de la réponse
    $response = $handler->handle($request);
    
    // Vérification du header Authorization
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (empty($authHeader)) {
        $response->getBody()->write(json_encode(['error' => 'Authorization header manquant']));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    }

    // Extraction du token avec regex
    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        $response->getBody()->write(json_encode(['error' => 'Format de token invalide']));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    }

    $token = $matches[1];

    try {
        // Décodage avec gestion d'erreur améliorée
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $request = $request->withAttribute('user', (array)$decoded);
    } catch (ExpiredException $e) {
        $response->getBody()->write(json_encode(['error' => 'Token expiré']));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    } catch (SignatureInvalidException $e) {
        $response->getBody()->write(json_encode(['error' => 'Signature invalide']));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Token invalide']));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    }

    // Si tout est valide, passer à la prochaine middleware
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
    });

    $app->get('/get/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $response->getBody()->write(json_encode($db->getLigne($args['table'], $args['id']), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/post/{table}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        $responseData = $db->post($args["table"], $data);
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

    $app->put('/put/{table}/{id}', function (Request $request, Response $response, $args) use ($db) {
        $data = (array) $request->getParsedBody();
        if ($db->put($args['table'], $args['id'], $data)) {
            $response->getBody()->write("Accepted");
        } else {
            $response->getBody()->write("failed");
        }
        return $response;
    });

    $app->post('/login', function(Request $request, Response $response, $args) use ($db) {
        $data = $request->getParsedBody();
        $login = $data['login'];
        $mdp = $data['mdp'];

        $user = $db->loginExist($login, $mdp);

     if ($user) {
        $token = JWT::encode([
            'login' => $login,
            'mdp' => $mdp,
            'exp' => time() + 3600 // Expire dans 1 heure
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

