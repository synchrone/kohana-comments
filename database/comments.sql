CREATE TABLE IF NOT EXISTS `comments` (
	`id` int(11) NOT NULL auto_increment,
	`comment_type_id` int(11) NOT NULL,

	-- MPTT-Related stuff
	`lft` int(11) NOT NULL,
	`rgt` int(11) NOT NULL,
	`lvl` int(11) NOT NULL,
	`parent_id` int(11) NOT NULL,
    -- MPTT-End

    `scope` int(11) NOT NULL,

	`user_id` int(11) NOT NULL,
	`date` int(10) NOT NULL,
	`text` text NOT NULL,

    --B8
	`state` VARCHAR(16) DEFAULT 'queued' NOT NULL,
	`probability` DECIMAL(6,5),
    --end B8

	PRIMARY KEY (`id`),
	FOREIGN KEY (comment_type_id) REFERENCES comment_types(id),
	FOREIGN KEY (user_id) REFERENCES users(id),
	INDEX (`comment_type_id`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `comment_types` (
	`id` int(11) NOT NULL auto_increment,
	`type` VARCHAR(128) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
