<?php
$c = $app->getContainer();

$app->add(new API\Middlewares\Log($c->get('logger'), $c->get('db')));
$app->add(new API\Middlewares\Authentication($c->get('logger'), $c->get('db')));
