<?php

class cms_core {
	public static $staticdbc;

	var $debug         = true;
	var $compress_html = false;
	/*******    general    *********/
	var $root_url = '';

	var $root_path = '';
	/*******    core    ********/
	var $core_path    = '';
	var $plugins_path = '';
	var $startup_path = '';
	var $lib_path     = '';
	var $cache_path   = '';
	var $data_path    = '';
	/*******    admin    *******/
	var $admin_url        = '';
	var $admin_path       = '';
	var $themes_path      = '';
	var $theme            = 'default';
	var $admin_images_url = 'img/';
	/*******    site    ********/
	var $site_id = 'cms';
//	var $root_url = '';
	var $site_url      = '';
	var $site_path     = '';
	var $publisher_url = '';

	var $url_mode = '';
	/*******    misc    *******/
	var $db_cache_interval = 0;
	/*******    caching&smarty    **********/
	var $cache_interval  = 0;
	var $smarty_dir      = '';
	var $smarty_base_dir = '';
	var $smarty_dirs     = array('templates' => '', 'templates_c' => '', 'configs' => '', 'plugins' => '', 'cache' => '');
	/**/
	var $plugins      = array();
	var $plugins_menu = array();
	var $groups       = array();
	var $groups_menu  = array();
	var $request_vars = array('plugin' => 'plugin', 'command' => 'command', 'arguments' => 'arg');
	/*only plugins specified in those arrays would be available*/
	var $include = array('plugins' => array(), 'handlers' => array(), 'startup' => array());

	/*all plugins, specified in those arrays would NOT be available*/
	var $exclude = array('plugins' => array(), 'handlers' => array(), 'startup' => array());

	/* server software (apache or iis )*/
	var $server = 'apache';

	/* objects */
	var $smarty;
	var $structure;
	var $message_box;
	/**/
	var $page_info = array();


	var $install;
	var $tableColumn = array();

	var $defaultLang = '';

	public static $_tables = array();

	/* 404 variables */
	var $vars_404      = array('structure' => 'true');
	var $vars_falseget = array('structure' => 'true');

	/* caching operations */

	function get_cached_array($cache_file) {
		$data = $this->get_cache($cache_file);
		if ($data === false) {
			return false;
		} else {
			return unserialize($data);
		}
	}

