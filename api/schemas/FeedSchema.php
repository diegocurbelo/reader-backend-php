<?php
namespace API\Schemas;

use Neomerx\JsonApi\Schema\SchemaProvider;

class FeedSchema extends SchemaProvider {

  protected $resourceType = 'feed';
  protected $selfSubUrl   = '/feeds/';

  public function getId($feed) {
    return $feed->id;
  }

  public function getAttributes($feed) {
    return [
      'title'        => $feed->title,
      'feed-url'     => $feed->feed_url,
      'site-url'     => $feed->site_url,
      'unread-count' => $feed->unread_count,
    ];
  }

  public function getRelationships($feed, array $includeRelationships) {
    if ($feed->entries === null) {
      return [];

    } else {
      return [
        'entries' => [self::DATA => $feed->entries],
      ];
    }
  }

  public function getIncludePaths() {
    return [
      'entries',
    ];
  }

}
