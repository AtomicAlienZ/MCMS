<?php

class cms_publisher extends cms_core {
	var $debug              = true;
	var $site_id            = 'cms';
	var $root_url           = 'http://localhost/';
	var $cache_interval     = 0;
	var $smarty_dir         = '';
	var $compress_html      = false;
	var $smarty;
	var $smarty_base_dir    = '';
	var $smarty_dirs        = array('templates' => '', 'templates_c' => '', 'configs' => '', 'plugins' => '', 'cache' => '');
	var $plugins_path       = '';
	var $startup_path       = '';
	var $core_path          = '';
	var $themes_path        = '';
	var $cms_url            = '';
	var $images_url         = '';
	var $theme              = 'default';
	var $plugins            = array();
	var $handlers           = array();
	var $template_variables = array();
	var $request_vars       = array('plugin' => 'plugin', 'command' => 'command', 'arguments' => 'arg');
	## only plugins specified in those arrays would be available
	var $include = array('plugins' => array(), 'handlers' => array(), 'startup' => array());

	## all plugins, except specified in those arrays would be available
	var $exclude = array('plugins' => array(), 'handlers' => array(), 'startup' => array());

	var $message_box;
	# startup
	var $dbc;
	var $watcher;
	var $db_cache_interval = 0;
	var $page_info         = array();

	# error document
	var $ed_subject     = array('000' => 'Невідома помилка', '400' => 'Поганий запит', '401' => 'Авторизація', '403' => 'Заборонений URL', '404' => 'Неіснуючий URL', '500' => 'Внутрішня помилка сервера');
	var $ed_description = array('000' => 'Виникла невідома помилка.', '400' => 'В набраній вами адресі сервер знайшов синтаксичну помилку.', '401' => 'Набрана вами адреса вимагає попередньої авторизації.', '403' => 'Доступ до набраної вами адреси закритий.', '404' => 'Набрана вами адреса не існує.', '500' => 'Виникла внстрішня помилка сервера. Цілком можливо, що причина цеї проблеми зникне до того часу коли ви закінчите це читати.');
	/* */
	var $_benchmark_id = 0;

	var $language;
	var $top;

	var $firstLangAlias = '';
	var $allLang        = array();

	var $userInfo = array();

	function cms_publisher() {
		require_once(cms_LIB_PATH . "files.inc.php");
		$this->files = new files;
//		$this->cms_core();
	}

