<?php
$start_time = microtime(true);

date_default_timezone_set('America/Montevideo');
require_once 'vendor/autoload.php';

use API\Models\Feed;
use API\Models\Entry;
use PicoFeed\Reader\Reader;
use PicoFeed\PicoFeedException;

// usort() comparison function for Entry
function entrySort($a, $b) {
    $a = $a->date->format('Y-m-d H:i:s');
    $b = $b->date->format('Y-m-d H:i:s');
    return $a == $b ? 0 : ($a > $b) ? 1 : -1;
}

$c = require_once 'settings.php';
$db = new PDO($c['settings']['db']['dsn'], $c['settings']['db']['user'], $c['settings']['db']['password']);

$stmt = $db->prepare('SELECT * FROM feeds');
$stmt->execute();
$feeds = $stmt->fetchAll(PDO::FETCH_CLASS, Feed::class);

$total_feeds   = $stmt->rowCount();
$updated_feeds = 0;
$new_entries   = 0;

$reader = new Reader;
$i = 0;
foreach ($feeds as $feed) {
  $feed_start_time = microtime(true);
  try {
    $added_entries = 0;
    $resource = $reader->download($feed->feed_url, $feed->last_modified, $feed->etag);
    if ($resource->isModified()) {
      $parser = $reader->getParser($resource->getUrl(), $resource->getContent(), $resource->getEncoding());
      $data = $parser->execute();

            $stmt = $db->prepare('
                UPDATE feeds SET title = :title, site_url = :site_url, description = :description, logo = :logo,
                etag = :etag, last_modified = :last_modified WHERE id = :feed_id
            ');
            $stmt->execute([
                ':feed_id'       => $feed->id,
                ':title'         => $data->title,
                ':site_url'      => $data->site_url,
                ':description'   => $data->description,
                ':logo'          => $data->logo,
                ':etag'          => $resource->getEtag(),
                ':last_modified' => $resource->getLastModified(),
            ]);

            usort($data->items, 'entrySort');

            foreach ($data->items as $item) {
                $stmt = $db->prepare('SELECT * FROM entries WHERE hash = ?');
                $stmt->execute([ $item->id ]);
                $entry = $stmt->fetchObject(Entry::class);
                if (! $entry) {
                    $stmt = $db->prepare('
                        INSERT INTO entries (hash, feed_id, title, url, author, content, `date`)
                        VALUES (:hash, :feed_id, :title, :url, :author, :content, :date)
                    ');
                    $stmt->execute([
                        ':hash'    => $item->id,
                        ':feed_id' => $feed->id,
                        ':title'   => $item->title,
                        ':url'     => $item->url,
                        ':author'  => $item->author,
                        ':date'    => $item->date->format('Y-m-d H:i:s'),
                        ':content' => $item->content,
                    ]);
                    $added_entries ++;
                }
            }
            $updated_feeds ++;
            $new_entries += $added_entries;

            if ($added_entries > 0) {
                $stmt = $db->prepare('
                    DELETE FROM entries WHERE feed_id = :feed_id AND id <= (
                        SELECT id FROM (
                            SELECT id
                            FROM entries
                            WHERE feed_id = :feed_id
                            ORDER BY id DESC
                            LIMIT 1 OFFSET 1000
                        ) foo
                    )
                ');
                $stmt->execute([
                    'feed_id' => $feed->id,
                ]);
            }

        }

        $stats[] = [
            'feed_id'     => $feed->id,
            'modified'    => $resource->isModified(),
            'new_entries' => $added_entries,
            'time'        => microtime(true) - $feed_start_time,
        ];

    } catch (PicoFeedException $e) {
        $stats[] = [
            'feed_id' => $feed->id,
            'error'   => $e->getMessage(),
            'time'    => microtime(true) - $feed_start_time,
        ];
    }

} // Cada feed

$stmt = $db->prepare('
    INSERT INTO update_log (total_feeds, updated_feeds, new_entries, started_at, elapsed_time, info)
    VALUES (:total_feeds, :updated_feeds, :new_entries, :started_at, :elapsed_time, :info)
');
$stmt->execute([
    ':total_feeds'   => $total_feeds,
    ':updated_feeds' => $updated_feeds,
    ':new_entries'   => $new_entries,
    ':started_at'    => date('Y-m-d H:i:s', $start_time),
    ':elapsed_time'  => microtime(true) - $start_time,
    ':info'          => json_encode($stats),
]);