	function get_cache($cache_file) {
		if (file_exists($cache_file)) {
			$data = file_get_contents($cache_file);
			if (!empty($data)) {
				return $data;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function save_cache($cache_file, $data) {
		$cache_dir = dirname($cache_file);
		if (!is_writeable($cache_dir)) {
			return false;
		} elseif (file_exists($cache_file) && !is_writeable($cache_file)) {
			trigger_error('ОШИБКА: Файл уже существует ' . basename($cache_file) . '');
			return false;
		} elseif ($handle = fopen($cache_file, 'wb')) {
			if (!fwrite($handle, $data)) {
				trigger_error('ОШИБКА: Невозможно произвести запись в файл ' . basename($cache_file) . '');
				return false;
			}
			fclose($handle);
			return true;
		} else {
			return false;
		}
	}

	function cache_clear() {
		if (!empty($this->smarty_dirs['cache'])) {
			$cache_path = $this->smarty_dirs['cache'];
		} else {
			$cache_path = $this->smarty_base_dir . 'cache';
		}
		$level = 0;
		$counter = 0;
		$dp = opendir($cache_path);
		if (!$dp) {
			trigger_error("Ошибка при чтении папки кэша", E_USER_ERROR);
		}
		while (!(($de = readdir($dp)) === FALSE)) {
			if (!strpos(' ' . $de, $this->site_id) === false) {
				$filename = $cache_path . "/" . $de;
				if (is_dir($filename)) {
//					$this->remove_dir($filename, $level + 1);
				} else {
					$counter++;
					unlink($filename);
				}
			}
		}
//		$this->cms->message_box->add('bottom', 'Кэш успешно очищен!', $this->plugin['menu'], 'information');
	}

	function remove_dir($dirname, $level = 1) {
		if ($handle = @opendir($dirname)) {
			while ($entry = readdir($handle)) {
				if ($entry != '.' && $entry != '..') {
					if (is_dir($dirname . '/' . $entry)) {
						$this->remove_dir($dirname . '/' . $entry, $level + 1);
					} else {
						unlink($dirname . '/' . $entry);
					}
				}
			}
			closedir($handle);
			if ($level)
				@rmdir($dirname);
			return true;
		} else {
			return false;
		}
	}

	/* database */
	function init_dbc() {

		if (!empty($this->dbc) && is_object($this->dbc)) {
			return true;
		}
		include_once($this->lib_path . 'adodb/adodb.inc.php');
		$ADODB_CACHE_DIR = $this->cache_path;
		$this->dbc = ADONewConnection(ROOT_DB_DRIVER);
		$this->dbc->cacheSecs = $this->db_cache_interval;
//		$this->dbc->debug = PM_DEBUG;
		$this->dbc->debug = false;
		if (!$this->dbc->Connect(ROOT_DB_HOST, ROOT_DB_USER, ROOT_DB_PASS, ROOT_DB_NAME)) {
			trigger_error('Database connection failed', E_USER_WARNING);
			die();
		}
		$this->dbc->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->dbc->fnExecute = 'adodb_CountExecs';
		$this->dbc->fnCacheExecute = 'adodb_CountCachedExecs';

		self::$staticdbc = $this->dbc;
	}

	/**
	 * @return ADODB_mysql
	 */
	public static function getDBC() {
		return self::$staticdbc;
	}

	protected static $_languages;
	protected static $_language;

	protected static function initLanguages()
	{
		$languages = array();
		$lang = "";
		$db = self::getDBC();

		// Читаем список поддерживаемых на сайте языков
		$sql = "SELECT language_id, short_title, alias FROM " . self::$_tables["languages"] . " ORDER BY ord ASC";
		$result = $db->Execute($sql);
//        if (!$result) {
//            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
//            return false;
//        }

		if ($result->RecordCount() > 0) {

			$counter = 1;
			while ($record = $result->FetchRow()) {

				// Заполняем массив поддерживаемых языков
				$languages[$record["language_id"]] = $record["alias"];

				// Определяем текущий язык сайта как первый в списке
				if ($counter == 1) {
					$lang = $record["alias"];
				}

				$counter++;
			}
		}

		// Сохраняем список языков и текущий язык в переменных класса
		self::$_languages = $languages;
		self::$_language = $lang;
	}

	public static function getLanguages() {
		if (!isset(self::$_languages)) {
			self::initLanguages();
		}

		return self::$_languages;
	}

	public static function getLanguage() {
		if (!isset(self::$_language)) {
			self::initLanguages();
		}

		return self::$_language;
	}

	function load_config($site_id = 'cms') {
		$config_file = cms_CORE_PATH . 'config/' . $site_id . '/config.php';
		if (!file_exists($config_file) || !is_file($config_file)) {
			die('config failed (' . $site_id . ')');
		}
		include_once($config_file);
	}

	/* operations with site structure */

	function init_structure() {
		if (!empty($this->structure) && is_object($this->structure)) {
			return true;
		}
		require_once(cms_LIB_PATH . 'cms_structure.class.php');
		if (!class_exists('cms_structure')) {
			trigger_error('Класс cms_structure не найден', E_USER_ERROR);
			return false;
		}
		$this->structure = new cms_structure();
		$this->structure->dbc = &$this->dbc;
		$this->structure->cms = &$this;
		return true;
	}

	function get_root_item() {
		$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE enabled=1 AND level=1 AND parent=0 ORDER BY ord ASC LIMIT 1';
//		echo $query;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() <> 1) {
			return $path;
		}
		$row = $result->FetchRow();
//		echo "<prE>"; print_r($row); echo "</prE>";
		return $this->format_structure_item($row);
	}

	function format_structure_item($item = array()) {
		$item['url'] = $this->publisher_url . $item['relative_url'];
		if (empty($item['title_map'])) {
			$item['title_map'] = strip_tags($item['title']);
		}
		return $item;
	}

	function update_cache_id($s_id = 0, $toc_id = 0) {
		if ($toc_id > 0) {
			$query = 'UPDATE ' . $this->tables['toc'] . ' SET cache_id=cache_id+1 WHERE toc_id=' . $toc_id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
				return false;
			}
		}
		if ($s_id > 0) {
			$query = 'UPDATE ' . $this->tables['structure'] . ' SET cache_id=cache_id+1, modification_time=now() WHERE s_id=' . $s_id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
				return false;
			}
		}
		return true;
	}

