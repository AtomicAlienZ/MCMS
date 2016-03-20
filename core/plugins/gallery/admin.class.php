<?php

class plugin_admin_interface extends cms_plugin_admin {
	var $plugin		 = array();
	var $config		 = array();
	var $dbc;
	var $fob;
	var $cms		 = '';

	var $langs = array();
	var $defaultLang = "ru";

	function index($arguments) {
		$output = '';
		$s_id		= (int) $arguments['s_id'];
		$toc_id	  = (int) $arguments['toc_id'];
		$where_cond = array();
		$where_cond[] = 's_id='.$s_id;
		$where_cond[] = 'toc_id='.$toc_id;

		$this->langs = $this->cms->getAllLanguage();
		$this->defaultLang = $this->cms->defaultLang;

		$sql = "SELECT * FROM ".$this->cms->tables["gallery_structure"]." WHERE ".join(" AND ",$where_cond);
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount()==0) {
			$item = $this->insert($arguments);
		}
		else $item = $result->FetchRow();

		$edit_url = $this->cms->format_url($this->plugin["name"], "index", $arguments );
		$path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
		$this->cms->int_set_hierarchy($s_id, $path);

		$id = $item['id'];

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'index');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
		$rubrics = $this->getRubrics();
		$fob->add_select(true, 'record_id', $rubrics, 'Раздел', '', $item['record_id'], '');
		$fob->add_checkbox(false, 'open_this', 1, 'Открывать на данной странице', '', '', $item["open_this"], 'fob-check', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'record_id'	=> (int) $values['record_id'],
				'open_this'	=> (0 + !empty($values['open_this'])),
			);

			$update_sql =array();
			foreach ($item as $key=>$val) {
				$update_sql[]= $key.'='.$val;
			}
			$query = 'UPDATE '.$this->cms->tables['gallery_structure'].' SET '.join(', ',$update_sql).' WHERE '.join(' AND ',$where_cond);
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			$target_url = $this->cms->format_url('sim', 'man', array('s_id'=>$s_id));
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function getRubrics($id=0) {
		$ret = array(0=>"выбрать...");
		$query = "SELECT * FROM ".$this->cms->tables['gallery']." WHERE parent_id=0 ORDER BY BINARY(title_".$this->defaultLang.") ASC";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return $ret;
		}
		elseif ($result->RecordCount()==0) {
			return $ret;
		}
		while($item = $result->FetchRow()) {
			$ret[$item['gallery_id']]=$item["title_".$this->defaultLang];
		}
		return $ret;
	}


	function insert($arguments) {
		$output = '';
		$s_id		= (int) $arguments['s_id'];
		$toc_id	  = (int) $arguments['toc_id'];
		$where_cond = array();
		$items=array(
			's_id' => $s_id,
			'toc_id' => $toc_id,
		);

		$query = 'INSERT INTO '.$this->cms->tables['gallery_structure'].'  ('.join(',',array_keys($items)).') VALUES ('.join(',',$items).')';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$items['id'] = $this->dbc->insert_id();
		return $items;
	}

	function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		require_once(cms_LIB_PATH.'images.lib.php');
		$this->dbc = &$this->cms->dbc;
		$this->initTables();
	}
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.'gallery_manage/tables.php';
		if(file_exists($pathToTable)) {
			require_once($pathToTable);
			$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			$this->cms->install->dbc = &$this->dbc;
			$this->cms->install->tables = $this->tables;
			$this->cms->install->tablesColumn = $this->tableColumn;
			$this->cms->install->runInstall();
		}
	}
	function main($command, $arguments) {
		$this->init();
		$this->cms->int_set_h1($this->plugin['title']);
		if (method_exists($this, $command)) {
			$result = $this->$command($arguments);
			$result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
			return $result;
		}
		switch($command) {
			default:
				$result = $this->index($arguments);
				break;
		}
		$result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
		return $result;
	}

	function print_tabs($active_tab, $arguments = array()) {
		$tabs = array(
			'index' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', $arguments).'">'.$this->plugin['title'].'</a>',
		);
		$active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
		setcookie('pm_simtab', $active_tab, time()+3600*24, '');
		$output = $this->cms->int_add_tabs($tabs, $active_tab);
		return $output;
	}


}