<?php

$GLOBALS["stats_start_time"] = microtime(true);
	
date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

$settings = require_once __DIR__ . '/../settings.php';
$app = new \Slim\App($settings);

require_once __DIR__ . '/../api/dependencies.php';
require_once __DIR__ . '/../api/middlewares.php';
require_once __DIR__ . '/../api/routes.php';

$app->run();
