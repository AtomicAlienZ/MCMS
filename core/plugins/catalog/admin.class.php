<?php

class plugin_admin_interface extends cms_plugin_admin {

	var $plugin = array();
	var $config = array();
	var $items_per_page = 10;
	var $dbc;
	var $fob;
	var $cms = '';

	// Список поддерживаемых языков
	var $langs = array();

	// Текущий язык сайта
	var $lang = "ru";

	// Опции модуля
	var $options = array();

	// Список доступных табов
	var $tabs = array(
		"index"   => "Каталог"
	);

    //............................................................ Основная функция запуска плагина

	// Функция инициализации плагина
	function main($command, $arguments) {

		// Подключаем класс для работы с формами
		require_once(cms_LIB_PATH.'form_builder.class.php');

		// Инициализируем таблицы
		$this->dbc = $this->cms->dbc;
		$this->init_tables();

		// Инициализируем опции модуля
		$this->init_options();

		// Инициализируем список поддерживаемых языков
		$this->init_languages();

		// Если запрашиваемая команда отсутствует, то показываем первый таб
		if (!method_exists($this, $command)) {
			reset($this->tabs);
			$command = key($this->tabs);
		}

		// Выполняем запрошенную команду
        $result = $this->$command($arguments);

		// Печатаем табы в административном интерфейсе
	    $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');

        return $result;
	}

    //............................................................................ Параметры блока

	// Функция установки параметров блока
	function index($arguments) {

		$output = "";

		// Читаем идентификатор раздела и идентификатор блока в структуре сайта
		$s_id    = (int) $arguments['s_id'];
		$toc_id  = (int) $arguments['toc_id'];

		// Печатаем путь к странице
		$edit_url = $this->cms->format_url($this->plugin["name"], "index", $arguments );
		$path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
		$this->cms->int_set_hierarchy($s_id, $path);

		// Читаем идентификатор элемента каталога для выбранного блока
		$record = $this->get_structure_record($s_id, $toc_id);

		// Читаем список элементов каталога верхнего уровня
		$catalogs = $this->get_catalogs();

		if (!empty($catalogs)) {

			// Создаем форму для выбора каталога
			$fob = $this->init_fob('fob', $this->cms->admin_url);
			$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
			$fob->add_hidden('$'.$this->cms->request_vars['command'], 'index');
			$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
			$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
			$fob->add_select(true, 'record_id', $catalogs, "Каталог для показа", '', $record["record_id"], '');
			$fob->add_checkbox(false, 'main_flag', 1, '', 'Это основное расположение каталога', '', (bool) $record["main_flag"], 'fob-check', '');
			$fob->add_button('submit', 'submit', 'Сохранить', '');
			$output .= $fob->display('return');

			// Сохраняем в базе данных идентификатор элемента каталога для показа в блоке
			if ($fob->is_submited()  && $fob->is_valid_submit()) {

				// Читаем данные из формы
				$values = $fob->get_submited_values();  

				// Формируем SQL-запрос на обновление
				$update = array(
					'record_id' => (int) $values['record_id'],
					'main_flag' => (0 + !empty($values['main_flag']))
				);

				// Выполняем SQL-запрос
				$update_sql = array();
				foreach ($update as $key => $val) {
					$update_sql[]= $key.' = '.$val;
				}
				$query = 'UPDATE '.$this->cms->tables['catalog_structure'].' SET '.join(', ', $update_sql).' WHERE s_id='.$s_id.' AND toc_id='.$toc_id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}

				// Делаем редирект на страницу с добавленным блоком
				$target_url = $this->cms->format_url('sim', 'man', array('s_id' => $s_id));
				header('Location: '.$target_url);

			}

		} else {

			// Печатаем сообщение об отсутствии записей
			$output .= '<p>Список каталогов не определен</p>';
		}

