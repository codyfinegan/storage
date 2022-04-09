<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;
use StorageApi\Settings\Settings;
use StorageApi\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            /**
             * Read the env config
             * @param string $key
             * @param mixed|null $default
             * @return mixed
             */
            $get_env_var = function (string $key, mixed $default = null): mixed {
                return $_ENV[$key] ?? $default;
            };

            return new Settings([
                'displayErrorDetails' => !!$get_env_var('DEBUG', false), // Should be set to false in production
                'logError' => true,
                'logErrorDetails' => true,
                'logger' => [
                    'name' => 'storage-api',
                    'path' => $get_env_var('DEBUG', false) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'db' => [
                    'host' => $get_env_var('DB_HOST'),
                    'name' => $get_env_var('DB_NAME'),
                    'username' => $get_env_var('DB_USER'),
                    'password' => $get_env_var('DB_PASSWORD'),
                    'driver' => $get_env_var('DB_DRIVER')
                ]
            ]);
        }
    ]);
};
