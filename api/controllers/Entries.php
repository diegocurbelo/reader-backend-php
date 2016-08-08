<?php
namespace API\Controllers;

use PDO;
use API\Models\Entry;
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Schema\Link;
use PicoFeed\Reader\Reader;
use PicoFeed\PicoFeedException;

final class Entries extends Base {

  // GET /api/feeds/{feed_id}/entries
  function index(Request $request, Response $response, array $args) {
    $user_id = $request->getAttribute('user')->id;
    $feed_id = $args['feed_id'];

    $offset = $request->getQueryParams()['offset'];
    if (!$offset) {
      $offset = 0;
    }
    $limit = $request->getQueryParams()['limit'];
    if (!$limit) {
      $limit = 10;
    }

    $stmt = $this->db->prepare('
      SELECT E.id, E.title, E.url, E.author, E.date, E.content, E.feed_id
      FROM users U, user_feeds UF, entries E
      WHERE U.id = :user_id AND U.id = UF.user_id AND UF.feed_id = :feed_id AND UF.feed_id = E.feed_id
        AND E.id > :offset AND E.id > UF.last_read_entry_id
      ORDER BY E.id ASC LIMIT 10
    ');
    $stmt->execute([
      ':user_id' => $user_id,
      ':feed_id' => $feed_id,
      ':offset'  => $offset,
    ]);
    $entries = $stmt->fetchAll(PDO::FETCH_CLASS, Entry::class);

    return $this->returnJSON($response, $entries);
  }


  // PATCH /api/feeds/{feed_id}/entries/{entry_id}
  function update(Request $request, Response $response, array $args) {
    $user_id  = $request->getAttribute('user')->id;
    $feed_id  = $args['feed_id'];
    $entry_id = $args['entry_id'];

    $stmt = $this->db->prepare('
      UPDATE user_feeds
      SET last_read_entry_id = :entry_id
      WHERE user_id = :user_id AND feed_id = :feed_id AND last_read_entry_id < :entry_id
    ');
    $stmt->execute([
      ':user_id'  => $user_id,
      ':feed_id'  => $feed_id,
      ':entry_id' => $entry_id,
    ]);

    return $this->returnJSON($response, null, 204);
  }

}
