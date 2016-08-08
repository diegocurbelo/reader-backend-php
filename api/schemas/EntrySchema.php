<?php
namespace API\Schemas;

use Neomerx\JsonApi\Schema\SchemaProvider;

class EntrySchema extends SchemaProvider {

  protected $resourceType = 'entry';
  protected $selfSubUrl   = '/entries/';

  protected $isShowSelfInIncluded = true;

  public function getId($entry) {
    return $entry->id;
  }

  public function getAttributes($entry) {
    return [
      'feed-id'        => $entry->feed_id,
      'title'          => $entry->title,
      'published-date' => \DateTime::createFromFormat('Y-m-d H:i:s', $entry->date)->format('c'),
      'url'            => $entry->url,
      'content'        => $entry->content,
      'read'           => false,
    ];
  }

}
