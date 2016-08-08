<?php
namespace API\Handlers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class NotAllowed {

  protected $logger;

  public function __construct(Logger $logger) {
    $this->logger = $logger;
  }

  public function __invoke(Request $request, Response $response, array $methods) {
    $this->logger->warn('');

    $body = json_encode([
      'message' => 'Method not allowed',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return $response
      ->withStatus(405)
      ->withHeader('Content-Type', 'application/json')
      ->write($body);
  }

}
