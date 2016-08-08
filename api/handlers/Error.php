<?php
namespace API\Handlers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class Error {

  protected $logger;

  public function __construct(Logger $logger) {
    $this->logger = $logger;
  }

  public function __invoke(Request $request, Response $response, \Exception $exception) {
    $this->logger->critical($exception->getMessage(), [$exception->getCode()]);

    $body = json_encode([
      'message' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return $response
      ->withStatus(501)
      ->withHeader('Content-type', 'application/json')
      ->write($body);
  }

}