	function display($site_id = 'cms', $is404 = false) {
		global $ADODB_TOTAL_EXECS, $ADODB_TOTAL_CACHED;
		if (!$this->debug) {
			ob_start();
		}
		require_once(cms_LIB_PATH . 'timer.class.php');

		$this->timer = new Benchmark_Timer;

		$this->load_config($site_id);
		$this->startup();
		$this->init_dbc();
		$this->start_benchmark();

		while (list($name, $path) = each($this->smarty_dirs)) {
			if (!empty($path)) {
				$this->smarty_dirs[$name] = $path;
			} else {
				$this->smarty_dirs[$name] = $this->smarty_base_dir . $name;
			}
		}

		$this->getAllLanguages();
		$this->reindex_handlers();

		if ($this->server == 'iis') {
			$request_url = 'http://' . $_ENV['SERVER_NAME'] . $_ENV['SCRIPT_NAME'];
			if (!empty($_ENV['QUERY_STRING'])) {
				$request_url = $request_url . '?' . $_ENV['QUERY_STRING'];
			}
		} else {
			// Apache
			$request_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
		$request_url = str_replace('www.', '', $request_url);
		if (strpos($request_url, '?') > 0) {
			$request_url = substr($request_url, 0, strpos($request_url, '?'));
			$this->vars_falseget['structure'] = 'false';
		}

		$publisher_url = str_replace('www.', '', $this->publisher_url);
		$request_url = str_replace('..', '', $request_url);
		$relative_url = substr($request_url, strlen($publisher_url), strlen($request_url));
		if (strpos($relative_url, '?') > 0) {
			$relative_url = substr($relative_url, 0, strpos($relative_url, '?'));
		}
		if (substr($relative_url, -1) == '/') {
			$relative_url = substr($relative_url, 0, strlen($relative_url) - 1);
		}
		$subdirs = explode('/', $relative_url);
		if (empty($subdirs[count($subdirs) - 1])) {
			unset($subdirs[count($subdirs) - 1]);
		}
//Добавляем искуственно первый язык
//echo "<h1>'".$this->firstLangAlias."'</h1>";
		if ($this->firstLangAlias != "" && count($subdirs) > 0 && $subdirs[0] != $this->firstLangAlias) {
			$langFlip = array_flip($this->allLang);
			if (!isset($langFlip[$subdirs[0]])) {
				array_unshift($subdirs, $this->firstLangAlias);
			}
		}
//Добавляем искуственно первый язык

		$path = array();

		if (count($subdirs) > 0) {
			$path = $this->get_path($subdirs);
			if (count($path) == 0) {
			} else {
				$this->top = $path[0]["s_id"];
				$this->language = $path[0]["language_id"];
			}
//			die();
		} else {
			$path[] = $this->get_root_item();
			$this->top = $path[count($path) - 1]["s_id"];
			$this->language = $path[count($path) - 1]["language_id"];
		}
		if (!is_array($path) || count($path) == 0) {
			@ob_end_clean();

		}

//Убираем RU (начало)
		if (count($path) > 0) {
			foreach ($path as $keyPath => $itemStructure) {
				$path[$keyPath]["url"] = str_replace("/" . $this->firstLangAlias . "/", "/", $itemStructure["url"]);
				if ($this->firstLangAlias != '' && substr($itemStructure["relative_url"], 0, strlen($this->firstLangAlias)) == "ru") {
					$path[$keyPath]["relative_url"] = substr($itemStructure["relative_url"], (strlen($this->firstLangAlias) + 1));
				}
			}
		}
//Убираем RU (конец)

		$structure = $path[count($path) - 1];
		if (($structure['type'] == 2) && (!empty($structure['redirect_url']))) {
			header('Location: ' . $structure['redirect_url']);
		} elseif (($structure['type'] == 1)) {

			$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE parent=' . $structure['s_id'] . ' AND language_id="' . $this->language . '" ORDER BY ord LIMIT 1';

			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
				return false;
			} elseif ($result->RecordCount() == 0) {
				return false;
			}
			$row = $result->FetchRow();
			$item = $this->format_structure_item($row);

			$url = $item["url"];
			if ($this->firstLangAlias != '') {
				$url = str_replace("/" . $this->firstLangAlias . "/", "/", $url);
			}
			header('Location: ' . $url);
			die();

		}

		$this->InitSmarty();
		$cache_id = $structure['s_id'] . '_' . $structure['cache_id'];
		$this->smarty->cache_id = $cache_id;
		$this->smarty->compile_id = $this->site_id;
		$virtual_url = substr($relative_url, strlen($structure['relative_url']), strlen($relative_url));
		if ($virtual_url[0] == '/') {
			$virtual_url = substr($virtual_url, 1, strlen($virtual_url));
		}

		$structure['meta_description'] = htmlspecialchars(strip_tags($structure['meta_description']));

		$virtuals = explode('/', $virtual_url);
		$this->page_info = array();
		$this->page_info['lang'] = $path[0]['alias'];

		$this->page_info['langsPath'] = array();
		$this->page_info['langs'] = $this->getAllLanguages($structure['url'], $path[0]['alias']);
		$this->page_info['meta_title'] = htmlspecialchars(strip_tags($structure['meta_title']));

		$this->page_info['lang_ru'] = str_replace('/' . $path[0]['alias'] . '/', '/ru/', $structure['url']);
		$this->page_info['lang_ua'] = str_replace('/' . $path[0]['alias'] . '/', '/ua/', $structure['url']);
		$this->page_info['lang_en'] = str_replace('/' . $path[0]['alias'] . '/', '/en/', $structure['url']);

		$this->page_info['time'] = Date('Y-m-d H:i:s');
		$this->page_info['dayWeek'] = Date('D');
		$this->page_info['dayWeekN'] = Date('w');

		$this->page_info['language_id'] = $path[0]["language_id"];
		$this->page_info['request_url'] = $request_url;
		$this->page_info['relative_url'] = $relative_url;
		$this->page_info['root_url'] = $this->root_url;
		$this->page_info['main_url'] = $this->site_url;
		$this->page_info['title'] = strip_tags($structure['title']);
		$this->page_info['title_menu'] = strip_tags($structure['title_menu']);
		$this->page_info['virtuals'] = $virtuals;
		$this->page_info['structure'] = $structure;
		$this->page_info['path'] = $path;
		$this->page_info['show_top'] = 1;

		if (isset($_SERVER["QUERY_STRING"]))
			$this->page_info['query_string'] = $_SERVER["QUERY_STRING"];

		$this->setUniqueUser();

		if (!empty($this->user_data)) {
			$this->page_info['user_data'] = $this->user_data;
		} else {
			$this->page_info['user_data'] = array('uid' => 0, 'name' => '');
		}
		if (isset($_SERVER["REQUEST_URI"])) {
			if (isset($_GET) && count($_GET) > 0) {
				$this->page_info['url_print'] = $_SERVER["REQUEST_URI"] . "&media=print";
			} else {
				$this->page_info['url_print'] = $_SERVER["REQUEST_URI"] . "?media=print";
			}
		} else {
			if (isset($_GET) && count($_GET) > 0) {
				$this->page_info['url_print'] = $this->page_info['request_url'] . "&media=print";
			} else {
				$this->page_info['url_print'] = $this->page_info['request_url'] . "?media=print";
			}
		}
		$this->page_info['pathToIncTemplate'] = cms_CORE_PATH . "templates/inc/";

		$this->updateWords();

//		echo "<pre>"; print_r($this->page_info); echo "</pre>";

//Убираем RU (начало)
		if ($this->firstLangAlias != '' && substr($structure["relative_url"], 0, strlen($this->firstLangAlias)) == $this->firstLangAlias) {
			$structure["url"] = str_replace("/" . $this->firstLangAlias . "/", "/", $structure["url"]);
			$structure["relative_url"] = substr($structure["relative_url"], (strlen($this->firstLangAlias) + 1));
		}
//Убираем RU (конец)

		if ($structure['restricted'] == 1) {
			$this->setUserInfo();
			if (!isset($this->page_info["user_data"]["access_level"]) || $this->page_info["user_data"]["access_level"] != AL_DEVELOPER && $this->page_info["user_data"]["access_level"] != AL_ADMIN /* && !in_array($structure['s_id'], $this->userInfo["site_access"])*/ && !isset($this->user_data["site_access"][$structure['s_id']])) {

//				echo "<prE>"; print_r($this->userInfo); echo "</pre>"; die();

				$_SESSION["access_denied"] = 1;
				$url = $this->getLastUrl();
//				header('Location: '.$path[count($path)-2]["url"]);
				header('Location: ' . $url);
				die();
			}
		}
//		echo $this->getLastUrl();
//		print version
		if (!empty($_REQUEST['media'])) {
			$this->page_info['media'] = $_REQUEST['media'];
		} else {
			$this->page_info['media'] = 'screen';
		}
		if (!isset($_SESSION["access_denied"]))
			$_SESSION["access_denied"] = 0;
		$this->page_info['access_denied'] = $_SESSION["access_denied"];
//		end of print version


		if ($is404) {
			header("HTTP/1.0 404 Not Found");
			$structure['template_id'] = 3;
		}
		$query = 'SELECT * FROM ' . $this->tables['templates'] . ' WHERE template_id=' . $structure['template_id'];
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() <> 1) {
			//die('404 (tpl)');
		}
		$template = $result->FetchRow();
		$template['containers'] = str_replace(' ', '', $template['containers']);
		$template['containers'] = explode(',', $template['containers']);
		$template['handlers'] = str_replace(' ', '', $template['handlers']);
		$template['handlers'] = explode('|', $template['handlers']);
		$this->page_info['template'] = $template;
		$template['file'] = $this->smarty->template_dir . '/' . $template['filename'] . '.tpl';

//		die($template['file']);

		if (!file_exists($template['file'])) {
			//die('404 (tpl)');
		}


		$this->page_info['containers'] = $this->get_containers_content($structure, $template);

		$this->page_info['handlers'] = $this->get_handlers_content($template['handlers'], $structure['s_id']);


		$this->smarty->assign($this->page_info);
		if (!$this->debug) {
			@ob_end_clean();
		}
		$this->smarty->cache_id = $cache_id;


		if ($this->restrict_site_access && $structure['restricted'] == true) {
			if (!isset($this->user_data['site_permissions']) || $this->user_data['site_permissions'] == AP_NONE) {

			} elseif ($this->user_data['site_permissions'] == AP_PARTIAL && (empty($structure_access[$structure['s_id']]) || $structure_access[$structure['s_id']] == false)) {

			}
		}



		$content = $this->smarty->fetch($template['file'], $this->smarty->cache_id);

		if ($this->compress_html) {
			$content = $this->compress_html($content);
		}
		$this->timer->stop();
		$this->page_info['creation_time'] = Date('d.m.Y H:i:s');
		//$this->page_info['execution_time'] = $etime;
		$this->page_info['db_execs'] = (int)$ADODB_TOTAL_EXECS;
		$this->page_info['db_cached'] = (int)$ADODB_TOTAL_CACHED;
		//$this->save_benchmark();

