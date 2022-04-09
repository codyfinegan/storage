<?php

declare(strict_types=1);

use StorageApi\Kernel;

require_once __DIR__ . '/../vendor/autoload.php';

(new Kernel(__DIR__ . '/../'))->handle();
