-- 26.03.2016
DROP TABLE IF EXISTS `pm_shop_catregories`;
CREATE TABLE IF NOT EXISTS `pm_shop_catregories` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_parent` int(10) UNSIGNED NOT NULL,
	`alias` varchar(255) NOT NULL,
	`id_fieldset` int(10) UNSIGNED NOT NULL,
	`is_active` enum('n','y') NOT NULL DEFAULT 'n',
	`media` text NOT NULL,
	`datetime_created` datetime NOT NULL,
	`datetime_modified` datetime DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Shop categories table';
