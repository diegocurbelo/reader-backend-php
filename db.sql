CREATE TABLE `users` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(256) DEFAULT NULL,
    `email` varchar(256) NOT NULL DEFAULT '',
    `facebook_id` varchar(100) DEFAULT NULL,
    `access_token` varchar(256) DEFAULT NULL,
    `access_token_expiration` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `access_token` (`access_token`(255)),
    KEY `facebook_id` (`facebook_id`),
    KEY `email` (`email`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `feeds` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(256) DEFAULT '',
    `feed_url` varchar(256) NOT NULL DEFAULT '',
    `site_url` varchar(256) DEFAULT NULL,
    `description` text,
    `logo` varchar(256) DEFAULT NULL,
    `etag` varchar(256) DEFAULT NULL,
    `last_modified` varchar(256) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `entries` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `hash` varchar(64) NOT NULL,
    `feed_id` int(11) unsigned NOT NULL,
    `title` text NOT NULL,
    `url` text,
    `content` text,
    `author` varchar(256) DEFAULT NULL,
    `date` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hash` (`hash`),
    KEY `entries__feeds` (`feed_id`),
    CONSTRAINT `entries__feeds` FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `update_log` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `total_feeds` int(11) DEFAULT NULL,
    `updated_feeds` int(11) DEFAULT NULL,
    `new_entries` int(11) DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `elapsed_time` double NOT NULL,
    `info` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_feeds` (
    `user_id` int(11) unsigned NOT NULL,
    `feed_id` int(11) unsigned NOT NULL,
    `title` varchar(256) DEFAULT NULL,
    `last_read_entry_id` int(11) unsigned NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`,`feed_id`),
    KEY `user_feeds__feeds` (`feed_id`),
    CONSTRAINT `user_feeds__feeds` FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `user_feeds__users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
