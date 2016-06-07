<?php

require_once(cms_LIB_PATH.'cms_structure.class.php');

class plugin_admin_interface  extends cms_plugin_admin{
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms = '';
    var $templates = array();
	var $lang;
	var $tabs = array();


    function index($arguments=array()) {
		$this->cms->intSetHierarchy();
        $output = '';
        $output .= '
            <div class="admin-table2"><div class="width-fix"><table>
            <tr>
              <td class="title-h">Заголовок станицы</td>
              <td class="act-h">Короткое&nbsp;имя</td>
              <td class="act-h">Алиас</td>
              <td class="act-h last" colspan="3">Действия</td>
            </tr>
        ';
        $items = $this->lang->getAllLanguages();
        $countResults = count($items);
        $counter = 0;
        foreach($items as $item) {
			$arg = array("language"=>$item["language_id"]);
            $output .= '<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">';
            $output .= '<td class="title"><a href="'.$this->cms->format_url($this->plugin['name'], 'formItem', $arg).'">'.$item["title"].'</a></td>';
            $output .= '<td class="act">'.$item["short_title"].'</td>';
            $output .= '<td class="act">'.$item["alias"].'</td>';

			$switch = $item['enabled'] ? '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arg).'" class="on">вкл</a>' : '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arg).'" class="off">выкл</a>';
			$output .= '<td class="act">'.$switch.'</td>';

			$output .= '<td class="act"><a href="'.$this->cms->format_url($this->plugin['name'], 'formItem', array("language"=>$item["language_id"])).'">редактировать</a></td>';
            $output .= '<td class="act last"><a onclick="return confirm(\'Вы действительно желаете удалить язык?\')" href="'.$this->cms->format_url($this->plugin['name'], 'itemDelete', $arg).'">удалить</a></td>';
            $output .= '</tr>';
            $counter++;
        }
        $output .= '</table></div></div>';

		$addLink = '<input type="button" class="button" value="Добавить язык" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'formItem', array())."'".'">';
		$output .= $addLink;

        return $output;
    }

	function formItem($arguments) {
		$output = '';

		if(isset($arguments["language"]) && (int)$arguments["language"]>0) $langId = (int)$arguments["language"];
		if(isset($_POST["fob"]["language"]) && (int)$_POST["fob"]["language"]>0) $langId = (int)$_POST["fob"]["language"];

		if($langId) {
//			$head = 'Изменение информации о языке';
//			$addTabs["name"] = "editForm";
//			$this->tabs["now"] = $addTabs["name"];
//			$addTabs["title"] = 'Изменение языка</a>';
//			$this->tabs[] = $addTabs;

			$sql = "SELECT l.*, s.template_id FROM ".$this->cms->tables['languages']." l LEFT JOIN ".$this->cms->tables['structure']." s ON s.language_id=l.language_id WHERE l.language_id='".$langId."'";
			$results = $this->dbc->Execute($sql);
			$item = $results->FetchRow();

		
			$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Изменение информации о языке сайта "'.$item["title"].'"</a>';
		}
		else {
			$head = 'Добавление языка';
			$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Добавление нового языка сайта</a>';
		}
		$this->cms->intSetHierarchy();

		$fob = $this->init_fob('fob', $this->cms->cms_url);

		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'formItem');
		if($arguments["language"] || $_POST["fob"]["language"]) $fob->add_hidden('language', $arguments["language"]);
        $fob->add_text(true, 'title', $item["title"], 'Название');
        $fob->add_text(true, 'short_title', $item["short_title"], 'Короткое имя');
        $fob->add_text(true, 'alias', $item["alias"], 'Алиас');
//		$listTemplates = $this->getTamplates();
//		$fob->add_select(false, 'template', $listTemplates, 'Шаблон', '', $item["template_id"]);

		$listOrd = $this->getItemSelectFromOrd($arguments["language"], $item["ord"]);
//		echo "<pre>"; print_r($listOrd); echo "</pre>";
		$fob->add_select(true, 'ord', $listOrd["items"], 'Порядок', '', $listOrd["selectedNow"]);
		$fob->add_checkbox(false, 'enabled', 1, 'Включен', '', '', $item["enabled"]);

		$fob->add_button('submit', 'submit', 'Сохранить', '&nbsp;');

		$output .= $fob->display('return');

		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files();
//			echo "<prE>"; print_r($item); echo "</prE>";

