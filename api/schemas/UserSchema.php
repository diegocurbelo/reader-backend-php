<?php
namespace API\Schemas;

use Neomerx\JsonApi\Schema\SchemaProvider;

class UserSchema extends SchemaProvider {

  protected $resourceType = 'user';
  protected $selfSubUrl   = '/users/';

  public function getId($user) {
    return $user->id;
  }

  public function getAttributes($user) {
    return [
      'username' => $user->username,
      'name'     => $user->displayname,
      'email'    => $user->email,
    ];
  }

}