		return $output;
	}

	//............................................................................... Общие функции

	// Функция чтения списка каталогов верхнего уровня
	function get_catalogs() {

		$catalogs = array(0=>"выбрать...");

		// Читаем список каталогов верхнего уровня
		$query = 'SELECT * FROM '.$this->cms->tables['catalog'].' WHERE parent_id=0 AND enabled=1 ORDER BY ord DESC, title_'.$this->lang;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		if ($result->RecordCount() > 0) {
			while ($record = $result->FetchRow()) {
				$catalogs[$record["id"]] = $record["title_".$this->lang];
			}
		}

		return $catalogs;
	}

	// Функция чтения информации о структуре текущего блока
	function get_structure_record($s_id, $toc_id) {

		// Проверяем, определен ли каталог для данного блока
		$query = 'SELECT * FROM '.$this->cms->tables['catalog_structure'].' WHERE s_id='.$s_id.' AND toc_id='.$toc_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}

		if ($result->RecordCount() > 0 ) {

			// Читаем информацию о структуре элемента каталога
			$record = $result->FetchRow();

		} else {

			// Если каталог для данного блока не был определен, то создаем новую запись в базе данных
	        $record = array(
	        	's_id'		=> $s_id, 
	        	'toc_id'	=> $toc_id, 
	        	'record_id' => 0,
				"main_flag" => 0
	        );

			// Выполняем SQL-запрос
	        $query = 'INSERT INTO '.$this->cms->tables['catalog_structure'].' ('.join(', ', array_keys($record)).') VALUES ('.join(', ',$record).')';
	        $result = $this->dbc->Execute($query);
	        if (!$result) {
	            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
	            return false;
	        }
		}

		return $record;
	}

	// Функция печати списка табов
	function print_tabs($active_tab='index', $arguments) {

		// Формируем список табов
		$tabs = array();
		foreach($this->tabs as $tab_id => $title) {
			$tabs[$tab_id] = "<a href=\"".$this->cms->format_url($this->plugin["name"], $tab_id, $arguments)."\">".$title."</a>";
		}

		// Проверяем  активный таб и сохраняем его имя в cookies
		if (strstr($active_tab, "_")) {
			$active_tab = substr($active_tab, 0, strpos($active_tab, "_"));
		}
		if (!isset($tabs[$active_tab])) {
			reset($tabs);
			$active_tab = key($tabs);
		}
		setcookie('pm_simtab', $active_tab, time()+3600*24, '');

		// Выводим табы на печать
		$output = $this->cms->int_add_tabs($tabs, $active_tab);

		return $output;
	}

	// Функция инициализации поддерживаемых на сайте языков
	function init_languages() {

		$languages = array();
		$lang = "";

		// Читаем список поддерживаемых на сайте языков
		$sql = "SELECT language_id, short_title, alias FROM ".$this->cms->tables["languages"]." ORDER BY ord ASC";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return false;
		}

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
		$this->langs = $languages;
		$this->lang = $lang;
	}

	// Функция инициализации опций модуля
	function init_options() {

		$options = array();

		// Читаем список опций из базы данных
		$query = "SELECT * FROM ".$this->cms->tables["catalog_options"]."";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg()."<br>Query: ".$query, E_USER_ERROR);
			return false;
		}
		if ($result->RecordCount() > 0) {
			while ($record = $result->FetchRow()) {
				$options[$record["name"]] = $record["value"];
			}
		}

		// Сохраняем прочитанные опции в переменной класса
		$this->options = $options;
	}

	// Функция инициализации таблиц для работы плагина
	function init_tables() {
		$pathToTable = cms_PLUGINS_PATH.$this->plugin['name'].'_manage/tables.php';
		if(file_exists($pathToTable)) {
			require_once($pathToTable);
			$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			$this->cms->install->dbc = &$this->dbc;
			$this->cms->install->tables = $this->tables;
			$this->cms->install->tablesColumn = $this->tableColumn;
			$this->cms->install->runInstall();
		}
	}

	//.............................................................................................
}

?>