		if ($this->debug) {
			$content .= '<style>
			/*.pm_hide { display: none;  visibility: hidden;  position: absolute; }*/
			.pm_show { display: block; visibility: visible; position: relative; }
			#pm_debug_panel a, #pm_debug_panel a:link, #pm_debug_panel a:visited, #pm_debug_panel a:hover, #pm_debug_panel a:active {font-family:tahoma, arial; font-size:10px; color:#990000;text-decoration:none;}
			#pm_debug_panel a:hover {text-decoration:underline;}
			#pm_debug_panel{width:150px;position:absolute;top:15px;right:15px;font-family:tahoma, arial; font-size:11px; color:#8b0000;background-color: #ffffff;padding:3px;border:1px #cc3300 solid;}
			#pm_debug_panel pre {padding-top:0;margin-top:0;}
			.debug_info{color:#000000; margin:20px; padding:5px; padding-left:25px; border: solid #000000; border-width: thin; width: 80%; background-color: #f9f9f9;font-family: Verdana, Arial, sans-serif; font-size: 11px;}
			</style>
			<script>
			function pmSwitchObj(obj_id, className){div = document.getElementById(obj_id);if (!div) {return false;} else if (typeof(className) != \'undefined\'){div.className=className;} else if (div.className == \'pm_show\') {div.className=\'pm_hide\';} else {div.className=\'pm_show\';}}
			function pmHideDebugPanel(){document.cookie = \'pm_hide_debug_panel\'+"="+1 + ";";div = document.getElementById(\'pm_debug_panel\');if (!div) {return false;} else {div.className=\'pm_hide\';} }
			</script>
			';
		}

		$queries_visible = false;
		$page_info_visible = false;

		if ($this->debug && ($this->display_debuginfo || (isset($_REQUEST['pm_page_info'])))) {
			$content .= $this->display_page_info();
			$page_info_visible = true;
		}
		if ($this->debug && ($this->display_debuginfo || (isset($_REQUEST['pm_queries'])))) {
			$content .= $this->display_query_log();
			$queries_visible = true;
		}
		if ($this->debug && $this->display_debugpanel && (empty($_COOKIE['pm_hide_debug_panel']) || 1 == $_COOKIE['pm_hide_debug_panel'])) {
			$content .= $this->display_debug_panel($page_info_visible, $queries_visible);
//            $content .= $this->display_timers($timers);
		}
		/*
				if (headers_sent()===false) {
					header('Last-Modified: '. gmdate('D, d M Y H:i:s', strtotime($structure['modification_time'])) . ' GMT');
				}
		*/
//		$this->setUserUrl();

		if ($this->firstLangAlias != '') {
			$content = str_replace("/" . $this->firstLangAlias . "/", "/", $content);
		}

		if (!$is404 && (!in_array('true', $this->vars_404) || !in_array('true', $this->vars_falseget))) {

			// That means it is 404

			return $this->display($site_id, true);
		} else {
			//print_r( $this->vars_404 );
			//print_r( $this->vars_falseget );
		}

		return $content;
//		echo '<div align="center" style="float:none;clear: both;font-family:verdana, tahoma, arial; font-size:10px; color:#888888;">'.Date('d.m.Y H:i:s').' / time: '.$etime.'s / queries: '.$ADODB_TOTAL_EXECS.' ('.$ADODB_TOTAL_CACHED.') </div>';
	}

	function startup() {
		$startup = array();
		$cache_file = $this->cache_path . $this->site_id . '-startup_cache.php';
		$cache = $this->get_cache($cache_file);
		if (is_array($cache) && (count($cache) > 0)) {
			$startup = $cache;
		} else if (count($this->include['startup']) > 0) {
			while (list(, $plugin_dir) = each($this->include['startup'])) {
				if ((is_dir($this->startup_path . $plugin_dir)) && file_exists($this->startup_path . $plugin_dir . '/startup.php') && (array_search($plugin_dir, $this->exclude['startup']) === false)) {
					$startup[$plugin_dir] = $this->startup_path . $plugin_dir . '/startup.php';
					//die($startup[$plugin_dir]);
				}
			}
			$this->save_cache($cache_file, serialize($startup));
		}
		ksort($startup);

		$startup['users'] = $this->startup_path . 'users/startup.php';

		while (list($dir, $file) = each($startup)) {
			include_once($file);
		}
	}

	function start_benchmark() {
		return false;
		$insert = array('hit_time' => "now()");
		$insert_fields = '';
		$insert_values = '';
		$sql_sep = '';
		while (list($field, $value) = each($insert)) {
			$insert_fields .= $sql_sep . $field;
			$insert_values .= $sql_sep . $value;
			$sql_sep = ', ';
		}
		$query = 'INSERT INTO ' . $this->tables['benchmark'] . ' (' . $insert_fields . ') VALUES(' . $insert_values . ')';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		}
		$this->_benchmark_id = $this->dbc->insert_id();
		return true;
	}

	function getAllLanguages($structureUrl = '', $path = '') {
		$ret = array();
		$sql = "SELECT alias FROM " . $this->tables["structure"] . " WHERE level=1 AND enabled=1 ORDER BY ord";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount() > 0) {
		}
		while ($row = $result->FetchRow()) {
			$ret[] = $row["alias"];
			$this->allLang[] = $row["alias"];
			if (trim($structureUrl) != '' && $this->firstLangAlias != '') {
//				echo $structureUrl."<bR>";
//				echo "firstLangAlias: ".$this->firstLangAlias." - - - - lang: ".$this->page_info['lang']." - - - - alias: ".$row["alias"]."<Br>";
				$url = '';
				if ($this->page_info['lang'] == $row["alias"]) {
//					echo "1";
					$url = $structureUrl;
				} elseif ($this->firstLangAlias == $row["alias"]) {
//					echo "2";
					$url = str_replace('/' . $this->page_info['lang'] . '/', '/', $structureUrl);
				} else {
//					echo "3";
					$url = str_replace(ROOT_URL, ROOT_URL . $row["alias"] . "/", $structureUrl);
				}
//				echo $url."<br><br>";
				$this->page_info['langsPath'][] = $url;
//				$this->page_info['langsPath'][] = str_replace('/'.$this->firstLangAlias.'/', '/'.$row["alias"].'/', $structureUrl);
			}
			if ($this->firstLangAlias == '') {
				$this->firstLangAlias = $row["alias"];
			}
		}
		if (isset($this->page_info['langsPath'])) {
//			echo "<pre>"; print_r($this->page_info['langsPath']); echo "</pre><Br>";
		}
		return $ret;
	}

	function reindex_handlers() {
		if ($handle = opendir($this->plugins_path)) {
			while (false !== ($file = readdir($handle))) {
				if ($file == "." || $file == ".." || (!is_dir($this->plugins_path . $file))) {
					continue;
				} else if (file_exists($this->plugins_path . $file . '/plugin-info.php') && (array_search($file, $this->exclude['plugins']) === false)) {
					if ($plugin_info = $this->_load_plugin_info($this->plugins_path . $file . '/plugin-info.php')) {
						$plugin_info['path'] = $this->plugins_path . $file . '/';
						$handler_file = $plugin_info['path'] . 'handler.class.php';
						if (file_exists($handler_file)) {
							$this->handlers[$plugin_info['name']] = $plugin_info;
						}
					}
				}
			}
			closedir($handle);
			ksort($this->handlers);
			//print_r($this->handlers);
		} else {
			trigger_error("Error opening " . $this->plugins_path . "");
		}
	}

	function _load_plugin_info($plugin_file) {
		include_once($plugin_file);
		if (!empty($plugin) && is_array($plugin)) {
			return $plugin;
		} else {
			return false;
		}
	}

	function get_path($subdirs) {
		$path = array();
		$relative_urls = array();
		$aliases = $subdirs;
//		echo "<pre>"; print_r($subdirs); echo "</pre>";
		while ( /*count($relative_urls)<3 && */
			count($aliases) > 0) {
			$relative_urls[] = 'relative_url="' . implode('/', $aliases) . '/"';
			array_pop($aliases);
		}
		$rel_cond = implode(' OR ', $relative_urls);


//		echo "<pre>"; print_r($relative_urls); echo "</pre>";

		//Проверка на ошибку 404
		//добавил тут. паша. то что выше строчку.
		$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE enabled=1 and ' . $relative_urls[0] . ' ORDER BY level DESC LIMIT 1';
		$result = $this->dbc->Execute($query);

		//print_r($relative_urls[0]);

		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() != 1) {
			$this->vars_404['structure'] = 'false';
		}


		$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE enabled=1 and (' . $rel_cond . ') ORDER BY level DESC LIMIT 1';
//		echo $query."<br>";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() == 1) {
			$structure = $result->FetchRow();
			$structure1 = $structure;
//			$path[] = $this->format_structure_item($structure);
			while ($structure["parent"] > 0) {
				$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE s_id=' . $structure['parent'] . '';
				$result = $this->dbc->Execute($query);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
					return false;
				} else {
					$structure = $result->FetchRow();
					$structure["title"] = strip_tags($structure["title"]);
					$path[] = $this->format_structure_item($structure);
				}
			}
			$path = array_reverse($path);
			$structure1["title"] = strip_tags($structure1["title"]);
			$itStructure = $this->format_structure_item($structure1);
			$itStructure["relative_url"] = str_replace($this->firstLangAlias . "/", "", $itStructure["relative_url"]);
			$path[] = $itStructure;
//			echo "<pre>"; print_r($path); echo "</pre>";
			return $path;
		}

		$level = 0;
		while (list($i, $alias) = each($subdirs)) {
			if (empty($alias)) {
				return $path;
			}
			$level++;
			$query = 'SELECT * FROM ' . $this->tables['structure'] . ' WHERE enabled=1 and alias="' . addslashes($alias) . '" and level=' . $level;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
				return false;
			} elseif ($result->RecordCount() <> 1) {
				return $path;
			}
			$row = $result->FetchRow();
			$path[] = $this->format_structure_item($row);
		}
		return $path;
	}

	function InitSmarty() {
		if (!is_object($this->smarty)) {
			require_once(SMARTY_DIR . 'Smarty.class.php');
			$smarty = new Smarty;
			/*
			$smarty->assign(array(
								 "SITE_URL"	=> $this->root_url,
								 "images_url"  => $this->images_url
								 ));
								 */
			$smarty->template_dir = $this->smarty_dirs['templates'];
			$smarty->compile_dir = $this->smarty_dirs['templates_c'];
			$smarty->config_dir = $this->smarty_dirs['configs'];
			$smarty->plugins_dir = array($this->smarty_dirs['plugins']);
			$smarty->cache_dir = $this->smarty_dirs['cache'];
			$smarty->force_compile = PM_DESIGN;
			$smarty->compile_check = PM_DESIGN;
			$smarty->compile_id = $this->site_id;
			$smarty->cache_lifetime = $this->cache_interval;
			$smarty->caching = $this->caching;
			$this->smarty = &$smarty;
			/*$this->smarty->register_resource("db", array("db_get_template",
									   "db_get_timestamp",
									   "db_get_secure",
									   "db_get_trusted"));
									   */
		}
	}

	function setUniqueUser() {
		$this->user_data["unique"]["session"] = "";
		$this->user_data["unique"]["counter"] = 0;
		$this->user_data["unique"]["catalog_id"] = 0;
		$this->user_data["unique"]["code"] = "";

		if (!empty($_SERVER['HTTP_USER_AGENT']))
			$user_agent = trim($_SERVER['HTTP_USER_AGENT']); else $user_agent = '';
//		$session_unique_id = $this->user_data["ip"]["ip"].$this->user_data["ip"]["proxy"].$user_agent.Date('dmY', time());
		$session_unique_id = $this->user_data["ip"]["ip"] . Date('dmY', time());
		$session_unique_id = md5($session_unique_id);
		$this->user_data["unique"]["session"] = $session_unique_id;

		$sql = "SELECT COUNT(*) as counts FROM " . $this->tables["toc"] . " WHERE handler='action_code' AND enabled=1";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount() > 0) {
			$item = $result->FetchRow();
			if ($item["counts"] == 0) {
				return;
			}
		} else {
			return;
		}


		$sql = "SELECT * FROM " . $this->tables["user_unique"] . " WHERE session='" . $this->user_data["unique"]["session"] . "'";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount() > 0) {
			$item = $result->FetchRow();
			$this->user_data["unique"]["counter"] = $item["counter"];
			$sql = "SELECT * FROM " . $this->tables["user_code"] . " WHERE user_unique_id='" . $item["id"] . "' AND closed=0";
//			echo $sql."<br>";
			$result = $this->dbc->Execute($sql);
			if ($result && $result->RecordCount() > 0) {
				$item = $result->FetchRow();
				$this->user_data["unique"]["code"] = $item["code"];
				$this->user_data["unique"]["catalog_id"] = $item["catalog_id"];
			}
		} else {
			$sql = "SELECT MAX(counter) as counts FROM " . $this->tables["user_unique"] . "";
//			echo $sql."<br>";
			$result = $this->dbc->Execute($sql);
			if ($result && $result->RecordCount() > 0) {
				$item = $result->FetchRow();
				$counter = (int)$item["counts"] + 1;
			} else {
				$counter = 1;
			}
			$this->user_data["unique"]["counter"] = $counter;

			$sql = "INSERT INTO " . $this->tables["user_unique"] . " SET session='" . $this->user_data["unique"]["session"] . "', created=NOW(), counter='" . $counter . "'";
//			echo $sql."<br>";
			$this->dbc->Execute($sql);
			$new_id = $this->dbc->insert_id();

			$sql = "SELECT id, action_for_user FROM pm_ncatalog WHERE `type`='item' AND action_for_user>0 AND enabled=1 ORDER BY action_for_user DESC";
//			echo $sql."<br>";
			$result = $this->dbc->Execute($sql);
			if ($result && $result->RecordCount() > 0) {
				$catalog_id = 0;
				while ($item = $result->FetchRow()) {
//					echo "catalog_id: ".$catalog_id." counter: ".$counter." action_for_user: ".$item["action_for_user"]."<br>";
					if ($catalog_id == 0 && $counter % $item["action_for_user"] == 0) {
						$catalog_id = $item["id"];
					}
				}
				if ($catalog_id > 0) {
					$code = $new_id . substr($this->user_data["unique"]["session"], 0, 12 - strlen((string)$new_id));
					$this->user_data["unique"]["code"] = $code;
					$this->user_data["unique"]["catalog_id"] = $catalog_id;
					$sql = "INSERT INTO " . $this->tables["user_code"] . " SET code='" . $code . "', catalog_id='" . $catalog_id . "', user_unique_id='" . $new_id . "', created=NOW()";
//					echo $sql."<br>";
					$this->dbc->Execute($sql);
				}
			}

		}
	}

	function updateWords($words = '') {
		if (empty($words)) {
			$words = $this->page_info['structure']["words"];
		}
		$arrFlip = array();
		if (!empty($words)) {
			$arr = explode("|", substr($words, 1, -1));
			$fullArr = array();
			foreach ($arr as $w_id) {
				if ((int)$w_id > 0) {
					$fullArr[] = $w_id;
				}
			}
			if (count($fullArr) > 0) {
				$sql = "UPDATE pm_words SET count_view_" . $this->page_info['lang'] . "=(count_view_" . $this->page_info['lang'] . "+1) WHERE word_id IN (" . implode(",", $fullArr) . ")";
				$this->dbc->Execute($sql);
			}
		}
	}

	function getLastUrl() {
		$sql = "SELECT url FROM " . $this->tables["users_url"] . " WHERE uid='" . (int)$this->user_data["uid"] . "' AND url<>'" . addslashes($this->page_info['request_url']) . "' ORDER BY dates DESC LIMIT 0,1";
//		echo $sql."<Br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount() > 0) {
			$row = $result->FetchRow();
			return $row["url"];
		} else {
			return $path[count($path) - 2]["url"];
		}
	}

	function get_containers_content($structure, $template) {

		if (!is_array($template['containers']) || count($template['containers']) == 0) {
			return false;
		}

		$output = array();

		/* генеруєм контент для модулів вказаних в шаблоні для кожного контейнера */
		$containers = str_replace(' ', '', $template['handlers_init']);
		$containers = explode('|', $containers);

		while (list(, $container) = each($containers)) {
			if (empty($container)) {
				continue;
			}

			$container_name = substr($container, 0, strpos($container, '('));
			$handlers = substr($container, strpos($container, '(') + 1, strpos($container, ')') - strpos($container, '(') - 1);
			$handlers = explode('|', $handlers);
			$counter = 0;

			while (list(, $handler) = each($handlers)) {
				if (empty($handler)) {
					continue;
				}
				$toc = array('toc_id' => 0, 's_id' => $structure['s_id'], 'container' => $container_name, 'handler' => $handler, 'counter' => $counter++);
				if (!empty($this->handlers[$toc['handler']])) {
					$item['handler'] = $toc['handler'];
					$item['counter'] = $counter++;
					$item['output'] = $this->exec_handler($toc);
					if ($item['output'] === false) {
						continue;
					}
					$output[$toc['container']][] = $item ;
				} else {
					trigger_error('Handler not found: ' . $toc['handler'], E_USER_NOTICE);
				}
			}

		}

		/* генеруєм контент для модулів добавлених через CMS */
		$containers = $template['containers'];
		$excl = array();
		$query = 'SELECT * FROM ' . $this->tables['toc_inh_exclusions'] . ' WHERE s_id=' . $structure['s_id'];
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		} else {
			while ($row = $result->FetchRow()) {
				$excl[] = $row['toc_id'];
			}
		}
		if (count($excl) > 0) {
			$excl_cond = 'and toc.toc_id NOT IN (' . implode(',', $excl) . ')';
		} else {
			$excl_cond = '';
		}
		$conds = array('1');
		while (list(, $c_name) = each($containers)) {
			if (!empty($c_name)) {
				$conds[] = 'toc.container="' . addslashes($c_name) . '"';
			}
		}
		if (count($conds) == 0) {
			$conds = array('1');
		}
		$root = $this->get_root_item();
		$this->getAllParent($structure['s_id'], $parentArr);
		$query = 'SELECT toc.* FROM ' . $this->tables['toc'] . ' toc WHERE (toc.s_id in (' . implode(",", $parentArr) . ') and toc.inheritable=1 and toc.enabled=1 OR toc.s_id=' . $structure['s_id'] . ') AND (' . implode(' OR ', $conds) . ') ' . $excl_cond . ' AND toc.enabled=1 ORDER BY toc.container, toc.sort_order DESC, toc.toc_id ASC'; // Убрал наследование с главной страницы на все//А я кажись поставил:)
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		}
		$counter = 0;
		while ($toc = $result->FetchRow()) {
			if (empty($this->handlers[$toc['handler']])) {
				trigger_error('Handler not found: ' . $toc['handler'], E_USER_NOTICE);
				continue;
			}
			if (!empty($toc["urls"])) {
				$find = false;
				$urlsArr = explode("\r\n", $toc["urls"]);
				$tmpUrl = $urlsArr;
				foreach ($tmpUrl as $tUrl) {
					if (stristr($tUrl, "www") === false) {
						$urlsArr[] = str_replace("http://", "http://www.", $tUrl);
					}
				}
//				echo "<pre style='text-align: left'>`"; print_r($urlsArr); echo "`</pre>";
//				echo "<pre style='text-align: left'>`"; print_r($this->page_info); echo "`</pre>";
				$startCheckUrl1 = $this->page_info["structure"]["url"];
				if (stristr($startCheckUrl1, "www") === false) {
					$startCheckUrl2 = str_replace("http://", "http://www.", $startCheckUrl1);
				}
//				echo $startCheckUrl1." ".$startCheckUrl2."<br>";
				$checkUrl = array();
				if (count($this->page_info["virtuals"]) > 0) {
					$url1 = $startCheckUrl1;
					$url2 = $startCheckUrl2;
					foreach ($this->page_info["virtuals"] as $virt) {
						$url1 .= $virt . "/";
						$url2 .= $virt . "/";
					}
					$checkUrl[] = $url1;
					$checkUrl[] = substr($url1, 0, -1);
					$checkUrl[] = $url2;
					$checkUrl[] = substr($url2, 0, -1);
				}

				$resIntersect = array_intersect($checkUrl, $urlsArr);

				if (count($resIntersect) == 0) {
					continue;
				}
			}
			$toc['counter'] = $counter++;

			$item['handler'] = $toc['handler'];
			$item['output'] = $this->exec_handler($toc);

			if ($item['output'] === false) {
				continue;
			}

			$output[$toc['container']][] = $item;

			//die($item);

		}

		return $output;
	}

	/*    function display_timers($timers)
		{
			$total = array_shift($timers);
			$max_width = 120;
			$max_time = 0;
			while (list($alias, $time) = each($timers)) {
				$max_time = max($time, $max_time);
			}
			reset($timers);
			$output = '<div id="pm_timers_bar" class="pm_hide" onclick="javascript:pmSwitchObj(\'pm_timers_bar\');" style="width:290px;position:absolute;top:100px;right:15px;font-family:arial, tahoma; font-size:10px; color:#8b0000;background-color: #ffffff;padding:3px;border:1px #cc3300 solid;cursor: pointer;">';
			while (list($alias, $time) = each($timers)) {
				$width = ceil($time * $max_width / $max_time);
				$output .= '<div style="width:120px;float:left;text-align:right;padding-right:5px;">' . $alias . '</div>';
				$output .= '<div style="width:40px;float:left;">' . $time . '</div>';
				$output .= '<div style="float:left;margin-left:0px;background-color: #cc3300;width:' . $width . 'px;height:10px;"></div>';
				$output .= '<div style="float:none;clear:both;"></div>';
			}
			$output .= '<div style="width:80px;float:left;text-align:right;padding-right:5px;">total</div>';
			$output .= '<div style="width:40px;float:left;">' . $total . '</div>';
			$output .= '<div style="float:none;clear:both;"></div>';
			$output .= '</div>';
			return $output;
		}*/

	function exec_handler($toc) {
		$handler = $this->handlers[$toc['handler']];
//		echo $handler['name'].' - '.$this->page_info['lang'].'<br>';
		$handler_file = $handler['path'] . 'handler.class.php';
		if (file_exists($handler_file)) {
			include_once($handler_file);
			$handler_class_name = $handler['name'] . '_handler';
			if (!class_exists($handler_class_name)) {
				trigger_error('Plugin interface failed, undefined class: ' . $handler_class_name . ' in ' . basename($handler_file), E_USER_WARNING);
				return false;
			}
			$content_handler = new $handler_class_name();
			$content_handler->plugin = $handler;
			$content_handler->cms = &$this;
			$content_handler->page_info = &$this->page_info;
			$content_handler->dbc = &$this->dbc;
			if (file_exists($handler['path'] . 'plugin.config.php')) {
				//include_once($handler['path'].'plugin.config.php');
				include($handler['path'] . 'plugin.config.php');
				$content_handler->plugin_config = $plugin_config;
			}
			$this->smarty->cache_id = $this->site_id . '_' . md5(implode('_', $toc));
			$this->smarty->clear_all_assign();
			$this->smarty->assign($this->page_info);
			$handler_result = $content_handler->exec($toc);
			//print_r($handler_result);
			return $handler_result;
		} else {
			trigger_error('Plugin interface failed, file not exists: ' . basename($handler_file), E_USER_NOTICE);
			return false;
		}
	}

	function getAllParent($s_id, &$parentArr) {
		$parentArr[] = $s_id;
		$sql = "SELECT parent FROM " . $this->tables['structure'] . " WHERE s_id='" . $s_id . "'";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount() > 0) {
			while ($row = $result->FetchRow()) {
				if ($row["parent"])
					$this->getAllParent($row["parent"], $parentArr);
			}
		}
	}

	function get_handlers_content($handlers, $s_id) {
		$output = array();
		while (list(, $handler) = each($handlers)) {
			if (empty($handler)) {
				continue;
			}
			$toc = array('toc_id' => 0, 's_id' => $s_id, 'container' => '', 'handler' => $handler, 'content_id' => 0);
			if (!empty($this->handlers[$toc['handler']])) {
				$output[$toc['handler']] = $this->exec_handler($toc);
			} else {
				trigger_error('Handler not found: ' . $handler, E_USER_NOTICE);
			}
		}
		return $output;
	}

	function compress_html($content) {
		$symb = array("' >'", "'< '", "'< /'", "'</ '", "'> <'");
		$codes = array(">", "<", "</", "</", "><");
		$content = preg_replace($symb, $codes, $content);
		$content = preg_replace('/\r/', '', $content);
		$content = preg_replace('/\n/', '', $content);
		$content = preg_replace("/\s{3,}/", ' ', $content);
		return trim($content);
	}

	function save_benchmark() {
		$insert = array('hit_time' => time(), 'version' => 0, 'cached' => 0, 'e_time' => $this->page_info['execution_time'], 'queries' => $this->page_info['db_execs'], 'queries_cached' => $this->page_info['db_cached']);
		$insert_fields = '';
		$insert_values = '';
		$sql_sep = '';
		while (list($field, $value) = each($insert)) {
			$insert_fields .= $sql_sep . $field;
			$insert_values .= $sql_sep . $value;
			$sql_sep = ', ';
		}
		//	$query = 'INSERT INTO '.$this->tables['benchmark'].' ('.$insert_fields.') VALUES('.$insert_values.')';
		//	$result = $this->dbc->Execute($query);
//		if (!$result) {
		//trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
		//return false;
		//}
		//$this->_benchmark_id = $this->dbc->insert_id();
		return true;

		$update = array('hit_time' => "now()", 'version' => 1, 'cached' => 0, 'e_time' => $this->page_info['etime'], 'queries' => $this->page_info['db_execs'], 'queries_cached' => $this->page_info['db_cached'],);
		$update_sql = '';
		$update_sep = '';
		while (list($field, $value) = each($update)) {
			$update_sql .= $update_sep . $field . '=' . $value . ' ';
			$update_sep = ', ';
		}
		$query = 'UPDATE ' . $this->tables['benchmark'] . ' SET ' . $update_sql . ' WHERE hit_id=' . $this->_benchmark_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		}
		return true;
	}

	function display_page_info($expand = false) {
		if ($expand) {
			$className = 'pm_show';
		} else {
			$className = 'pm_hide';
		}
		$page_info = $this->_get_array_toc($this->page_info);
		$content = '';
		$content .= '<a name="pm_page_info" ></a><div align="center" style="float:none;clear: both;"><div class="debug_info" align="left"><a href="javascript:void(0);" onclick="javascript:pmSwitchObj(\'pm_page_info\');" style="color:#000000;"><b>Page Info</b></a>';
		$content .= '<div id="pm_page_info" class="' . $className . '">' . $page_info['toc'] . '<hr><pre>';
		$content .= (print_r($page_info['array'], true));
		$content .= '</pre></div></div></div>';
		return $content;
	}

	function _get_array_toc($array, $levels = 2) {
		$toc = array();;
		$content = array();
		//$content[''] = array();
		foreach ($array as $key => $value) {
			if (!is_array($value)) {
				$content[$key] = $value;
			} else {
				$toc[] = '<li><a href="#' . $key . '">' . $key . '</a></li>';
				if ($levels > 1) {
					$child = $this->_get_array_toc($value, $levels - 1);
					$toc[] = $child['toc'];
					$content['<a name="' . $key . '">' . $key . '</a>'] = $child['array'];
				} else {
					htmlspecialchars_array($value);
					$content['<a name="' . $key . '">' . $key . '</a>'] = $value;
				}

			}
		}
		if (count($toc) > 0) {
			$full_toc = '<ul>' . implode('', $toc) . '</ul>';
		} else {
			$full_toc = '';
		}
		return array('toc' => $full_toc, 'array' => $content);
	}

	function display_query_log($expand = false) {
		global $ADODB_QUERY_LOG;
		if ($expand) {
			$className = 'pm_show';
		} else {
			$className = 'pm_hide';
		}
		$content = '';
		$content .= '<a name="pm_queries" ></a><div align="center" style="float:none;clear: both;"><div class="debug_info" align="left"><a href="javascript:void(0);" onclick="javascript:pmSwitchObj(\'pm_queries\');" style="color:#000000;"><b>Queries</b></a>';
		$content .= '<div id="pm_queries" class="' . $className . '"><pre>';
		$content .= htmlspecialchars(print_r($ADODB_QUERY_LOG, true));
		$content .= '</pre></div></div></div>';
		return $content;
	}

	function display_debug_panel($page_info_visible, $queries_visible) {
		if ($page_info_visible) {
			$page_info_href = '#pm_page_info';
		} else {
			$page_info_href = $this->page_info['request_url'];
			if (strpos($page_info_href, '?') === false) {
				$page_info_href .= '?';
			}
			$page_info_href .= '&pm_page_info';
		}
		if ($queries_visible) {
			$queries_href = '#pm_queries';
		} else {
			$queries_href = $this->page_info['request_url'];
			if (strpos($queries_href, '?') === false) {
				$queries_href .= '?';
			}
			$queries_href .= '&pm_queries';
		}
		$output = '<div id="pm_debug_panel" style="">';
		$output .= '<div style="float:right;"><sup><a style="font-size:9px;text-decoration:none;" href="javascript:void(0);" onclick="javascript:pmHideDebugPanel();">[X]</a></sup></div>';

		$output .= '<b>phpcms</b><br>';
		$output .= '<a href="' . $page_info_href . '" onclick="javascript:pmSwitchObj(\'pm_page_info\', \'pm_show\');">page info</a><br>';
		$output .= '<a href="javascript:void(0);" onclick="javascript:pmSwitchObj(\'pm_timers_bar\');">timers: ' . $this->page_info['execution_time'] . '</a><br>';
		$output .= '<a href="' . $queries_href . '" onclick="javascript:pmSwitchObj(\'pm_queries\', \'pm_show\');">queries: ' . $this->page_info['db_execs'] . '/' . $this->page_info['db_cached'] . '</a>';

		$output .= '<div style="float:none;clear:both;"></div>';
		$output .= '</div>';
		return $output;
	}

	function setUserUrl() {
		$_SESSION["access_denied"] = 0;
		if (!isset($this->user_data["uid"]))
			return;
		$sql = "DELETE FROM " . $this->tables["users_url"] . " WHERE dates<DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$this->dbc->Execute($sql);
		$sql = "INSERT INTO " . $this->tables["users_url"] . " SET dates=NOW(), uid='" . (int)$this->user_data["uid"] . "', url='" . addslashes($this->page_info['request_url']) . "'";
		$this->dbc->Execute($sql);
	}

	function getInfoGroup($groupId) {
		$sql = "SELECT * FROM " . $this->tables["users"] . " WHERE uid='" . $groupId . "' AND ugroup=1";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $sql, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() != 1) {
			return false;
		}
		$row = $result->FetchRow();
		return $row;
	}

