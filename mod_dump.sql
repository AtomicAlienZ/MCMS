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


-- 11.04.2016
ALTER TABLE `pm_shop_catregories` ADD `name_ua` VARCHAR(255) NOT NULL AFTER `is_active`, ADD `name_en` VARCHAR(255) NOT NULL AFTER `name_ua`, ADD `name_ru` VARCHAR(255) NOT NULL AFTER `name_en`;

ALTER TABLE `pm_shop_catregories` ADD `title_ua` VARCHAR(255) NOT NULL AFTER `name_ru`, ADD `title_en` VARCHAR(255) NOT NULL AFTER `title_ua`, ADD `title_ru` VARCHAR(255) NOT NULL AFTER `title_en`, ADD `meta_keywords_ua` TEXT NOT NULL AFTER `title_ru`, ADD `meta_keywords_en` TEXT NOT NULL AFTER `meta_keywords_ua`, ADD `meta_keywords_ru` TEXT NOT NULL AFTER `meta_keywords_en`;

ALTER TABLE `pm_shop_catregories` ADD `meta_desc_ua` TEXT NOT NULL AFTER `meta_keywords_ru`, ADD `meta_desc_en` TEXT NOT NULL AFTER `meta_desc_ua`, ADD `meta_desc_ru` TEXT NOT NULL AFTER `meta_desc_en`;

ALTER TABLE `pm_shop_catregories` ADD `description_ua` TEXT NOT NULL AFTER `name_ru`, ADD `description_en` TEXT NOT NULL AFTER `description_ua`, ADD `description_ru` TEXT NOT NULL AFTER `description_en`;