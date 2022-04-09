<?php

declare(strict_types=1);

namespace StorageApi;

use DI\Bridge\Slim\Bridge;
use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use StorageApi\Handlers\HttpErrorHandler;
use StorageApi\Handlers\ShutdownHandler;
use StorageApi\ResponseEmitter\ResponseEmitter;
use StorageApi\Settings\SettingsInterface;

class Kernel
{
    /**
     * @var string
     */
    private string $root;

    /**
     * @var bool
     */
    private bool $isProd;

    /**
     * @var Container|null
     */
    private ?Container $container = null;

    /**
     * @var App|null
     */
    private ?App $app = null;

    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $request = null;

    public function __construct(string $root)
    {
        $this->root = $root;
        $dotenv = Dotenv::createImmutable($this->root);
        $dotenv->load();

        $this->isProd = empty($_ENV['DEBUG']);
    }

    /**
     * @return ResponseInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function handle(): ResponseInterface
    {
        $app = $this->getApp();
        $request = $this->getRequest();
        $response = $app->handle($request);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        return $response;
    }

    /**
     * @return App
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getApp(): App
    {
        if ($this->app) {
            return $this->app;
        }

        $container = $this->getContainer();

        $app = Bridge::create($container);
        $callableResolver = $app->getCallableResolver();

        foreach (['middleware', 'routes'] as $file) {
            $obj = require $this->root . "/config/{$file}.php";
            $obj($app);
        }

        /** @var SettingsInterface $settings */
        $settings = $container->get(SettingsInterface::class);

        $displayErrorDetails = $settings->get('displayErrorDetails');
        $logError = $settings->get('logError');
        $logErrorDetails = $settings->get('logErrorDetails');

        // Create Request object from globals
        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $this->request = $serverRequestCreator->createServerRequestFromGlobals();

        // Create Error Handler
        $responseFactory = $app->getResponseFactory();
        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

        // Create Shutdown Handler
        $shutdownHandler = new ShutdownHandler($this->request, $errorHandler, $displayErrorDetails);
        register_shutdown_function($shutdownHandler);

        // Add Routing Middleware
        $app->addRoutingMiddleware();

        // Add Body Parsing Middleware
        $app->addBodyParsingMiddleware();

        // Add Error Middleware
        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $this->app = $app;
        return $this->app;
    }

    /**
     * @return Container
     * @throws Exception
     */
    public function getContainer(): Container
    {
        if ($this->container) {
            return $this->container;
        }
        // Instantiate PHP-DI ContainerBuilder
        $containerBuilder = new ContainerBuilder();
        if ($this->isProd) { // Should be set to true in production
            $containerBuilder->enableCompilation($this->root . '/var/cache');
        }

        // Load our config files
        foreach (['settings', 'dependencies'] as $file) {
            $obj = require $this->root . "/config/{$file}.php";
            $obj($containerBuilder);
        }

        // Build PHP-DI Container instance
        $this->container = $containerBuilder->build();

        $settings = $this->getSettings();
        $settings->set('root', $this->root);
        $settings->set('app', $this->root . '/app');

        return $this->container;
    }

    /**
     * @return RequestInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRequest(): RequestInterface
    {
        if (!$this->request) {
            $this->getApp();
        }
        return $this->request;
    }

    /**
     * @return SettingsInterface
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSettings(): SettingsInterface {
        return $this->getContainer()->get(SettingsInterface::class);
    }
}
