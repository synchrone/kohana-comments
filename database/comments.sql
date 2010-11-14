CREATE TABLE IF NOT EXISTS `comments` (
	`id` int(11) NOT NULL auto_increment,
	`comment_type_id` int(11) NOT NULL,
	`parent_id` int(11) NOT NULL,
	`state` enum('ham','queued','spam') NOT NULL DEFAULT 'queued',
	`probability` DECIMAL(6,5),
	`date` int(10) NOT NULL,
	`user_id` int(11) NOT NULL,
	`text` text NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (comment_type_id) REFERENCES comment_types(id),
	INDEX (`comment_type_id`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `comment_types` (
	`id` int(11) NOT NULL auto_increment,
	`type` VARCHAR(128) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
