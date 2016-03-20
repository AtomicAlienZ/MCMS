<?php

class plugin_admin_interface  extends cms_plugin_admin {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $fob;
	var $cms_ai = '';
	
	function index($arguments) {
		$output = '';

		$add_link = '<p><input type="button" class="button" value="Добавить слово" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'addWord', $arguments)."'".'"></p>';
		$output .= $add_link;

		$sql = "SELECT * FROM ".$this->cms->tables["words"]." ORDER BY BINARY(word_ru) ASC";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			$output .= '<div class="admin-table2"><div class="width-fix">';
			$output .= '
					<table>
						<tr>
							<td class="title-h">Слово (рус)</td>
<!--							<td class="act-h">Слово (укр)</td>
							<td class="act-h">Слово (eng)</td>-->
							<td class="act-h">&nbsp;</td>
							<td class="act-h">&nbsp;</td>
							<td class="act-h last" colspan="2">Действия</td>
						</tr>';
			while($item = $result->FetchRow()) {
				$arguments = array('id'=>$item['word_id']);
				if ($item['enabled']) {
					$switch = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switchState', $arguments).'" class="on">вкл</a>';
				}
				else {
					$switch  = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switchState', $arguments).'" class="off">выкл</a>';
				}
				$actions = array(
					'edit' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'editWord', $arguments).'">редактировать</a>',
					'delete' => '<a onclick="return confirm(\''.$this->translate('Удалить').'?\')" href="'.$this->cms->format_url($this->plugin['name'], 'deleteWord', $arguments).'">удалить</a>'
				);
				$output .= '
						<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
							<td class="title"><a href="'.$this->cms->format_url($this->plugin['name'], 'editWord', $arguments).'">'.$item['word_ru'].'</a></td>
<!--							<td class="act"><a href="'.$this->cms->format_url($this->plugin['name'], 'editWord', $arguments).'">'.$item['word_ua'].'</a></td>
							<td class="act"><a href="'.$this->cms->format_url($this->plugin['name'], 'editWord', $arguments).'">'.$item['word_en'].'</a></td>-->
							<td class="act">&nbsp;</td>
							<td class="act">'.$switch.'</td>
							<td class="act">'.$actions['edit'].'</td>
							<td class="act last">'.$actions['delete'].'</td>
						</tr>';
			}
			$output .= '</table>';
			$output .= '</div></div>';
		}
		else {
			$output .= $this->cms->int_add_h3('Слов не найдено');
		}
		$output .= $add_link;
		return $output;
	}
	function addWord($arguments) {
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'addWord');
		$fob->add_text(true, 'word_ru', '', 'Название', 'Рус', 'fob-text', '', 'text', '', '');
