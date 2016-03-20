<?php
class plugin_admin_interface extends cms_plugin_admin {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;

    function init() {
        require_once(cms_LIB_PATH.'form_builder.class.php');
        //die (cms_LIB_PATH.'form_builder.class.php');
        $this->dbc = &$this->cms->dbc;
    }


//--------------------------------------------------------------------------------------------------------------------------


    function print_tabs($active_tab, $arguments = array()) {
        $tabs = array(
            'index' => $this->plugin['title']
        );
        $active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
        setcookie('pm_simtab', $active_tab, time()+3600*24, '');
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }


    function main($command, $arguments) {
        $this->init();
        switch($command) {
            default:
            case 'index':
            $result = $this->index($arguments);
            break;
        }
        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
        return $result;
    }


//--------------------------------------------------------------------------------------------------------------------------


    function index($arguments) {
        $output = '';
        $toc_id = (int) $arguments['toc_id'];
        $s_id = (int) $arguments['s_id'];

        $edit_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=>$s_id, 'toc_id'=>$toc_id));

        $path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
        $this->cms->int_set_hierarchy($s_id, $path);
        $query = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
                $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() != 1) {
            return $output;
        }

        $toc = $result->FetchRow();
        if ($toc['content_id'] == 0) {
            $content_id = $this->insert_item($toc_id);
            if (!$content_id) {
                return $output;
            }
        } else {
            $content_id = $toc['content_id'];
        }

        $query = 'SELECT * FROM '.$this->cms->tables['content_html'].' WHERE content_id='.$content_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $content = $result->FetchRow();

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$'.$this->cms->request_vars['command'], 'index');
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);

        $fob->add_text(false, 'title', $content['title'], 'Заголовок');

        $fob->add_wysiwyg(false, 'content', $content['content'], 'Содержимое');

        $fob->add_button('submit', 'submit', 'Сохранить', '&nbsp;');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $update = array(
                'title'   => "'".addslashes(trim($values['title']))."'",
                'content'   => "'".addslashes(trim($values['content']))."'"
            );
            $update_sql = '';
            $update_sep = '';

            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep. $field.'='.$value.' ';
                $update_sep = ', ';
            }
            $query = 'UPDATE '.$this->cms->tables['content_html'].' SET '.$update_sql.' WHERE content_id='.$content_id;

            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
            $this->cms->update_cache_id($s_id, $toc_id);
            if($values["submit"]=="Сохранить и остаться") {
                        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array("s_id"=>$s_id, "toc_id"=>$arguments["toc_id"]));
			header('Location: '.$target_url);
                }
            else {$this->back2manager($s_id);}
            return $output;
        }
        return $output;
    }


    function insert_item($toc_id) {
        $insert = array(
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
        $query = 'INSERT INTO '.$this->cms->tables['content_html'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $content_id = $this->dbc->insert_id();
        $query = 'UPDATE '.$this->cms->tables['toc'].' SET content_id='.$content_id.' WHERE toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        return $content_id;
    }


    function back2manager($s_id) {
        $target_url = $this->cms->format_url('sim', 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
        return false;
    }




}

?>
