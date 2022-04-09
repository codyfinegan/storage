<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use StorageApi\Controllers\GraphQLController;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->map(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/',
        GraphQLController::class . ':index'
    );
};