//		$fob->add_text(true, 'word_ua', '', 'Название', 'Укр', 'fob-text', '', 'text', '', '');
//		$fob->add_text(true, 'word_en', '', 'Название', 'Eng', 'fob-text', '', 'text', '', '');
		$fob->add_checkbox(false, 'enabled', 1, 'Активен', '', '', 1, 'fob-check', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'word_ru' => "'".addslashes(trim($values['word_ru']))."'",
//				'word_ua' => "'".addslashes(trim($values['word_ua']))."'",
//				'word_en' => "'".addslashes(trim($values['word_en']))."'",
				'enabled' => (int)$values['enabled'],
			);
			$query = 'INSERT INTO '.$this->cms->tables['words'].' ('.join(',', array_keys($item)).') VALUES('.join(',', $item).')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index',$arguments);
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function editWord($arguments) {
		$output = '';
		$id = (int) $arguments['id'];
		$sql = "SELECT * FROM ".$this->cms->tables["words"]." WHERE word_id='".$id."'";
		$result = $this->dbc->Execute($sql);
		
		$item = $result->FetchRow();
		
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'editWord');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_text(true, 'word_ru', $item['word_ru'], 'Название', 'Рус', 'fob-text', '', 'text', '', '');
//		$fob->add_text(true, 'word_ua', $item['word_ua'], 'Название', 'Укр', 'fob-text', '', 'text', '', '');
//		$fob->add_text(true, 'word_en', $item['word_en'], 'Название', 'Eng', 'fob-text', '', 'text', '', '');
		$fob->add_checkbox(false, 'enabled', 1, 'Активен', '', '', $item['enabled'], 'fob-check', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'word_ru' => "'".addslashes(trim($values['word_ru']))."'",
//				'word_ua' => "'".addslashes(trim($values['word_ua']))."'",
//				'word_en' => "'".addslashes(trim($values['word_en']))."'",
				'enabled' => (int)$values['enabled'],
			);
			
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($item)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$sql = "UPDATE ".$this->cms->tables["words"]." SET ".$update_sql." WHERE word_id='".$id."'";
			$result = $this->dbc->Execute($sql);
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function switchState($arguments) {
		$id = (int) $arguments['id'];
		$sql = "UPDATE ".$this->cms->tables["words"]." SET enabled=abs(enabled-1) WHERE word_id='".$id."'";
		$result = $this->dbc->Execute($sql);
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', $arg);
		header('Location: '.$target_url);
		return $this->index(array());
	}
	function deleteWord($arguments) {
		$id = (int) $arguments['id'];
		$query = "DELETE FROM ".$this->cms->tables["words"]." WHERE word_id='".$id."'";
		$result = $this->dbc->Execute($query);
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', $arg);
		header('Location: '.$target_url);
	}



	function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		$this->dbc = $this->cms->dbc;
		$this->initTables();
	}
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.$this->plugin['name'].'/tables.php';
		if(file_exists($pathToTable)) {
			require($pathToTable);
			$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			$this->cms->install->dbc = &$this->dbc;
			$this->cms->install->tables = $this->tables;
			$this->cms->install->tablesColumn = $this->tableColumn;
			$this->cms->install->runInstall();
		}
	}
	function getActiveTabs(&$tabs, $activeTab) {
		if(array_key_exists($activeTab, $tabs)) return $activeTab;
		switch($activeTab) {
			default: 
				$ret = "index";
				break;
		}
		return $ret;
	}
	
	function print_tabs($active_tab, $arguments = array()) {
		$tabs = array(
			'index' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', $arguments).'">Облака тегов</a>',
		);
		$active_tab = $this->getActiveTabs($tabs, $active_tab);
		setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');
		$output = $this->cms->int_add_tabs($tabs, $active_tab);
		return $output;
	}
	function main($command, $arguments) {
		$this->init();
		if (method_exists($this, $command)) {
			$result = $this->$command($arguments);
			$result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
			return $result;
		}
		switch($command) {
			default:
			case 'index':
				$result = $this->index($arguments);
				break;
		}
		$result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
		return $result;
	}
	function getDirectionList ($selected, $id) {    	
    	$sql = "SELECT * FROM ".$this->cms->tables["catalog"]." WHERE parent = 631 ORDER BY BINARY(title) ASC";
    	//echo $sql;     	
    	
    	$selected = array();    	
    	$result = $this->dbc->Execute($sql);    	
    	if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql);
			return false;
		}
		if ($result->RecordCount()>0) {
			while($item = $result->FetchRow()) {
				$clList[$item['id']] = $item['title'];
				if ($item['consultation_id']==$id ) $selected[$item['id']] = $item['title'];
			}
		}
    	
    	return $clList;    	
    }

	function directives($arguments) {
		$output = '';
		
		$sql = "SELECT * FROM ".$this->cms->tables["directives"]." ORDER BY BINARY(title) ASC";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			$output .= '<div class="admin-table2"><div class="width-fix">';
			$output .= '
					<table>
						<tr>
							<td class="title-h">Название</td>
							<td class="act-h last" colspan="2">Действия</td>
						</tr>';
			while($item = $result->FetchRow()) {
				$arguments = array('id'=>$item['directive_id']);
				$actions = array(
						'edit' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'editDirective', $arguments).'">редактировать</a>',
						'delete' => '<a onclick="return confirm(\''.$this->translate('Удалить').'?\')" href="'.$this->cms->format_url($this->plugin['name'], 'deleteDirective', $arguments).'">удалить</a>'
						);
				$output .= '
						<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
							<td class="title"><a href="'.$this->cms->format_url($this->plugin['name'], 'editDirective', $arguments).'">'.$item['title'].'</a></td>
							<td class="act">'.$actions['edit'].'</td>
							<td class="act last">'.$actions['delete'].'</td>
						</tr>';
			}
			$output .= '</table>';
			$output .= '</div></div>';
		}
		else {
			$output .= $this->cms->int_add_h3('Направления отсутствуют');
		}
		$add_link = '<p><input type="button" class="button" value="Добавить направление" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'addDirective', $arguments)."'".'"></p>';
		$output .= $add_link;
		return $output;
	}
	function addDirective($arguments) {
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'addDirective');
		$fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'title' => "'".addslashes(trim($values['title']))."'",
			);
			$query = 'INSERT INTO '.$this->cms->tables['directives'].' ('.join(',', array_keys($item)).') VALUES('.join(',', $item).')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'directives',$arguments);
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function editDirective($arguments) {
		$output = '';
		$id = (int) $arguments['id'];
		$sql = "SELECT * FROM ".$this->cms->tables["directives"]." WHERE directive_id='".$id."'";
		$result = $this->dbc->Execute($sql);
		
		$item = $result->FetchRow();
		
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'editDirective');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'title' => "'".addslashes(trim($values['title']))."'",
			);
			
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($item)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$sql = "UPDATE ".$this->cms->tables["directives"]." SET ".$update_sql." WHERE directive_id='".$id."'";
			$result = $this->dbc->Execute($sql);
			$target_url = $this->cms->format_url($this->plugin['name'], 'directives', $arguments);
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function deleteDirective($arguments) {
		$id = (int) $arguments['id'];
		$query = "DELETE FROM ".$this->cms->tables["directives"]." WHERE directive_id='".$id."'";
		$result = $this->dbc->Execute($query);
		$target_url = $this->cms->format_url($this->plugin['name'], 'directives', $arg);
		header('Location: '.$target_url);
	}
	function getAllDirectives() {
		$ret = array(0=>"...");
		$sql = "SELECT * FROM ".$this->cms->tables["directives"]." ORDER BY BINARY(title)";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			while($item = $result->FetchRow()) {
				$ret[$item["directive_id"]] = $item["title"];
			}
		}
		return $ret;
	}

}

?>