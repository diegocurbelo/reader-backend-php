<?php

$app->group('/api', function() {

  // SESSIONS
  $this->post('/session', 'Session:create')->setName('Session:create');

  // FEEDS
  $this-> get('/feeds', 'Feeds:index')-> setName('Feeds:index');
  $this->post('/feeds', 'Feeds:create')->setName('Feeds:create');
  $this->post('/feeds/scan', 'Feeds:scan')->setName('Feeds:scan');

  // ENTRIES
  $this->  get('/feeds/{feed_id}/entries',            'Entries:index')-> setName('Entries:index');
  $this->patch('/feeds/{feed_id}/entries/{entry_id}', 'Entries:update')->setName('Entries:update');
  
});
