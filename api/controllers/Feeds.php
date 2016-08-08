<?php
namespace API\Controllers;

use PDO;
use API\Models\Feed;
use API\Models\Entry;
use Psr\Log\LoggerInterface as Logger;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Neomerx\JsonApi\Encoder\Encoder;

final class Feeds extends Base {

  // GET /api/feeds
  function index(Request $request, Response $response, array $args) {
    $user_id = $request->getAttribute('user')->id;

    $stmt = $this->db->prepare('
      SELECT id, title, feed_url, site_url, logo, unread_count
      FROM (
        SELECT F.id, COALESCE(UF.title, F.title) AS title, F.feed_url, F.site_url, F.logo, count(E.id) AS unread_count
        FROM users U, user_feeds UF, feeds F, entries E
        WHERE U.id = :user_id AND UF.user_id = U.id AND UF.feed_id = F.id AND F.id = E.feed_id AND E.id > UF.last_read_entry_id
        GROUP BY F.id
      UNION
        SELECT F.id, COALESCE(UF.title, F.title) AS title, F.feed_url, F.site_url, F.logo, 0 AS unread_count
        FROM users U, user_feeds UF, feeds F
        WHERE U.id = :user_id AND UF.user_id = U.id AND UF.feed_id = F.id
      ) AS temp
      GROUP BY id, title, feed_url, site_url, logo
      ORDER BY title
    ');
    $stmt->execute([
      ':user_id' => $user_id,
    ]);
    $feeds = $stmt->fetchAll(PDO::FETCH_CLASS, Feed::class);

    return $this->returnJSON($response, $feeds);
  }

}
