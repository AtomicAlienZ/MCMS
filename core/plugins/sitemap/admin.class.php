<?php
/***********************************************************
* Plugin:		pages map
* @package      phpcms
* @subpackage   plugins
*/
class plugin_admin_interface extends cms_plugin_admin {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms = '';

    function _insert_item($toc_id){
        $insert = array(
                      'toc_id'      => $toc_id,
                      'title'   => "''"
                      );
        $insert_fields = '';
        $insert_values = '';
        $sql_sep = '';
        while (list($field, $value) = each($insert)) {
            $insert_fields .= $sql_sep.$field;
            $insert_values .= $sql_sep.$value;
            $sql_sep = ', ';
        }
        $query = 'INSERT INTO '.$this->cms->tables['common_blocks'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }

        //$content_id = $this->dbc->insert_id();

        $query = 'UPDATE '.$this->cms->tables['toc'].' SET content_id='.$toc_id.' WHERE toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        return $toc_id;
    }

    function index($arguments) {
        $output = '';
        $toc_id = (int) $arguments['toc_id'];
        $s_id = (int) $arguments['s_id'];

//        $structure = $this->cms->get_structure_item($s_id);
        $edit_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id, 'toc_id'=>$toc_id));
		$path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
		$this->cms->int_set_hierarchy($s_id, $path);

        $query = 'SELECT * FROM '.$this->cms->tables['common_blocks'].' WHERE toc_id='.$toc_id;
		$result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()>1) {
            return $output;
        }
        $block = $result->FetchRow();

        $query = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
		$result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return $output;
        }
        $toc = $result->FetchRow();

		if ($toc['content_id']==0){
            $content_id = $this->_insert_item($toc_id);
			if (!$content_id) {
                return $output;
            }
		} else {
			$content_id = $toc['content_id'];
		}

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
        $fob->add_hidden('$'.$this->cms->request_vars['command'], 'index');
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
        $fob->add_text(false,  'title', $block['title'], $this->translate('Title', 'common'), '', 'fob-text', '', 'text', '', '');
        $fob->add_select(false, 'parent', $this->_get_structure(), $this->cms->translate('Parent'), '', $block['parent'], '');
        $fob->add_text(false,  'depth', $block['depth'], $this->translate('Depth'), '', 'fob-short', '', 'text', '', '');
        $fob->add_select(false, 'template', $this->_get_templates(), $this->translate('Template'), '', $block['template'], '');
/*
		$handlers = $this->cms->handlers_list;
		$handlers[''] = '';
		ksort($handlers);
        $fob->add_select(false, 'handler', $handlers, $this->translate('Handler'), '', $block['handler'], '');
*/
        $fob->add_button('submit', 'submit', $this->cms->translate('Save', 'common'), '');
        $output .= $fob->display('return');
        if ($fob->is_submited()  && $fob->is_valid_submit() ) {
            $values = $fob->get_submited_values();
            $update = array(
                          'title'		=> "'".addslashes(trim($values['title']))."'",
                          'parent'    	=> (int) $values['parent'],
                          'template'    => (int) $values['template'],
                          'depth'   	=> (int) $values['depth'],
                          //'handler'		=> "'".addslashes(trim($values['handler']))."'"
                          );
            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep.$field.'='.$value.' ';
                $update_sep = ', ';
            }
            $query = 'UPDATE '.$this->cms->tables['common_blocks'].' SET '.$update_sql.' WHERE toc_id='.$toc_id;
			$result = $this->dbc->Execute($query);
	        if (!$result) {
	            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
	            return false;
	        }
            $this->back2manager($s_id);
			return $output;
        }
        return $output;
    }

    function _get_templates() {
        $query = 'SELECT template_id, title FROM '.$this->cms->tables['templates'].' ORDER BY title, tpl_alias';
		$result = $this->dbc->Execute($query);
        $templates = array(0=>'');
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else {
            while ($row = $result->FetchRow()){
                $templates[$row['template_id']] = $row['title'];
            }
        }
        return $templates;
    }

    function _get_handlers() {
        $query = 'SELECT template_id, title FROM '.$this->cms->tables['templates'].' ORDER BY title, tpl_alias';
		$result = $this->dbc->Execute($query);
        $templates = array(0=>'');
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else {
            while ($row = $result->FetchRow()){
                $templates[$row['template_id']] = $row['title'];
            }
        }
        return $templates;
    }

    function _get_structure($depth_limit = 10) {
        $query = 'SELECT s_id, relative_url, title FROM '.$this->cms->tables['structure'].' WHERE enabled=1 and level<'.($depth_limit+1).' ORDER BY title ASC';
		$result = $this->dbc->Execute($query);
        $structure = array( -1=>'-default-', 0=>'-root-');
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else {
            while ($row = $result->FetchRow()){
				$title = str_replace('/', ' / ', $row['relative_url']);
                $structure[$row['s_id']] = $title;
            }
        }
		return $structure;
    }

    function _delete_content_file($file_url){
        $filename = $this->cms->site_path.substr($file_url,1, strlen($file_url));
        if (!file_exists($filename) || !is_file($filename)) {
            return false;
        }
        if (!is_writeable($filename)) {
            //$this->cms_ai->message_box->add('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');
            return false;
        }
        $result = unlink($filename);
        if (!$result) {
            //$this->cms_ai->message_box->add('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');
            return false;
        }
        return true;
    }

    function back2manager($s_id) {
        $target_url = $this->cms->format_url('sim', 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
		return false;
    }

    function init(){
        require_once(cms_LIB_PATH.'images.lib.php');
        require_once(cms_LIB_PATH.'strings.lib.php');
        require_once(cms_LIB_PATH.'form_builder.class.php');
        $this->dbc    = &$this->cms->dbc;
    }
    function print_tabs($active_tab, $arguments = array()) {
        $tabs = array(
            'index' => $this->plugin['title'],
        );
        $active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
        setcookie('pm_simtab', $active_tab, time()+3600*24, '');
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


}

?>