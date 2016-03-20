-- phpMyAdmin SQL Dump
-- version 4.0.10.12
-- http://www.phpmyadmin.net
--
-- Host: kryzhani.mysql.ukraine.com.ua
-- Generation Time: Mar 20, 2016 at 04:39 PM
-- Server version: 5.1.72-cll-lve
-- PHP Version: 5.6.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `kryzhani_mcms`
--

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog`
--

CREATE TABLE IF NOT EXISTS `pm_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `type` enum('folder','item') NOT NULL DEFAULT 'item',
  `alias` varchar(30) NOT NULL DEFAULT '',
  `relative_url` varchar(255) NOT NULL DEFAULT '',
  `full_relative_url` varchar(255) NOT NULL DEFAULT '',
  `fieldset` varchar(30) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `img_sm` varchar(255) NOT NULL DEFAULT '',
  `img_sm1` varchar(255) NOT NULL DEFAULT '',
  `img_sm2` varchar(255) NOT NULL DEFAULT '',
  `img_sm3` varchar(255) NOT NULL DEFAULT '',
  `num_views` int(11) NOT NULL DEFAULT '0',
  `num_orders` int(11) NOT NULL DEFAULT '0',
  `price` float(8,2) NOT NULL DEFAULT '0.00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ord` int(4) NOT NULL DEFAULT '0',
  `enabled` int(1) NOT NULL DEFAULT '0',
  `news` int(1) NOT NULL DEFAULT '0',
  `best` int(1) NOT NULL DEFAULT '0',
  `action` int(1) NOT NULL DEFAULT '0',
  `recomended` int(1) NOT NULL DEFAULT '0',
  `words` varchar(255) NOT NULL DEFAULT '',
  `state` int(4) NOT NULL DEFAULT '0',
  `code` varchar(255) NOT NULL DEFAULT '',
  `count_last` int(11) NOT NULL DEFAULT '0',
  `action_for_user` int(11) NOT NULL DEFAULT '0',
  `title_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_title_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_description_ru` varchar(255) NOT NULL DEFAULT '',
  `short_description_ru` mediumtext NOT NULL,
  `description_ru` mediumtext NOT NULL,
  `video_ru` mediumtext NOT NULL,
  `hot` int(1) NOT NULL,
  `cheap` int(1) NOT NULL,
  `nregular` int(1) NOT NULL,
  `tour_start` date NOT NULL,
  `tour_finish` date NOT NULL,
  `duration` varchar(255) NOT NULL,
  `season` varchar(255) NOT NULL,
  `all_places` int(11) NOT NULL,
  `booked_places` int(11) NOT NULL,
  `itinerary` mediumtext NOT NULL,
  `title_en` varchar(255) NOT NULL DEFAULT '',
  `meta_title_en` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_en` varchar(255) NOT NULL DEFAULT '',
  `meta_description_en` varchar(255) NOT NULL DEFAULT '',
  `short_description_en` mediumtext NOT NULL,
  `description_en` mediumtext NOT NULL,
  `video_en` mediumtext NOT NULL,
  `title_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_title_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_description_ua` varchar(255) NOT NULL DEFAULT '',
  `short_description_ua` text NOT NULL,
  `description_ua` text NOT NULL,
  `video_ua` text NOT NULL,
  `title_az` varchar(255) NOT NULL DEFAULT '',
  `meta_title_az` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_az` varchar(255) NOT NULL DEFAULT '',
  `meta_description_az` varchar(255) NOT NULL DEFAULT '',
  `short_description_az` text NOT NULL,
  `description_az` text NOT NULL,
  `video_az` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=89 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_baskets`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_baskets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basket_id` int(11) NOT NULL DEFAULT '0',
  `item_id` int(11) NOT NULL DEFAULT '0',
  `quantity` int(4) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `additional` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_comments`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_comments` (
  `catalog_comments_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` int(11) NOT NULL DEFAULT '0',
  `fio` varchar(255) NOT NULL DEFAULT '',
  `comments` mediumtext,
  `enabled` enum('0','1') NOT NULL DEFAULT '0',
  `dates` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`catalog_comments_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_currency`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_currency` (
  `currency_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `ratio` float(11,2) NOT NULL DEFAULT '0.00',
  `ord` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`currency_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `pm_catalog_currency`
--

INSERT INTO `pm_catalog_currency` (`currency_id`, `title`, `ratio`, `ord`) VALUES
(1, 'Доллар США', 1.00, 0),
(2, 'Гривна', 8.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_fieldsets`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_fieldsets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_text` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_fieldsets_data`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_fieldsets_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_text` varchar(30) NOT NULL DEFAULT '',
  `fieldset` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL DEFAULT '',
  `options` varchar(255) NOT NULL DEFAULT '',
  `show_list_flag` int(1) NOT NULL DEFAULT '0',
  `show_view_flag` int(1) NOT NULL DEFAULT '0',
  `ord` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_files`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ord` int(4) NOT NULL DEFAULT '0',
  `enabled` int(1) NOT NULL DEFAULT '0',
  `title_ru` varchar(255) NOT NULL DEFAULT '',
  `description_ru` mediumtext NOT NULL,
  `title_en` varchar(255) NOT NULL DEFAULT '',
  `description_en` mediumtext NOT NULL,
  `title_ua` varchar(255) NOT NULL DEFAULT '',
  `description_ua` text NOT NULL,
  `title_az` varchar(255) NOT NULL DEFAULT '',
  `description_az` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_gallery`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL DEFAULT '0',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `img_sm` varchar(255) NOT NULL DEFAULT '',
  `img_sm1` varchar(255) NOT NULL DEFAULT '',
  `img_sm2` varchar(255) NOT NULL DEFAULT '',
  `img_sm3` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ord` int(4) NOT NULL DEFAULT '0',
  `enabled` int(1) NOT NULL DEFAULT '0',
  `title_ru` varchar(255) NOT NULL DEFAULT '',
  `description_ru` mediumtext NOT NULL,
  `title_en` varchar(255) NOT NULL DEFAULT '',
  `description_en` mediumtext NOT NULL,
  `title_ua` varchar(255) NOT NULL DEFAULT '',
  `description_ua` text NOT NULL,
  `title_az` varchar(255) NOT NULL DEFAULT '',
  `description_az` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_like`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_like` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int(11) NOT NULL DEFAULT '0',
  `to_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_options`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `pm_catalog_options`
--

INSERT INTO `pm_catalog_options` (`id`, `name`, `value`) VALUES
(1, 'img_width', '800'),
(2, 'img_height', '800'),
(3, 'img_sm_width', '200'),
(4, 'img_sm_height', '200'),
(5, 'img_sm1_width', '115'),
(6, 'img_sm1_height', '115'),
(7, 'img_sm2_width', '56'),
(8, 'img_sm2_height', '56'),
(9, 'records_per_page', '1000'),
(10, 'records_per_page_admin', '1000');

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_orders`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `patr` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `delivery` int(2) NOT NULL DEFAULT '0',
  `type_pay` int(2) NOT NULL DEFAULT '0',
  `webmoney` varchar(50) NOT NULL DEFAULT '',
  `type_face` int(2) NOT NULL DEFAULT '0',
  `action_code` varchar(50) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comments` mediumtext NOT NULL,
  `items` mediumtext NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `state` int(4) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '0',
  `price` float(11,2) NOT NULL DEFAULT '0.00',
  `price_delivery` float(11,2) NOT NULL DEFAULT '0.00',
  `total_quantity` int(11) NOT NULL DEFAULT '0',
  `currency` int(11) NOT NULL DEFAULT '0',
  `xls_number` int(11) NOT NULL DEFAULT '0',
  `link_file_order` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_orders_items`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_orders_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT '0',
  `item_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `quantity` int(4) NOT NULL DEFAULT '0',
  `price` float(11,2) NOT NULL DEFAULT '0.00',
  `additional` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_preorders`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_preorders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL DEFAULT '0',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `patr` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comments` mediumtext NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_selects`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_selects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_text` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `menu_flag` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_catalog_structure`
--

CREATE TABLE IF NOT EXISTS `pm_catalog_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL DEFAULT '0',
  `toc_id` int(11) NOT NULL DEFAULT '0',
  `record_id` int(11) NOT NULL DEFAULT '0',
  `main_flag` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_common_blocks`
--

CREATE TABLE IF NOT EXISTS `pm_common_blocks` (
  `toc_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `parent` int(11) NOT NULL DEFAULT '-1',
  `depth` int(11) NOT NULL DEFAULT '-1',
  `template` int(11) unsigned NOT NULL DEFAULT '0',
  `handler` varchar(32) NOT NULL DEFAULT '',
  `order_by` int(11) unsigned NOT NULL DEFAULT '10',
  `limit_to` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`toc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pm_content_html`
--

CREATE TABLE IF NOT EXISTS `pm_content_html` (
  `content_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `announce` mediumtext NOT NULL,
  `content` mediumtext NOT NULL,
  `field1` varchar(255) NOT NULL DEFAULT '',
  `field2` varchar(255) NOT NULL DEFAULT '',
  `field3` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`content_id`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_feedback`
--

CREATE TABLE IF NOT EXISTS `pm_feedback` (
  `feedback_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `s_id` int(5) unsigned NOT NULL DEFAULT '0',
  `toc_id` int(5) unsigned NOT NULL DEFAULT '0',
  `enabled` int(1) unsigned NOT NULL DEFAULT '0',
  `sort_order` int(2) unsigned NOT NULL DEFAULT '0',
  `mail` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`feedback_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_gallery`
--

CREATE TABLE IF NOT EXISTS `pm_gallery` (
  `gallery_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '0',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `relative_url` varchar(255) NOT NULL DEFAULT '',
  `title_ru` varchar(255) NOT NULL DEFAULT '',
  `title_en` varchar(255) NOT NULL DEFAULT '',
  `title_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_title_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_title_en` varchar(255) NOT NULL DEFAULT '',
  `meta_title_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_en` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords_ua` varchar(255) NOT NULL DEFAULT '',
  `meta_description_ru` varchar(255) NOT NULL DEFAULT '',
  `meta_description_en` varchar(255) NOT NULL DEFAULT '',
  `meta_description_ua` varchar(255) NOT NULL DEFAULT '',
  `descr_ru` mediumtext NOT NULL,
  `descr_en` mediumtext NOT NULL,
  `descr_ua` mediumtext NOT NULL,
  `content_ru` mediumtext NOT NULL,
  `content_en` mediumtext NOT NULL,
  `content_ua` mediumtext NOT NULL,
  `img` varchar(255) NOT NULL DEFAULT '',
  `img_sh` varchar(255) NOT NULL DEFAULT '',
  `img_sm` varchar(255) NOT NULL DEFAULT '',
  `img_sm1` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(255) NOT NULL DEFAULT '',
  `source_url` varchar(255) NOT NULL DEFAULT '',
  `ord` int(11) NOT NULL DEFAULT '0',
  `enabled` int(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `visited` int(10) unsigned NOT NULL DEFAULT '0',
  `words` varchar(255) NOT NULL,
  PRIMARY KEY (`gallery_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1946 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_gallery_comments`
--

CREATE TABLE IF NOT EXISTS `pm_gallery_comments` (
  `comment_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор родительского комментария. Если 0 - корневой комментарий.',
  `user_id` varchar(255) NOT NULL DEFAULT '',
  `gallery_id` int(11) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment` mediumtext NOT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_gallery_options`
--

CREATE TABLE IF NOT EXISTS `pm_gallery_options` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_gallery_structure`
--

CREATE TABLE IF NOT EXISTS `pm_gallery_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL DEFAULT '0',
  `toc_id` int(11) NOT NULL DEFAULT '0',
  `record_id` int(11) NOT NULL DEFAULT '0',
  `open_this` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_languages`
--

CREATE TABLE IF NOT EXISTS `pm_languages` (
  `language_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL DEFAULT '',
  `short_title` varchar(24) NOT NULL DEFAULT '',
  `alias` varchar(64) NOT NULL DEFAULT '',
  `enabled` int(1) unsigned NOT NULL DEFAULT '0',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`language_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `pm_languages`
--

INSERT INTO `pm_languages` (`language_id`, `title`, `short_title`, `alias`, `enabled`, `ord`) VALUES
(1, 'Russian', 'ru', 'ru', 1, 1),
(4, 'Ukrainian', 'ua', 'ua', 4294967295, 3),
(5, 'English', 'en', 'en', 4294967294, 2);

-- --------------------------------------------------------

--
-- Table structure for table `pm_news`
--

CREATE TABLE IF NOT EXISTS `pm_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
  `language` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `enabled` int(1) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `descr` mediumtext,
  `content` mediumtext,
  `img_orig` varchar(255) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `post_date` datetime NOT NULL,
  `dates` date NOT NULL DEFAULT '0000-00-00',
  `author` varchar(255) NOT NULL DEFAULT '',
  `sourceName` varchar(255) NOT NULL DEFAULT '',
  `sourceUrl` varchar(255) NOT NULL DEFAULT '',
  `rubrics` varchar(255) NOT NULL DEFAULT '',
  `tags` varchar(255) NOT NULL DEFAULT '',
  `auto_start` int(1) NOT NULL DEFAULT '0',
  `dates_start` date NOT NULL DEFAULT '0000-00-00',
  `dates_end` date NOT NULL DEFAULT '0000-00-00',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `words` varchar(255) NOT NULL,
  `rubrics_list` varchar(255) NOT NULL,
  `img_sm` varchar(255) NOT NULL DEFAULT '',
  `img_sm1` varchar(255) NOT NULL DEFAULT '',
  `img_sm2` varchar(255) NOT NULL DEFAULT '',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_calendar`
--

CREATE TABLE IF NOT EXISTS `pm_news_calendar` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `s_id` int(11) unsigned NOT NULL DEFAULT '0',
  `toc_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `block_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_comments`
--

CREATE TABLE IF NOT EXISTS `pm_news_comments` (
  `comment_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL DEFAULT '',
  `news_id` int(11) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment` mediumtext,
  PRIMARY KEY (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_gallery`
--

CREATE TABLE IF NOT EXISTS `pm_news_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `descr` mediumtext,
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `img_orig` varchar(255) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `img_sm` varchar(255) NOT NULL DEFAULT '',
  `img_sm1` varchar(255) NOT NULL DEFAULT '',
  `enabled` int(1) NOT NULL DEFAULT '0',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `ord` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `img_sm2` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_options`
--

CREATE TABLE IF NOT EXISTS `pm_news_options` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  `value2` decimal(11,4) NOT NULL DEFAULT '0.0000',
  `groups` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `pm_news_options`
--

INSERT INTO `pm_news_options` (`id`, `name`, `value`, `value2`, `groups`) VALUES
(1, 'defaultWidth', 800, '800.0000', 1),
(2, 'defaultHeight', 800, '800.0000', 1),
(3, 'defaultWidthSm', 270, '270.0000', 2),
(4, 'defaultHeightSm', 183, '183.0000', 2),
(5, 'defaultWidthSm1', 137, '137.0000', 3),
(6, 'defaultHeightSm1', 92, '92.0000', 3),
(7, 'defaultWidthSm2', 126, '126.0000', 4),
(8, 'defaultHeightSm2', 126, '126.0000', 4);

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_rubrics`
--

CREATE TABLE IF NOT EXISTS `pm_news_rubrics` (
  `rubric_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `title_menu` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`rubric_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=74 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_news_structure`
--

CREATE TABLE IF NOT EXISTS `pm_news_structure` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `s_id` int(11) unsigned NOT NULL DEFAULT '0',
  `toc_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `sorttype` varchar(255) NOT NULL DEFAULT 'id',
  `lim` int(11) unsigned NOT NULL DEFAULT '5',
  `show_navi` int(1) NOT NULL DEFAULT '0',
  `block_id` int(11) NOT NULL DEFAULT '0',
  `rubrics` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_structure`
--

CREATE TABLE IF NOT EXISTS `pm_structure` (
  `s_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `title_menu` varchar(255) NOT NULL DEFAULT '',
  `title_page` varchar(255) NOT NULL DEFAULT '',
  `title_map` varchar(255) NOT NULL DEFAULT '',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` mediumtext NOT NULL,
  `meta_description` mediumtext NOT NULL,
  `level` int(11) unsigned NOT NULL DEFAULT '0',
  `alias` varchar(64) NOT NULL DEFAULT '',
  `relative_url` varchar(255) NOT NULL DEFAULT '',
  `parent` int(11) unsigned NOT NULL DEFAULT '0',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` int(1) unsigned NOT NULL DEFAULT '0',
  `enabled` int(1) unsigned NOT NULL DEFAULT '0',
  `restricted` int(1) unsigned NOT NULL DEFAULT '0',
  `access_level` int(11) NOT NULL DEFAULT '0',
  `show_search` int(1) unsigned NOT NULL DEFAULT '1',
  `show_map` int(1) unsigned NOT NULL DEFAULT '1',
  `show_menu_top` int(1) unsigned NOT NULL DEFAULT '1',
  `second_menu` int(11) NOT NULL DEFAULT '0',
  `show_menu_side` int(1) unsigned NOT NULL DEFAULT '1',
  `show_menu_bottom` int(1) unsigned NOT NULL DEFAULT '1',
  `redirect_url` varchar(255) NOT NULL DEFAULT '',
  `cache_id` int(11) unsigned NOT NULL DEFAULT '0',
  `cache_lifetime` int(11) unsigned NOT NULL DEFAULT '0',
  `creation_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `language_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  `tags` varchar(255) NOT NULL DEFAULT '',
  `count_visit` int(11) unsigned NOT NULL DEFAULT '0',
  `show_menu_top2` int(1) unsigned NOT NULL DEFAULT '1',
  `words` varchar(255) NOT NULL,
  `left_key` int(12) NOT NULL,
  `right_key` int(12) NOT NULL,
  `group_id` smallint(255) NOT NULL DEFAULT '0',
  `static` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`s_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=240 ;

--
-- Dumping data for table `pm_structure`
--

INSERT INTO `pm_structure` (`s_id`, `title`, `title_menu`, `title_page`, `title_map`, `meta_title`, `meta_keywords`, `meta_description`, `level`, `alias`, `relative_url`, `parent`, `template_id`, `type`, `enabled`, `restricted`, `access_level`, `show_search`, `show_map`, `show_menu_top`, `second_menu`, `show_menu_side`, `show_menu_bottom`, `redirect_url`, `cache_id`, `cache_lifetime`, `creation_time`, `modification_time`, `language_id`, `ord`, `tags`, `count_visit`, `show_menu_top2`, `words`, `left_key`, `right_key`, `group_id`, `static`) VALUES
(1, 'Главная страница', '', '', '', 'Главная страница', '', '', 1, 'ru', 'ru/', 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, '', 692, 0, '2010-10-04 07:01:23', '2016-03-20 16:38:58', 1, 1, 'Проект ФотоТур', 515, 0, '|14|22|6|81|80|27|25|7|9|21|', 0, 0, 1, 0),
(222, 'Ukrainian', '', '', '', 'Ukrainian', '', '', 1, 'ua', 'ua/', 0, 26, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, '', 21, 0, '2015-03-20 15:30:30', '2015-09-14 15:39:30', 4, 3, '', 0, 0, '', 0, 0, 1, 0),
(223, 'English', '', '', '', 'Mjolnir design studio based in Kyiv', '', 'Web-design, complex web implementations, identity and much more.', 1, 'en', 'en/', 0, 26, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, '', 12, 0, '2015-03-20 15:30:50', '2016-01-06 12:18:40', 5, 2, '', 0, 0, '', 0, 0, 1, 0),
(226, 'News', 'News', '', '', 'What''s happening here at Mjolnir design studio.', '', '', 2, 'wjo', 'en/wjo/', 223, 26, 0, 1, 0, 0, 1, 1, 1, 0, 1, 1, '', 4, 0, '2015-09-01 12:26:07', '2015-09-01 12:43:26', 5, 1, '', 0, 1, '', 0, 0, 1, 0),
(227, 'Вакансії', '', '', '', 'Вакансії у Mjolnir', '', '', 2, 'job', 'ua/job/', 222, 26, 0, 1, 0, 0, 1, 1, 1, 0, 1, 1, '', 5, 0, '2015-09-01 15:53:35', '2015-09-01 15:54:54', 4, 1, '', 0, 1, '', 0, 0, 1, 0),
(228, 'Вйо', '', '', '', 'Вйо — дизайномайстерня Mjolnir', '', '', 2, 'wjo', 'ua/wjo/', 222, 26, 0, 1, 0, 0, 1, 1, 1, 0, 1, 1, '', 8, 0, '2015-09-01 15:55:18', '2015-09-14 19:24:52', 4, 2, '', 0, 1, '', 0, 0, 1, 0),
(229, 'Наші роботи', '', '', '', 'Портфоліо студії Mjolnir', '', '', 2, 'we-do', 'ua/we-do/', 222, 26, 0, 1, 0, 0, 1, 1, 1, 0, 1, 1, '', 4, 0, '2015-09-01 16:17:30', '2015-09-01 16:19:13', 4, 3, '', 0, 1, '', 0, 0, 1, 0),
(232, 'Portfolio', '', '', '', 'Mjolnir portfolio', '', '', 2, 'we-do', 'en/we-do/', 223, 26, 0, 1, 0, 0, 1, 1, 1, 0, 1, 1, '', 3, 0, '2015-09-03 15:45:11', '2015-09-03 15:46:04', 5, 2, '', 0, 1, '', 0, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pm_tags`
--

CREATE TABLE IF NOT EXISTS `pm_tags` (
  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_templates`
--

CREATE TABLE IF NOT EXISTS `pm_templates` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL DEFAULT '',
  `filename` varchar(128) NOT NULL DEFAULT '',
  `containers` varchar(255) NOT NULL DEFAULT '',
  `handlers_install` varchar(255) NOT NULL DEFAULT '',
  `handlers_init` varchar(255) NOT NULL DEFAULT '',
  `handlers` varchar(255) NOT NULL DEFAULT '',
  `tpl_alias` varchar(32) NOT NULL DEFAULT '',
  `modification_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`template_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=28 ;

--
-- Dumping data for table `pm_templates`
--

INSERT INTO `pm_templates` (`template_id`, `title`, `filename`, `containers`, `handlers_install`, `handlers_init`, `handlers`, `tpl_alias`, `modification_time`) VALUES
(1, 'default', 'default', 'main, additional_1, additional_2, additional_3, additional_4', 'main(html_editor)', '', 'main_menu|logout', 'default', '2010-09-25 07:06:50'),
(3, '404', '404', 'main, footer', 'main(html_editor)', '', 'main_menu', '404', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `pm_toc`
--

CREATE TABLE IF NOT EXISTS `pm_toc` (
  `toc_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `s_id` int(11) unsigned NOT NULL DEFAULT '0',
  `container` varchar(32) NOT NULL DEFAULT '',
  `sort_order` int(11) unsigned NOT NULL DEFAULT '0',
  `handler` varchar(32) NOT NULL DEFAULT '',
  `content_id` int(11) unsigned NOT NULL DEFAULT '0',
  `enabled` int(1) unsigned NOT NULL DEFAULT '0',
  `tpl_alias` varchar(32) NOT NULL DEFAULT '',
  `inheritable` int(1) unsigned NOT NULL DEFAULT '0',
  `cache_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`toc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_toc_inh_exclusions`
--

CREATE TABLE IF NOT EXISTS `pm_toc_inh_exclusions` (
  `toc_id` int(11) unsigned NOT NULL DEFAULT '0',
  `s_id` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pm_users`
--

CREATE TABLE IF NOT EXISTS `pm_users` (
  `uid` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `post` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `info` mediumtext NOT NULL,
  `subscribe` int(1) unsigned NOT NULL DEFAULT '0',
  `reg_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valid_account` int(1) unsigned NOT NULL DEFAULT '0',
  `access_level` int(2) unsigned NOT NULL DEFAULT '0',
  `site_access` mediumtext NOT NULL,
  `cms_structure_access` mediumtext NOT NULL,
  `cms_plugins_access` varchar(255) NOT NULL DEFAULT '',
  `member_of_groups` varchar(255) NOT NULL DEFAULT '',
  `member_of_network` int(11) unsigned NOT NULL DEFAULT '0',
  `networks_access` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `patr` varchar(255) NOT NULL DEFAULT '',
  `address` mediumtext NOT NULL,
  `icq` varchar(255) NOT NULL DEFAULT '',
  `skype` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(255) NOT NULL DEFAULT '',
  `birthday` date NOT NULL DEFAULT '0000-00-00',
  `sex` int(3) NOT NULL DEFAULT '0',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1600 ;

--
-- Dumping data for table `pm_users`
--

INSERT INTO `pm_users` (`uid`, `login`, `password`, `email`, `name`, `post`, `phone`, `country`, `city`, `info`, `subscribe`, `reg_date`, `valid_account`, `access_level`, `site_access`, `cms_structure_access`, `cms_plugins_access`, `member_of_groups`, `member_of_network`, `networks_access`, `last_name`, `patr`, `address`, `icq`, `skype`, `company`, `position`, `birthday`, `sex`, `avatar`) VALUES
(1, 'admin', '9dbb300e28bc21c8dab41b01883918eb', 'ibolosig@gmail.com', 'admin', '', '', '', '', '', 0, '2011-02-25 17:43:27', 1, 90, '', '', '', '', 0, '', 'admin', '', '', '', '', '', '', '2011-02-25', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `pm_users_iplog`
--

CREATE TABLE IF NOT EXISTS `pm_users_iplog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_refresh` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_user_permissions`
--

CREATE TABLE IF NOT EXISTS `pm_user_permissions` (
  `permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(5) unsigned NOT NULL DEFAULT '0',
  `site_id` varchar(64) NOT NULL DEFAULT '',
  `plugin` varchar(64) NOT NULL DEFAULT '',
  `type` enum('site','plugin','action','structure','group') NOT NULL DEFAULT 'site',
  `object` varchar(64) NOT NULL DEFAULT '',
  `value` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pm_user_sessions`
--

CREATE TABLE IF NOT EXISTS `pm_user_sessions` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(5) unsigned NOT NULL DEFAULT '0',
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_refresh` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_forum` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `pm_user_sessions`
--

INSERT INTO `pm_user_sessions` (`id`, `uid`, `session_id`, `login_time`, `last_refresh`, `last_forum`, `ip`) VALUES
(1, 1, 'cdc4316e0acd5643677245a99dff57ee', '2016-03-20 16:37:50', '2016-03-20 16:38:58', '0000-00-00 00:00:00', '91.227.180.139');

-- --------------------------------------------------------

--
-- Table structure for table `pm_words`
--

CREATE TABLE IF NOT EXISTS `pm_words` (
  `word_id` int(11) NOT NULL AUTO_INCREMENT,
  `word_ru` varchar(255) NOT NULL DEFAULT '',
  `word_ua` varchar(255) NOT NULL DEFAULT '',
  `word_en` varchar(255) NOT NULL DEFAULT '',
  `count_view_ru` int(11) NOT NULL DEFAULT '0',
  `count_view_ua` int(11) NOT NULL DEFAULT '0',
  `count_view_en` int(11) NOT NULL DEFAULT '0',
  `enabled` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`word_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=197 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
