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

-- 12.04.2016
CREATE TABLE `pm_shop_fieldset` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , `data` TEXT NOT NULL COMMENT 'JSONed additional fields config' , PRIMARY KEY (`id`)) ENGINE = MyISAM;

-- 19.04.2016
CREATE TABLE `pm_shop_items` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `id_category` INT UNSIGNED NOT NULL , `id_user` INT UNSIGNED NOT NULL , `name_ua` VARCHAR(255) NOT NULL , `name_en` VARCHAR(255) NOT NULL , `name_ru` VARCHAR(255) NOT NULL , `desc_ua` TEXT NOT NULL , `desc_en` TEXT NOT NULL , `desc_ru` TEXT NOT NULL , `media` TEXT NOT NULL , `price` DECIMAL(10,2) NOT NULL , `times_ordered` INT UNSIGNED NOT NULL , `times_bought` INT UNSIGNED NOT NULL , `times_viewed` INT UNSIGNED NOT NULL , `datetime_created` DATE NOT NULL , `datetime_modified` DATE NOT NULL , PRIMARY KEY (`id`), INDEX (`id_category`), INDEX (`id_user`)) ENGINE = MyISAM;

-- 20.04.2016
ALTER TABLE `pm_shop_items` ADD `is_active` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `id_user`, ADD `is_banned` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `is_active`;

ALTER TABLE `pm_shop_items` ADD `fields` TEXT NOT NULL AFTER `price`;