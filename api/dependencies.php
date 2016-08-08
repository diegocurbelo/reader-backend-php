<?php

use Monolog\Logger;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;

use API\Models\User;
use API\Models\Feed;
use API\Models\Entry;
use API\Schemas\UserSchema;
use API\Schemas\FeedSchema;
use API\Schemas\EntrySchema;

// DIC configuration
$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------
$container['logger'] = function ($c) {
  $settings = $c->get('settings');
  $logger = new Logger($settings['logger']['name']);
  $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
  $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], Logger::DEBUG));
  return $logger;
};

$container['errorHandler'] = function ($c) {
  return new API\Handlers\Error($c->get('logger'));
};

$container['notFoundHandler'] = function ($c) {
  return new API\Handlers\NotFound($c->get('logger'));
};

$container['notAllowedHandler'] = function ($c) {
  return new API\Handlers\NotAllowed($c->get('logger'));
};

$container['db'] = function ($container) {
  $cfg = $container->get('settings')['db'];
  try {
    $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['password']);
  } catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'errors' => [
        'status' => 500,
        'title'  => 'Database error',
        'detail' => 'Unable to connect to de database',
      ]
    ]);
    die();
  }
  return $pdo;
};

$container['jsonEncoder'] = function ($container) {
  $settings = $container->get('settings');
  return Encoder::instance([
    User::class  => UserSchema::class,
    Feed::class  => FeedSchema::class,
    Entry::class => EntrySchema::class,
  ], new EncoderOptions(JSON_PRETTY_PRINT, $settings['api_root']));
};

// -----------------------------------------------------------------------------
// Controller factories
// -----------------------------------------------------------------------------
$container['Session'] = function ($c) {
  return new API\Controllers\Session($c->get('logger'), $c->get('db'), $c->get('settings')['facebook']);
};

$container['Feeds'] = function ($c) {
  return new API\Controllers\Feeds($c->get('logger'), $c->get('db'), $c->get('jsonEncoder'));
};

$container['Entries'] = function ($c) {
  return new API\Controllers\Entries($c->get('logger'), $c->get('db'), $c->get('jsonEncoder'));
};