			if(isset($values["language"]) && !empty($values["language"])) {
				$sql = "SELECT COUNT(*) as counts FROM ".$this->cms->tables["languages"]." WHERE alias='".addslashes($values["alias"])."' AND language_id!='".(int)$values["language"]."'";
				$results = $this->dbc->Execute($sql);
				$row = $results->FetchRow();
				if($row["counts"]>0) {
					$this->cms->int_set_message('top', 'Извините, но язык с таким "Алиасом" уже существует', 'Error', 'error');
				}
				else {
					if($values['ord']=="last") {
						$sql = "SELECT MAX(ord) as ord FROM ".$this->cms->tables['languages']."";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$nowOrd = $row["ord"];

						$sql = "UPDATE ".$this->cms->tables['languages']." SET ord=(ord-1) WHERE ord>='".$item["ord"]."'";
						$results = $this->dbc->Execute($sql);

						$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord-1) WHERE parent=0 AND ord>='".$item["ord"]."'";
						$results = $this->dbc->Execute($sql);
					}
					elseif($values['ord']=="first") {
						$sql = "UPDATE ".$this->cms->tables['languages']." SET ord=(ord+1) WHERE ord<='".$item["ord"]."'";
						$results = $this->dbc->Execute($sql);
						$nowOrd = 1;

						$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord+1) WHERE parent=0 AND ord<='".$item["ord"]."'";
						$results = $this->dbc->Execute($sql);
					}
					else {
						$sql = "SELECT ord FROM ".$this->cms->tables['languages']." WHERE language_id='".$values["ord"]."'";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$nowOrd = $row["ord"];

						if($nowOrd>$item["ord"]) {
							$sql = "UPDATE ".$this->cms->tables['languages']." SET ord=(ord-1) WHERE ord<='".$nowOrd."' AND ord>='".$item["ord"]."'";
							$results = $this->dbc->Execute($sql);

							$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord-1) WHERE parent=0 AND ord<='".$nowOrd."' AND ord>='".$item["ord"]."'";
							$results = $this->dbc->Execute($sql);
						}
						elseif($nowOrd<$item["ord"]) {
							$sql = "UPDATE ".$this->cms->tables['languages']." SET ord=(ord+1) WHERE ord>'".$nowOrd."' AND ord<='".$item["ord"]."'";
							$results = $this->dbc->Execute($sql);
							$nowOrd++;

							$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord+1) WHERE parent=0 AND ord>'".$nowOrd."' AND ord<='".$item["ord"]."'";
							$results = $this->dbc->Execute($sql);
						}
					}

					$sql = "UPDATE ".$this->cms->tables["languages"]." SET title='".addslashes($values["title"])."', short_title='".addslashes($values["short_title"])."', alias='".strtolower(addslashes($values["alias"]))."', enabled='".(int)$values["enabled"]."', ord='".$nowOrd."' WHERE language_id='".(int)$values["language"]."'";
					if(!$this->dbc->Execute($sql)) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
						return false;
					}

					$sql = "UPDATE ".$this->cms->tables["structure"]." SET level=1, alias='".strtolower(addslashes($values["alias"]))."', relative_url='".strtolower(addslashes($values["alias"]))."/', title='".addslashes($values["title"])."', title_menu='', title_map='', parent=0, template_id='".(int)$values["template"]."', enabled='".(int)$values["enabled"]."', modification_time=NOW(), ord='".$nowOrd."' WHERE language_id='".(int)$values["language"]."' AND parent=0";
					if($this->dbc->Execute($sql)) {
						$sql = "SELECT s_id FROM ".$this->cms->tables["structure"]." WHERE language_id='".(int)$values["language"]."' AND parent=0";
						$result = $this->dbc->Execute($sql);
						$item = $result->FetchRow();
						$this->updateRelativeURLs($item["s_id"], 1);
						$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('language'=>$values["language"]));
						header('Location: '.$target_url);
					}
					return false;
				}
			}
			else {

				$sql = "SELECT COUNT(*) as counts FROM ".$this->cms->tables["languages"]." WHERE alias='".addslashes($values["alias"])."'";
				$results = $this->dbc->Execute($sql);
				$row = $results->FetchRow();
				if($row["counts"]>0) {
					$this->cms->int_set_message('top', 'Извините, но язык с таким "Системным именем" уже существует', 'Error', 'error');
				}
				else {
					if($values['ord']=="last") {
						$sql = "SELECT MAX(ord) as ord FROM ".$this->cms->tables["languages"]."";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$nowOrd = $row["ord"]+1;
					}
					elseif($values['ord']=="first") {
						$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE parent=0";
						$this->dbc->Execute($sql);
						$sql = "UPDATE ".$this->cms->tables["languages"]." SET ord=(ord+1)";
						$results = $this->dbc->Execute($sql);
						$nowOrd = 1;
					}
					else {
						$sql = "SELECT ord FROM ".$this->cms->tables["languages"]." WHERE language_id='".$values["ord"]."'";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$nowOrd = $row["ord"]+1;

						$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE parent=0 AND ord>='".$nowOrd."'";
						$this->dbc->Execute($sql);

						$sql = "UPDATE ".$this->cms->tables["languages"]." SET ord=(ord+1) WHERE ord>='".$nowOrd."'";
						$results = $this->dbc->Execute($sql);
					}

					$sql = "INSERT INTO ".$this->cms->tables["languages"]." SET title='".addslashes($values["title"])."', short_title='".addslashes($values["short_title"])."', alias='".strtolower(addslashes($values["alias"]))."', enabled='".(int)$values["enabled"]."', ord='".$nowOrd."'";
//					echo $sql."<br>";
					if(!$this->dbc->Execute($sql)) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
						return false;
					}
					$langId = $this->dbc->_insertid();

					$sql = "SELECT template_id FROM ".$this->cms->tables["templates"]." WHERE filename='index'";
					$resultsTemplate = $this->dbc->Execute($sql);
					$rowTemplate = $resultsTemplate->FetchRow();
					$template_id = $rowTemplate["template_id"];

					$sql = "INSERT INTO ".$this->cms->tables["structure"]." SET level='1', alias='".strtolower(addslashes($values["alias"]))."', relative_url='".strtolower(addslashes($values["alias"]))."/', title='".addslashes($values["title"])."', title_menu='', title_map='', parent=0, template_id='".(int)$template_id."', type=0, enabled='".(int)$values["enabled"]."', creation_time=NOW(), ord='".$nowOrd."', language_id='".$langId."'";
//					echo $sql."<br>";
					if($this->dbc->Execute($sql)) {
						$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('language'=>$langId));
						header('Location: '.$target_url);
					}
				}
			}
        }

		return $output;
	}

	function itemDelete($arg) {
		$sql = "SELECT * FROM ".$this->cms->tables['languages']." WHERE language_id='".$arg["language"]."'";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()==0) {
			$this->cms->int_set_message('top', 'Ошибка! Элемент не найден', 'Error', 'error');
			return false;
        }
		else {
			$item = $result->FetchRow();

			$sql = "DELETE FROM ".$this->cms->tables['languages']." WHERE language_id='".$arg["language"]."'";
			$result = $this->dbc->Execute($sql);

			$sql = "UPDATE ".$this->cms->tables['languages']." SET ord=(ord-1) WHERE ord>='".$item["ord"]."'";
			$result = $this->dbc->Execute($sql);

		
			$sql = "DELETE FROM ".$this->cms->tables['structure']." WHERE language_id='".$arg["language"]."'";
			$result = $this->dbc->Execute($sql);

			$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord-1) WHERE ord>='".$item["ord"]."' AND parent=0";
			$result = $this->dbc->Execute($sql);
		}

		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
		header('Location: '.$target_url);
	}
	function switch_state($arg) {
		$sql = "UPDATE ".$this->cms->tables["structure"]." SET enabled=abs(enabled-1) WHERE language_id='".$arg["language"]."'";
		$this->dbc->Execute($sql);
		$sql = "UPDATE ".$this->cms->tables["languages"]." SET enabled=abs(enabled-1) WHERE language_id='".$arg["language"]."'";
		if($this->dbc->Execute($sql)) {
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
			header('Location: '.$target_url);
		}
	}
	function itemMove($arg) {
		$sql = "SELECT language_id FROM ".$this->cms->tables['languages']." WHERE ord='".$arg["ord"]."'";
		$results = $this->dbc->Execute($sql);
		if(!$results) {
			$this->cms->int_set_message('top', 'Неверные параметры для изменения порядка. Не найден опопент', 'Error', 'error');
			return false;
		}

		$oponent = $results->FetchRow();

		$sql = "SELECT ord FROM ".$this->cms->tables['languages']." WHERE language_id='".$arg["language"]."'";
		$results = $this->dbc->Execute($sql);
		if(!$results) {
			$this->cms->int_set_message('top', 'Неверные параметры для изменения порядка. Не найден элемент', 'Error', 'error');
			return false;
		}
		$now = $results->FetchRow();

		$sql = "UPDATE ".$this->cms->tables['languages']." SET ord='".$arg["ord"]."' WHERE language_id='".$arg["language"]."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', 'Ошибка при изменении порядка', 'Error', 'error');
			return false;
		}

		$sql = "UPDATE ".$this->cms->tables['languages']." SET ord='".$now["ord"]."' WHERE language_id='".$oponent["language_id"]."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', 'Ошибка при изменении порядка опонента', 'Error', 'error');
			return false;
		}

		$sql = "UPDATE ".$this->cms->tables['structure']." SET ord='".$arg["ord"]."' WHERE language_id='".$arg["language"]."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', 'Ошибка при изменении порядка (структура)', 'Error', 'error');
			return false;
		}

		$sql = "UPDATE ".$this->cms->tables['structure']." SET ord='".$now["ord"]."' WHERE language_id='".$oponent["language_id"]."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', 'Ошибка при изменении порядка опонента (структура)', 'Error', 'error');
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
		header('Location: '.$target_url);
	}
	function updateRelativeURLs($s_id, $level) {
		$this->updateRelativeUrl($s_id);
		$sql = "UPDATE ".$this->cms->tables['structure']." SET level='".$level."' WHERE s_id='".$s_id."'";
		$this->dbc->Execute($sql);
//		echo $sql."<br>";
		$sql = "SELECT s_id FROM ".$this->cms->tables['structure']." WHERE parent='".$s_id."'";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			while ($item = $result->FetchRow()) {
//				$this->update_relative_url($item['s_id']);
				$this->updateRelativeURLs($item['s_id'], $level+1);
			}
		}
	}

	function updateRelativeUrl($s_id){
		$url = $this->cms->encode_url($s_id);

		if (empty($url)) {
//			return false;
		}
		$query = 'UPDATE '.$this->cms->tables['structure'].' SET relative_url=CONCAT("'.addslashes($url).'", alias, "/") WHERE s_id='.$s_id;
//		echo $query."<br>";
		$result = $this->dbc->Execute($query);
		$templates = array();
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		return true;
	}
	function getTamplates() {
		$sql = 'SELECT template_id, title FROM '.$this->cms->tables['templates'].' ORDER BY title, tpl_alias';
		$result = $this->dbc->Execute($sql);
		$templates = array();
		if ($result->RecordCount()==0) {
			return false;
		}
		else {
			while ($row = $result->FetchRow()){
				$templates[$row['template_id']] = $row['title'];
			}
		}
		return $templates;
    }
	function getItemSelectFromOrd($id=0, $ord=0) {
		$sql = "SELECT language_id, title, ord FROM ".$this->cms->tables["languages"]." ".($id?" WHERE language_id<>'".$id."'":"")."ORDER BY ord ASC";
		$results = $this->dbc->Execute($sql);
		$items = array();
		$items["first"] = 'первым';
		$items["last"] = 'последним';
		$selectedNow = 0;
		if ($results) {
			while ($row = $results->FetchRow()) {
				if($row["language_id"]!=$id) $items[$row["language_id"]] = $row["title"];
				if($row["ord"]<$ord) $selectedNow = $row["language_id"];
			}
		}
		return array("items"=>$items, "selectedNow"=>$selectedNow);
	}
	function showTabs($active_tab='index', $arg = array()) {
		$arguments = array();
		foreach((array)$arg as $key => $value) {
			if($key!="language") $arguments[$key] = $value;
		}
		$output = '';
		$tabs  = array(
			'index'	=> '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', $arguments).'">Список языков</a>',
//			'formItem'	=> '<a href="'.$this->cms->format_url($this->plugin['name'], 'formItem', $arguments).'">[+] Добавить язык</a>',
		);
		foreach((array)$this->tabs as $tab) {
			if(is_array($tab))$tabs[$tab["name"]] = $tab["title"];
		}
		if(count($this->tabs) && $this->tabs["now"]) $active_tab = $this->tabs["now"];
		$active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
		$output .= $this->cms->int_add_tabs($tabs, $active_tab);

		return $output;
    }


    function init(){
        require_once(cms_LIB_PATH.'form_builder.class.php');
        require_once(cms_PATH_TOOLS.'languages.inc.php');
        $this->cms->init_dbc();
        $this->dbc = &$this->cms->dbc;
		$this->lang = new cmsLanguages;

		//$this->lang->initLanguages($this);
		$this->lang->dbc = &$this->dbc;
		$this->lang->cms = &$this->cms;

		$this->cms->pathModule[] = '<a href="index.php?'.$this->cms->request_vars['group'].'=modules">Програмные модули</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Языки сайта</a>';
    }

	function main($command, $arguments) {
		$this->init();                 
		$this->cms->int_set_h1($this->plugin['title']);
		if (method_exists($this, $command)) {
			$result = $this->$command($arguments);
		}
		else {
			switch($command) {
				default:
					$result = $this->index($arguments);
					break;
			}
		}
		$result = $this->cms->parse_template(array('tabs' => $this->showTabs($command, $arguments), 'output' => $result), 'workarea');
		return $result;
    }

}

?>