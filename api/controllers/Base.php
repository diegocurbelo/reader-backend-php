<?php
namespace API\Controllers;

use PDO;
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Neomerx\JsonApi\Encoder\Encoder;


class Base {
	protected $db;
	protected $logger;
	protected $encoder;

	function __construct(Logger $logger, PDO $db, Encoder $encoder = null) {
    $this->db      = $db;
    $this->logger  = $logger;
    $this->encoder = $encoder;
  }

	function returnJSON(Response $response, $data, $status = 200) {
		if ($this->encoder == null) {
			$data = json_encode($data);
		} else {
			$data = $this->encoder->encodeData($data);
		}
		return $response
			->withStatus($status)
      ->withHeader('Content-Type', 'application/json;charset=UTF-8')
      ->withHeader('Cache-Control', 'no-store')
      ->withHeader('Pragma', 'no-cache')
			->write($data);
	}
}
