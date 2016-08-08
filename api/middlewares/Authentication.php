<?php
namespace API\Middlewares;

use PDO;
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use API\Models\User;

class Authentication {

  protected $logger;
  protected $db;

  public function __construct(Logger $logger, PDO $db) {
    $this->logger = $logger;
    $this->db     = $db;
  }

  public function __invoke(Request $request, Response $response, callable $next) {
    $scheme = $request->getUri()->getScheme();
    $host = $request->getUri()->getHost();
    $uri = str_replace("//", "/", $request->getUri()->getPath());

    // Por seguridad la API solo es accesible por HTTPS, excepto para desarrollo (http://localhost)
    if ('https' !== $scheme && $host !== 'localhost') {
      return $this->returnJSON($response, ['message' => 'Not available over http'], 403);
    }

    // El unico resource no autenticado es '/api/session'
    if ($uri === '/api/session') {
      return $next($request, $response);
    }

    $access_token = $this->fetchToken($request);
    if ($access_token) {
      $stmt = $this->db->prepare('SELECT * FROM users WHERE access_token = :access_token');
      $stmt->execute([
        'access_token' => $access_token,
      ]);
      $user = $stmt->fetchObject(User::class);
      $request = $request->withAttribute('user', $user);
    }

    if (!$user) {
      return $this->returnJSON($response, ['message' => 'Not authenticated'], 401);
    }

    return $next($request, $response);
  }

    // --

    private function fetchToken(Request $request) {
      $server_params = $request->getServerParams();
      if (isset($server_params[$this->options["environment"]])) {
        $header = $server_params[$this->options["environment"]];
      } else {
        $header = $request->getHeader("Authorization");
        $header = isset($header[0]) ? $header[0] : "";
      }
      if (preg_match("/Bearer\s+(.*)$/i", $header, $matches)) {
        return $matches[1];
      }
      $this->logger->debug("No token received");
      return false;
  }

	private function returnJSON(Response $response, $data, $status = 200) {
		return $response
			->withStatus($status)
      		->withHeader('Content-Type', 'application/json;charset=UTF-8')
      		->withHeader('Cache-Control', 'no-store')
      		->withHeader('Pragma', 'no-cache')
			->write(json_encode($data));
	}
}
