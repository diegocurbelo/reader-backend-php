<?php
namespace API\Handlers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class NotFound {

  protected $logger;

  public function __construct(Logger $logger) {
    $this->logger = $logger;
  }

  public function __invoke(Request $request, Response $response) {
    $this->logger->warn('');

    $body = json_encode([
      'message' => 'Not found',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return $response
      ->withStatus(404)
      ->withHeader('Content-Type', 'application/json')
      ->write($body);
  }

}