	function decode_url($url) {
		$s_id = 0;
		switch ($this->url_mode) {
			case 'alias_id':
				$pattern = '/^(.*)_(\d{1,})/u';
				if (preg_match($pattern, $url, $matches)) {
					$tmp_id = (int)$matches[2];
					$s_id = $this->check_sid($tmp_id);
				}
				break;

			case 'id':
				if (is_numeric($url)) {
					$tmp_id = (int)$url;
					$s_id = $this->check_sid($tmp_id);
				}
				break;

			default:
			case 'smart':
				$subdirs = explode('/', $url);
				while (list($i, $alias) = each($subdirs)) {
					if (empty($alias)) {
						return $path;
					}
					$level++;
					$query = 'SELECT s_id FROM ' . $this->tables['structure'] . ' WHERE enabled=1 and alias="' . addslashes($alias) . '" and level=' . $level;
					$result = $this->dbc->Execute($query);
					if (!$result) {
						trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
						return false;
					} elseif ($result->RecordCount() <> 1) {
						return $path;
					}
					$row = $result->FetchRow();
					$tmp_id = $row['s_id'];
				}
				$s_id = $tmp_id;
				break;
		}
		return $s_id;
	}

	function check_sid($s_id) {
		$query = 'SELECT s_id FROM ' . $this->tables['structure'] . ' WHERE s_id=' . $s_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() == 1) {
			$structure = $result->FetchRow();
			return $structure['s_id'];
		}
		return false;
	}

	function encode_url($s_id) {
		$structure = $this->get_structure_item($s_id);
		$url = '';
		switch ($this->url_mode) {
			case 'alias_id':
				$url = $structure['alias'] . '-' . $structure['s_id'];
				break;

			case 'id':
				$url = $s_id;
				break;

			default:
			case 'smart':
				$query = 'SELECT s_id, relative_url FROM ' . $this->tables['structure'] . ' WHERE s_id="' . $structure['parent'] . '"';
				$result = $this->dbc->Execute($query);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
					return false;
				}
				$path = array();
				$row = $result->FetchRow();
				$url = $row['relative_url'];
				break;
		}
//		die($url);
		return $url;
	}

	function get_structure_item($s_id) {
		$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE s_id=' . $s_id;
		$result = $this->dbc->Execute($query);
		//die($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() <> 1) {
			trigger_error('Duplicated structure items: ' . $s_id, E_USER_ERROR);
			return false;
		}
		$row = $result->FetchRow();
		$row['url'] = $this->publisher_url . $row['relative_url'];
		return $row;
	}

	/* misc */

	function compress_html($content) {
		$symb = array("' >'", "'< '", "'< /'", "'</ '", "'> <'");
		$codes = array(">", "<", "</", "</", "><");
		$content = preg_replace($symb, $codes, $content);
		$content = preg_replace('/\t/', ' ', $content);
		$content = preg_replace('/\r/', ' ', $content);
		$content = preg_replace('/\n/', ' ', $content);
		$content = preg_replace("/\s{3,}/", ' ', $content);
		return trim($content);
	}

	/***************************    Install        ******************************************************/
	function initInstall() {
		if (!empty($this->install) && is_object($this->install)) {
			return true;
		}
		$this->install = new InstallDB;
		$this->install->dbc = &$this->dbc;
	}

	/***************************    Install Default Table    ******************************************************/
	function installDefaultTable() {
		$this->install->tables = $this->tables;
		$this->install->tablesColumn = $this->tableColumn;

		$this->install->runInstall();
	}
	/***************************    Install Default Table (end)    ******************************************************/
	/***************************    Install (end)        ******************************************************/

	/***************************    set default var (start)    ******************************************************/
	function getAllLanguage() {
		$langs = array();
		$itsFirst = true;
		$sql = "SELECT language_id, title, short_title, alias  FROM " . $this->tables["languages"] . " ORDER BY ord ASC";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
			return false;
		} else {
			while ($item = $result->FetchRow()) {
				if ($this->defaultLang == '')
					$this->defaultLang = $item["alias"];
				$item["itsFirst"] = $itsFirst;
				$langs[$item["language_id"]] = $item;
				$itsFirst = false;
			}
		}
		return $langs;
	}
	/***************************    Install Default Table (end)    ******************************************************/
} /* cms_core class end */


