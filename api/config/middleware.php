<?php
declare(strict_types=1);

use Slim\App;
use StorageApi\Middleware\SessionMiddleware;

return function (App $app) {
    $app->add(SessionMiddleware::class);
};