//FORUM!

	function getSIdByAlias($alias, $language = 0) {
		if (!$language) {
			$path[] = $this->get_root_item();
			$language = $path[0]["language_id"];
		}
		$sql = "SELECT s_id FROM " . $this->tables["structure"] . " WHERE alias='" . addslashes($alias) . "' AND language_id='" . $language . "' AND enabled=1";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $sql, E_USER_ERROR);
			return false;
		} elseif ($result->RecordCount() == 0) {
			return false;
		}
		$row = $result->FetchRow();
		return $row["s_id"];
	}

	function clear_forum_sessions() {
		$query = 'DELETE FROM ' . $this->tables['forum_sessions'] . ' WHERE last_refresh>0 and last_refresh<' . (time() - 1800) . '';
		$result = $this->dbc->query($query);
		if (!$result) {
			trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
			return false;
		}
		return true;
	}

	function _is_forum_granted($forum_id) {
		if ($this->page_info['user_data']['uid'] == 0) {
			return false;
		} elseif (empty($this->page_info['user_data']['granted_forums'][$forum_id])) {
			return false;
		}
		return true;
	}

	function get_online_users() {
		//die($this->page_info['user_data']['uid']);
		if ($this->page_info['user_data']['uid'] > 0) {
			$key = '';
		} else {
			$this->user_data['ip'] = $this->_GetIP();
			$key = md5($this->user_data['ip']['ip'] . '-' . $_SERVER['HTTP_USER_AGENT']);
		}

		$this->update_forum_sessions($this->page_info['user_data']['uid'], $key);
		$users = array();
		$query = 'SELECT u.* FROM ' . $this->tables['forum_sessions'] . ' s, ' . $this->tables['users'] . ' u WHERE s.uid>0 and s.uid=u.uid and (s.last_refresh=0 or s.last_refresh>' . (time() - 1200) . ') ORDER BY s.last_refresh';
		//echo $query;
		$result = $this->dbc->query($query);
		if (!$result) {
			trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
			return false;
		} else if ($result->RecordCount() > 0) {
			while ($user = $result->FetchRow()) {
				if ($user['show_realname'] == 1) {
					$user['name'] = $user['name'];
				} else {
					$user['name'] = $user['login'];
				}
				$user['profile_url'] = '/users/' . $user['uid'] . '/';
				$users[] = $user;
			}
		}

		$query = 'SELECT count(uid) as total FROM ' . $this->tables['forum_sessions'] . ' WHERE uid=0 and (last_refresh=0 or last_refresh>' . (time() - 1200) . ')';
		$result = $this->dbc->query($query);
		if (!$result) {
			trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
			return false;
		}
		$guests = $result->FetchRow();

		return array('users' => $users, 'users_total' => count($users), 'guests' => $guests['total'], 'total' => count($users) + $guests['total']);
	}

	function _getIP() {
		// Get some headers that may contain the IP address
		$SimpleIP = $_SERVER['REMOTE_ADDR'];
		$TrueIP = '';

		if (empty($TrueIP) && (!empty($_SERVER['HTTP_FORWARDED_FOR']))) {
			$TrueIP = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		if (empty($TrueIP) && (!empty($_SERVER['HTTP_X_FORWARDED']))) {
			$TrueIP = $_SERVER['HTTP_X_FORWARDED'];
		}
		if (empty($TrueIP) && (!empty($_SERVER['']))) {
			$TrueIP = $_SERVER['HTTP_FORWARDED'];
		}

		$GetProxy = ($TrueIP == '' ? 0 : 1);

		if ($GetProxy == 0) {
			if (!empty($_SERVER['HTTP_VIA'])) {
				$TrueIP = $_SERVER['HTTP_VIA'];
			}
			if (empty($TrueIP) && (!empty($_SERVER['HTTP_X_COMING_FROM']))) {
				$TrueIP = $_SERVER['HTTP_X_COMING_FROM'];
			}
			if (empty($TrueIP) && (!empty($_SERVER['HTTP_COMING_FROM']))) {
				$TrueIP = $_SERVER['HTTP_COMING_FROM'];
			}
			if (!empty($TrueIP)) {
				$GetProxy = 2;
			}
		};

		if ($TrueIP == $SimpleIP)
			$GetProxy = 0;

		// Return the true IP if found, else the proxy IP with a 'p' at the begining
		switch ($GetProxy) {
			case '0':
				// True IP without proxy
				$IP = $SimpleIP;
				$proxy = '';
				break;
			case '1':
				$b = ereg("^([0-9]{1,3}\.){3,3}[0-9]{1,3}", $TrueIP, $IP_array);
				if ($b && (count($IP_array) > 0)) {
					// True IP behind a proxy
					$IP = $IP_array[0];
					$proxy = '';
				} else {
					// Proxy IP
					$IP = "" . $SimpleIP;
					$proxy = 'p';
				};
				break;
			case '2':
				// Proxy IP
				$IP = "" . $SimpleIP;
				$proxy = 'p';
		};

		if (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})/i", $IP, $matches)) {
			$IP = '';
		}
		$result = array('ip' => $IP, 'proxy' => $proxy);
		return $result;
	}

	function update_forum_sessions($uid, $user_key) {
		$query = 'SELECT * FROM ' . $this->tables['forum_sessions'] . ' WHERE uid=' . $uid . ' and user_key="' . addslashes($user_key) . '"';
		//echo $query;
		$result = $this->dbc->query($query);
		if (!$result) {
			trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
			return false;
		} else if ($result->RecordCount() == 0) {
			$query = 'INSERT INTO ' . $this->tables['forum_sessions'] . '(uid, last_refresh, user_key) VALUES(' . $uid . ', ' . time() . ', "' . addslashes($user_key) . '")';
			//die($query);
			$result = $this->dbc->query($query);
			if (!$result) {
				trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
				return false;
			}
		} else {
			$query = 'UPDATE ' . $this->tables['forum_sessions'] . ' SET  last_refresh=' . time() . ' WHERE uid=' . $uid . '';
			//die($query);
			$result = $this->dbc->query($query);
			if (!$result) {
				trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
				return false;
			}
		}
		return true;
	}

	/* rg@0606 */

	function _get_userinfo($uid) {
		$user_data_query = "SELECT * FROM " . $this->tables['users'] . " WHERE uid=" . $uid;
		//die($user_data_query);
		$user_data_result = $this->dbc->query($user_data_query);
		if (!$user_data_result) {
			trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
		} else if ($user_data_result->RecordCount() <> 1) {
			$user_data = array('uid' => 0);
		} else {
			$user_data = $user_data_result->FetchRow();
			unset($user_data['password']);

			$user_data['reg_date_short'] = Date('d.m.Y', strtotime($user_data['reg_date']));
			$user_data['reg_date'] = Date('d.m.Y H:i', strtotime($user_data['reg_date']));
			if ($user_data['show_realname'] == 1) {
				$user_data['name'] = $user_data['name'];
			} else {
				$user_data['name'] = $user_data['login'];
			}
			$user_data['profile_url'] = '';
			$user_data['profile_name'] = $user_data['name'];
			if ($user_data['show_email'] == 1) {
				//$user_data['email'] = $user_data['email'];
				//$user_data['profile_url']  = 'mailto:'.$user_data['email'];
				//$user_data['profile_name'] = $user_data['email'];
			} else {
				$user_data['email'] = '';
			}

			$query = 'SELECT count(post_id) as total FROM ' . $this->tables['forum_posts'] . ' WHERE poster_id=' . $uid . '';
			$result = $this->dbc->query($query);
			if (!$result) {
				trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
				return false;
			}
			$row = $result->FetchRow();
			$user_data['total_posts'] = $row['total'];

			$user_data['profile_url'] = '/users/' . $user_data['uid'] . '/';

			if (!empty($user_data['special_rank']) || !empty($user_data['special_rank_url'])) {
				$user_data['rank_url'] = $user_data['special_rank_url'];
				$user_data['rank'] = $user_data['special_rank'];
			} else {
				$query = 'SELECT * FROM ' . $this->tables['forum_ranks'] . ' WHERE posts<' . $user_data['total_posts'] . ' ORDER BY posts DESC limit 1';
				$result = $this->dbc->query($query);
				if (!$result) {
					trigger_error($this->dbc->err_message . '<br>Query: ' . $this->dbc->query_log[$this->dbc->query_counter - 1], E_USER_ERROR);
					return false;
				} else if ($user_data_result->RecordCount() == 0) {
					$user_data['rank_url'] = '';
					$user_data['rank'] = '';
				} else {
					$row = $result->FetchRow();
					$user_data['rank_url'] = $row['img_url'];
					$user_data['rank'] = $row['title'];
				}
			}
			//$user_data;
		}
		return $user_data;
	}

	/* /rg@0606 */

	function setUserInfo() {
		$userInfo = $this->page_info["user_data"];
		$this->userInfo["site_access"] = array();
		if (isset($userInfo["uid"]) && $userInfo["uid"] > 0) {
			$groups = explode("|", substr($userInfo["member_of_groups"], 1, -1));
			if (count($groups) > 0) {
				foreach ($groups as $group) {
					$this->userInfo["groups"][$group] = $this->getInfoGroup($group);
					$siteAccessId = explode("|", substr($this->userInfo["groups"][$group]["site_access"], 1, -1));
					foreach ($siteAccessId as $sId) {
						$this->userInfo["site_access"][$sId] = $sId;
					}
				}
			}
		} else {
		}
	}

	function _get_structure_access($uid) {
		$structure = array();
		$query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid=' . $uid . ' and type="structure" and value=1 ORDER BY object';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
			return false;
		}
		while ($row = $result->FetchRow()) {
			$structure[$row['object']] = $row['value'];
		}
		return $structure;
	}


} /* cms_cms class end */


function htmlspecialchars_array(&$arr) {
	foreach ($arr as $key => $val) {
		if (is_array($val)) {
			htmlspecialchars_array($arr[$key]);
		} else {
			$arr[$key] = htmlspecialchars($val, ENT_QUOTES);
		}
	}
}


?>
