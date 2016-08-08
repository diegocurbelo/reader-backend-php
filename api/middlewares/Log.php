<?php
namespace API\Middlewares;

use PDO;
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use API\Models\User;
use API\Controllers\Base as BaseController;

class Log {

  protected $logger;
  protected $db;

  public function __construct(Logger $logger, PDO $db) {
    $this->logger = $logger;
    $this->db     = $db;
  }

  public function __invoke(Request $request, Response $response, callable $next) {

    // --
		$response = $next($request, $response);
		// --

		$stmt = $this->db->prepare('
      INSERT INTO log (started_at, action, user_id, elapsed_time)
      VALUES (:started_at, :action, :user_id, :elapsed_time)
		');
		$stmt->execute([
      ':started_at'   => date('Y-m-d H:i:s', $GLOBALS["stats_start_time"]),
			':action'       => $request->getAttribute('route')->getName(),
			':user_id'      => $request->getAttribute('user')->id,
      ':elapsed_time' => microtime(true) - $GLOBALS["stats_start_time"],
		]);

		return $response;
  }

}