function adodb_CountExecs($db, $sql, $inputarray) {
	global $ADODB_QUERY_LOG, $ADODB_TOTAL_EXECS, $ADODB_LAST_QUERY;
	if (!is_array($inputarray))
		$ADODB_TOTAL_EXECS++; elseif (is_array(reset($inputarray)))
		$ADODB_TOTAL_EXECS += sizeof($inputarray);
	else $ADODB_TOTAL_EXECS++;
//	$ADODB_LAST_QUERY = $inputarray;
	$ADODB_QUERY_LOG[] = $sql;
}

function adodb_CountCachedExecs($db, $secs2cache, $sql, $inputarray) {
	global $ADODB_TOTAL_CACHED;
	$ADODB_TOTAL_CACHED++;
}

function redirect($url = '', $header = 0) {
	if (!headers_sent() && $header > 0) {
		switch ($header) {
			case 301:
				header("HTTP/1.1 301 Moved Permanently");
				header("Status: 301 Moved Permanently");
				break;

			case 302:
				header("HTTP/1.1 302 Moved Temporarily");
				header("Status: 302 Moved Temporarily");
				break;
		}
	}
	if (($url == '/' || $url == '') && (!empty($_SERVER['HTTP_HOST']))) {
		$url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
	}
	$url = str_replace('&amp;', '&', $url);
	if (!headers_sent()) {
		header('Location: ' . $url);
		header("Connection: close");
	} else {
		$timeout = 1;
		echo '<html><head><title>Перенаправление...</title><meta name="robots" content="noindex"><link href="/admin/css/cms.css" rel="stylesheet" type="text/css">';
		echo '<meta http-equiv="Refresh" content="' . $timeout . ', URL="' . $url . '">';
		echo '<script>setTimeout(\'window.location="' . $url . '";\', ' . ($timeout * 1000) . ');</script>';
		echo '</head><body>';
		echo '<div style="padding: 10px;">Сейчас Вы будете перенаправлены на страницу назначения.<br>Нажмите <a href="' . $url . '">здесь</a> Если Ваш браузер не перешел автоматически.</div>';
		echo '</body></html>';
	}
//
}

function js_setcookie($name, $value = 0, $expire = 0, $path = '', $domain = '') {

	if (!headers_sent()) {
		return setcookie($name, $value, time() + $expire, $path, $domain);
	} else {
		/*
				echo '<script>';
				echo 'var today = new Date();';
				echo 'var expire = new Date();';
				echo 'expire.setTime(today.getTime() + '.$expire.');';
				echo 'document.cookie = '.$name.'+"="+escape('.$value.') + ";expires="+expire.toGMTString();';
				echo '</script>';
				return true;
		*/
		echo '<script>';
		echo 'var today = new Date();';
		echo 'var expire = new Date();';
		echo 'expire.setTime(today.getTime() + ' . $expire . ');';
		echo 'var curCoock = "' . $name . '="+escape("' . $value . '")+";expires="+expire.toGMTString();';
		echo 'document.cookie = curCoock;';
//		echo 'document.cookie = "'.$name.'=" + escape('.$value.') + ";expires=" + expire.toGMTString();';
		echo '</script>';
	}
}

?>