<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use StorageApi\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        Connection::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $db_settings = $settings->get('db');

            $connectionParams = [
                'dbname' => $db_settings['name'],
                'user' => $db_settings['username'],
                'password' => $db_settings['password'],
                'host' => $db_settings['host'],
                'driver' => $db_settings['driver'],
            ];

            $connection = DriverManager::getConnection($connectionParams);

            # Set up SQL logging
            $connection->getConfiguration()->setSQLLogger(new DebugStack());

            return $connection;
        }
    ]);
};
