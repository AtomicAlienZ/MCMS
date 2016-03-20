<?php

class plugin_admin_interface extends cms_plugin_admin {
    var $plugin         = array();
    var $config         = array();
    var $items_per_page = 10;
    var $dbc;
    var $fob;
    var $cms         = '';
//    var $i_template     = 'articles_item';
    var $i_handler      = 'html_editor';
        var $default_height = 150;
        var $default_width  = 150;

    function init_fob($form_name, $form_action) {
		$fob = new form_builder($form_name, $form_action, 'post', '', '', '', 'multipart/form-data');
		return $fob;
    }

	function index($arguments) {
		$s_id    = (int) $arguments['s_id'];
		$toc_id  = (int) $arguments['toc_id'];
		if (!empty($arguments['page'])) {
			$page = abs( (int) $arguments['page']);
		}
		else {
			$page = 1;
		}
		$output = '';
		$structure = $this->cms->get_structure_item($s_id);
		$edit_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
		$path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
		$arguments = array('s_id'=>$s_id, 'toc_id'=>$toc_id, 'page'=>$page);
		if ($page>1) {
			$path[] = '<a href="'.$edit_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments).'">'.$this->plugin['title'].'</a>';
		}
		$this->cms->int_set_hierarchy($s_id, $path);
		$output .= $this->_print_items($s_id, $toc_id, $page);
		$add_link = '<input class="button" type="button" value="Добавить" onClick="window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'item_add', $arguments)."'".'">';
		$output .= $add_link;
		return $output;
	}
	function _print_items($s_id, $toc_id, $page=1){
		$output = '';
		$output .= '<div class="admin-table2"><div class="width-fix"><table>
			<tr>
				<td class="title-h">e-mail</td>
				<td class="act-h last" colspan="3">&nbsp;</td>
			</tr>';
		$cond = 'toc_id='.$toc_id;
		$order_by = 'sort_order DESC, feedback_id DESC';
		$query = 'SELECT * FROM '.$this->cms->tables['feedback'].' WHERE '.$cond.' ORDER BY '.$order_by;
		if (!empty($limit)) {
			$query = $query.' LIMIT 0,'.$limit;
		}
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount()>0) {
			$output .= '';
			while($item = $result->FetchRow()) {
				$arguments = array('feedback_id'=>$item['feedback_id'], 's_id' => $s_id, 'toc_id' => $toc_id);
				if ($item['enabled']) {
					$switch = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arguments).'" class="on">вкл</a>';
				}
				else {
					$switch  = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arguments).'" class="off">выкл</a>';
				}
				$actions = array(
					'edit'    => '<a href="'.$this->cms->format_url($this->plugin['name'], 'item_edit', $arguments).'">редактировать</a>',
					'delete'  => '<a onclick="return confirm(\'Удалить?\')" href="'.$this->cms->format_url($this->plugin['name'], 'item_delete', $arguments).'">удалить</a>'
				);
				$output .= '
					<tr onmouseover="row_ovr(this);" onmouseout="row_out(this);">
						<td class="title"><a href="'.$this->cms->format_url($this->plugin['name'], 'item_edit', $arguments).'">'.$item['mail'].'</a></td>
						<td class="act">'.$switch.'</td>
						<td class="act">'.$actions['edit'].'</td>
						<td class="act last">'.$actions['delete'].'</td>
					</tr>';
			}
		}
		$output .= '</table></div></div>';
		return $output;
	}

	function item_delete($arguments) {
		$s_id        = (int) $arguments['s_id'];
		$toc_id      = (int) $arguments['toc_id'];
		$feedback_id = (int) $arguments['feedback_id'];
		$query = 'SELECT * FROM '.$this->cms->tables['feedback'].' WHERE feedback_id='.$feedback_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount()==0) {
			return false;
		}
		$announce = $result->FetchRow();

		$query = 'DELETE FROM '.$this->cms->tables['feedback'].' WHERE feedback_id='.$feedback_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id, 'toc_id'=>$toc_id));
		header('Location: '.$target_url);
	}

	function item_add($arguments) {
		$output = '';
		$s_id         = (int) $arguments['s_id'];
		$toc_id = (int) $arguments['toc_id'];

		$edit_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id));
		$path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
		$this->cms->int_set_hierarchy($s_id, $path);

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'item_add');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
		$fob->add_text(true,  'mail', '', 'e-mail', '', 'fob-text', '', 'text', '', '');

		$fob->add_checkbox(false, 'enabled', 1, 'Включен', '', '', (bool) $item['enabled'], 'fob-check', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '&nbsp;');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$item = array(
				'mail'           => "'".addslashes(trim($values['mail']))."'",
				'enabled'         => (int)0+!empty($values['enabled']),
				'toc_id'                        => $toc_id,
				's_id'                        => $s_id,
			);
			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
			while (list($field, $value) = each($item)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$query = 'INSERT INTO '.$this->cms->tables['feedback'].' ('.$insert_fields.') VALUES('.$insert_values.')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$feedback_id = $this->dbc->insert_id();
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id, 'toc_id'=>$toc_id));
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}

    function item_edit($arguments) {
        $output = '';
        $s_id        = (int) $arguments['s_id'];
        $toc_id      = (int) $arguments['toc_id'];
        $feedback_id = (int) $arguments['feedback_id'];
        $arguments   = array('feedback_id'=>$feedback_id, 's_id' => $s_id, 'toc_id' => $toc_id);

        $query = 'SELECT * FROM '.$this->cms->tables['feedback'].' WHERE feedback_id='.$feedback_id;
                $result = $this->dbc->Execute($query);
        if (!$result) {
                        trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        } else if ($result->RecordCount()==0) {
            $this->cms->int_set_message('top', 'Required article not found<br>'.$query, $this->plugin['name'], 'error');
                        return false;
        }
        $item = $result->FetchRow();
//         echo "<pre>";print_r($item);die();
        $edit_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments );
                $path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
        $edit_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments );
                $path[] = '<a href="'.$edit_url.'">'.$item['title'].'</a>';

                $this->cms->int_set_hierarchy($s_id, $path);

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
        $fob->add_hidden('$'.$this->cms->request_vars['command'], 'item_edit');
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[feedback_id]', $feedback_id);

        $fob->add_text(   true,   'mail',   $item['mail'],     'e-mail',  '', 'fob-text', '', 'text', '', '');

        $fob->add_checkbox(false, 'enabled', 1, 'Включен', '', '', (bool) $item['enabled'], 'fob-check', '');

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        if ($fob->is_submited()  && $fob->is_valid_submit() ) {
            $values = $fob->get_submited_values();

            $item = array(
                                                  'mail'           => "'".addslashes(trim($values['mail']))."'",
                                                  'enabled'         => (int)0+!empty($values['enabled']),
                         );

            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($item)) {
                $update_sql .= $update_sep.$field.'='.$value.' ';
                $update_sep = ', ';
            }
            $query = 'UPDATE '.$this->cms->tables['feedback'].' SET '.$update_sql.' WHERE feedback_id='.$feedback_id;
                        $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
            header('Location: '.$target_url);
                        return $output;
        }
        return $output;
    }

	function switch_state($arguments) {
		$s_id        = (int) $arguments['s_id'];
		$toc_id      = (int) $arguments['toc_id'];
		$feedback_id = (int) $arguments['feedback_id'];

		$query = 'UPDATE '.$this->cms->tables['feedback'].' SET enabled=abs(enabled-1) WHERE feedback_id='.$feedback_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}

		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id, 'toc_id'=>$toc_id));
		header('Location: '.$target_url);
		return $this->index(array());
    }

    function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		require_once(cms_LIB_PATH.'images.lib.php');
		$this->dbc = &$this->cms->dbc;
		$this->initTables();
    }
    function initTables() {
        $pathToTable = cms_PLUGINS_PATH.$this->plugin['name'].'/tables.php';
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
            'index' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', $arguments).'">'.$this->plugin['title'].'</a>'
        );
        $active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
        setcookie('pm_simtab', $active_tab, time()+3600*24, '');
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }

}

?>