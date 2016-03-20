<?php

require_once(cms_LIB_PATH.'cms_structure.class.php');

class plugin_admin_interface extends cms_structure{
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms = '';

	var $top;
	var $language;
	var $elementsStructrure = array();
	var $structureFromSelect = array();

    var $groups = array (
        1 => 'Группа 1',
        2 => 'Группа 2',
        3 => 'Группа 3',
        4 => 'Группа 4',
        5 => 'Группа 5'
    );

    var $structure_types = array (
            0 => 'Страница',
            1 => 'Папка',
            2 => 'Перенаправление'
        );


    function init_fob($form_name, $form_action) {
        $fob = new form_builder($form_name, $form_action, 'post', '', '', '', 'multipart/form-data');
        return $fob;
    }

    function _get_templates() {
        $query = 'SELECT template_id, title FROM '.$this->cms->tables['templates'].' ORDER BY title, tpl_alias';
        $result = $this->dbc->Execute($query);
        $templates = array();
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

    function _get_template($id){
        $query = 'SELECT * FROM '.$this->cms->tables['templates'].' WHERE template_id='.$id;
            $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            return false;
        }
        $template = $result->FetchRow();
        $template['containers'] = $this->get_containers($template['containers']);
        $template['locked_handlers'] = $this->get_locked_handlers($template['handlers_install'], $template['containers']);
        return $template;
    }

    function _print_tabs($active_tab='manage', $s_id=0) {
        setcookie ('pm_simtab', $active_tab,time()+3600*24, '');
        $arguments = array('s_id'=>$s_id);
        $tabs  = array(
            'manage'        => '<a href="'.$this->cms->format_url($this->plugin['name'], 'manage', $arguments).'">управление наполнением</a>',
            'properties'    => '<a href="'.$this->cms->format_url($this->plugin['name'], 'properties', $arguments).'">свойства страницы</a>',
            'words'    => '<a href="'.$this->cms->format_url($this->plugin['name'], 'words', $arguments).'">теги</a>',
            );
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }

    function index($arguments){
        if (!empty($_COOKIE['pm_simtab'])) {
            $tab = $_COOKIE['pm_simtab'];
        } else {
            $tab = 'manage';
        }
        switch($tab){
            default:
            case 'manage':
            return $this->manage($arguments);
            break;

            case 'properties':
            return $this->properties($arguments);
            break;

            case 'subpages':
            return $this->subpages($arguments);
            break;
        }
        return;
    }


    function manage($arguments) {
        $variables = array();
        $output = '';
        $s_id = (int) $arguments['s_id'];
        $this->cms->int_set_hierarchy($s_id);
        $variables['tabs'] = $this->_print_tabs('manage', $s_id);
        
        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
        $result = $this->dbc->Execute($query);

        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }

        $structure = $result->FetchRow();
        
        $variables['output'] =  $this->_print_containers($structure);

        $variables['info'] = $this->_print_short_structure_info($structure);
//      $variables['nearpages'] = $structure['parent'] > 0 ? $variables['nearpages'] = $this->nearpages($structure) : '&nbsp;';
        $output .= $this->cms->parse_template($variables, 'sitem_manage');
        return $output;
    }


    function nearpages($arguments) {
        $output = '';
        $variables = array();
        $parent = (int) $arguments['parent'];
        $current_id = (int) $arguments['s_id'];

        $this->cms->int_set_hierarchy($parent);
        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$parent;
            $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $structure = $result->FetchRow();
        $variables['header'] = 'Соседние страницы';
        $variables['title'] = $this->cms->int_add_h2($structure['title']);
        $variables['tree'] = $this->_print_near_pages($structure, 1, $current_id);
        $variables['add_title'] = 'Добавить соседнюю страницу';
        $variables['add_url'] = $this->cms->format_url('sa', 'add', array('s_id'=>$s_id));
        $output = $this->cms->parse_template($variables, 'sitem_nearpages');
        return $output;
    }


    function subpages($arguments){
        $output = '';
        $variables = array();
        $s_id = (int) $arguments['s_id'];
        $this->cms->int_set_hierarchy($s_id);
        $variables['tabs'] = $this->_print_tabs('subpages', $s_id);
        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $structure = $result->FetchRow();
        $variables['structure_title'] = $this->cms->int_add_h2($structure['title']);
        $variables['structure_tree'] = $this->_print_structure_tree($structure, 1);
        $variables['add_url'] = $this->cms->format_url('sa', 'add', array('s_id'=>$s_id));
        $variables['add_title'] = 'Добавить вложенную страницу';
        $output .= $this->cms->parse_template($variables, 'sitem_subpages');
        return $output;
    }


    function block_add($arguments) {
        $output = '';
        $s_id = (int) $arguments['s_id'];
        $container = $arguments['container'];
        $handler = $arguments['handler'];
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));
        if (empty($this->cms->handlers_list[$handler])) {
            header('Location: '.$target_url);
            return false;
        }

        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL ) {
            $action = 'block_add';
            $access_buttons = array(
                'Назад' => 'history.go(-1);',
                'На главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$handler];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } else if (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$handler.' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $insert = array(
                      's_id' => $s_id,
                      'container' => "'".addslashes($container)."'",
                      'handler' => "'".addslashes($handler)."'"
                      );
        $insert_fields = '';
        $insert_values = '';
        $sql_sep = '';
        while (list($field, $value) = each($insert)) {
            $insert_fields .= $sql_sep.$field;
            $insert_values .= $sql_sep.$value;
            $sql_sep = ', ';
        }
        $query = 'INSERT INTO '.$this->cms->tables['toc'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $id = $this->dbc->insert_id();
        $this->cms->update_cache_id($s_id, 0);
        header('Location: '.$target_url);
    }

    function block_delete($arguments) {
        $output     = '';
        $s_id       = (int) $arguments['s_id'];
        $toc_id     = (int) $arguments['toc_id'];

        $query   = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
        $result  = $this->dbc->Execute($query);

        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }

        $item = $result->FetchRow();

        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL ) {
            $action = 'block_delete';
            $access_buttons = array(
                'Назад' => 'history.go(-1);',
                'На главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$item['handler']];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } else if (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$item['handler'].' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $query = 'DELETE FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id.' and s_id='.$s_id;
        $result = $this->dbc->Execute($query);

        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }

        $this->cms->update_cache_id($s_id, 0);
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));

        header('Location: '.$target_url);
    }

    function block_move($arguments) {
        $output = '';
        $s_id = (int) $arguments['s_id'];
        $toc_id = (int) $arguments['toc_id'];
        $order = (int) $arguments['order'];

        $query   = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
        $result  = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();

        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL ) {
            $action = 'block_move';
            $access_buttons = array(
                'Назад' => 'javascript:history.go(-1);',
                'На главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$item['handler']];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } else if (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$item['handler'].' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $query = 'UPDATE '.$this->cms->tables['toc'].' SET sort_order='.$order.' WHERE toc_id='.$toc_id.' and s_id='.$s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $this->cms->update_cache_id($s_id, $toc_id);
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
    }

    function block_switch_state($arguments) {
        $s_id    = (int) $arguments['s_id'];
        $toc_id  = (int) $arguments['toc_id'];
        $query   = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
        $result  = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();

        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL ) {
            $action = 'block_switch';
            $access_buttons = array(
                'назад' => 'javascript:history.go(-1);',
                'на главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$item['handler']];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } else if (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$item['handler'].' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $enabled = abs($item['enabled']-1);

        $update_query = 'UPDATE '.$this->cms->tables['toc'].' SET enabled='.$enabled.' WHERE toc_id='.$toc_id;
        $update_result = $this->dbc->Execute($update_query);
        if (!$update_result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $this->cms->update_cache_id($s_id, 0);
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
    }

    function block_inheritable($arguments) {
        $s_id    = (int) $arguments['s_id'];
        $toc_id  = (int) $arguments['toc_id'];
        $query   = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();
        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL ) {
            $action = 'block_inheritance';
            $access_buttons = array(
                'Назад' => 'javascript:history.go(-1);',
                'На главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$item['handler']];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } else if (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$item['handler'].' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $inheritable = abs($item['inheritable']-1);

        $update_query = 'UPDATE '.$this->cms->tables['toc'].' SET inheritable='.$inheritable.' WHERE toc_id='.$toc_id;
        $update_result = $this->dbc->Execute($update_query);
        if (!$update_result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
    }

    function block_inherited($arguments) {
        $s_id    = (int) $arguments['s_id'];
        $toc_id = (int) $arguments['toc_id'];
        $query = 'SELECT * FROM '.$this->cms->tables['toc_inh_exclusions'].' WHERE s_id='.$s_id.' and toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()==0) {
            $query = 'INSERT INTO '.$this->cms->tables['toc_inh_exclusions'].'(toc_id, s_id) VALUES('.$toc_id.', '.$s_id.')';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
        } else if ($result->RecordCount()>0) {
            $query = 'DELETE FROM '.$this->cms->tables['toc_inh_exclusions'].' WHERE s_id='.$s_id.' and toc_id='.$toc_id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'man', array('s_id'=>$s_id));
        header('Location: '.$target_url);
    }


    function block_properties($arguments) {
        $variables = array();
        $output = '';
        $s_id = (int) $arguments['s_id'];
        $toc_id = (int) $arguments['toc_id'];
        $query = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();
        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL) {
            $action = 'block_props';
            $access_buttons = array(
                'Назад' => 'history.go(-1);',
                'На главную' => $this->admin_url
            );
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid'], true);
            $plugins_access = $plugins_access[$item['handler']];
            //echo "<br><pre>";print_r($item);print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
            if (!array_key_exists($action, $plugins_access) && !empty($plugins_access['index']) && $plugins_access['index']==true) {
            } elseif (array_key_exists($action, $plugins_access) && $plugins_access[$action]==0) {
                $this->cms->int_set_message('top', 'У вас недостаточно прав для выполнения операции ('.$item['handler'].' -> '.$action.').', 'Доступ запрещен', 'warning', $access_buttons);
                return false;
            }
        }

        $this->cms->int_set_hierarchy($s_id);
        $variables['tabs'] = $this->_print_tabs('manage', $s_id);
        $variables['output_title'] = $this->cms->int_add_h2('Свойства блока');

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
        $fob->add_hidden('$'.$this->cms->request_vars['command'], 'block_props');
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
        $fob->add_checkbox(false, 'enabled', 'enabled', 'Отображать на сайте', '', '', (bool) $item['enabled'], '', '', 'checkbox', '', '');
        $fob->add_text(false, 'sort_order', $item['sort_order'], 'Приоритет', '', '', '', 'text-short', '', '');
        $fob->add_select(false, 'tpl_alias', $this->_get_handler_templates($item['handler']), 'Шаблон', '', $item['tpl_alias'], '', '', '', 'select', '', '');
        $fob->add_checkbox(false, 'inheritable', 'yes', 'Наследуемый блок', '', '', (bool) $item['inheritable'], '', '', 'checkbox', '', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '&nbsp;', '', '', '', 'button', '', '');

        $variables['output'] = $fob->display('return');
        $output .= $this->cms->parse_template($variables, 'sitem_block_properties');
        
        if ($fob->is_submited()  && $fob->is_valid_submit() ) {
            $values = $fob->get_submited_values();
            $update = array(
                          'sort_order'      => (int) $values['sort_order'],
                          'enabled'         => (int) 0+!empty( $values['enabled']),
                          'inheritable'     => (int) 0+!empty($values['inheritable']),
                          'tpl_alias'       => "'".trim(addslashes($values['tpl_alias']))."'"
                          );
            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep.$field.'='.$value.' ';
                $update_sep = ', ';
            }
            $update_query = 'UPDATE '.$this->cms->tables['toc'].' SET '.$update_sql.' WHERE toc_id='.$toc_id;
            $update_result = $this->dbc->Execute($update_query);
            if (!$update_result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
            $this->cms->update_cache_id($s_id, $toc_id);
            $target_url = $this->cms->format_url($this->plugin['name'], 'manage', array('s_id'=>$s_id));
            header('Location: '.$target_url);
            return $this->index(array());
        }
        return $output;
    }

    function _get_handler_templates($handler){
        $list = array(''=>'-');
        if (empty($this->cms->plugins[$handler])) {
            $list = array(''=>'01');
            return $list;
        }
        $tpls_path = $this->cms->plugins[$handler]['path'].'tpls/';
        if (!file_exists($tpls_path) || !is_dir($tpls_path)) {
            $list = array(''=>$tpls_path);
            return $list;
        }

        if ($handle = opendir($tpls_path)) {
            while (false !== ($file_name = readdir($handle))) {
                $file = $tpls_path.$file_name;
                $file_info = pathinfo($file);
                $extension = strtolower($file_info["extension"]);
                if (!is_file($file) || ($extension<>'tpl') ) {
                    continue;
                }
                $tpl_name = substr($file_name, 0, strlen($file_name)-4);
                $list[$tpl_name] = $tpl_name;
            }
            ksort($list);
            closedir($handle);
        } else {
            trigger_error("Error opening ".$tpls_path);
        }
        return $list;
    }

    function _bool2str($boolean){
        if ($boolean) {
            return $this->cms->translate('да', $this->plugin['name']);
        } else {
            return $this->cms->translate('нет', $this->plugin['name']);
        }
    }

    function _get_admin_tpl($tpl_alias){
        $tpl_file = $this->cms->core_path.'/templates/admin.tpls/'.$tpl_alias.'.tpl';
        if (file_exists($tpl_file)) {
            $data = file_get_contents($tpl_file);
            if (!empty($data)) {
                return $data;
            }
        }
        return false;
    }

    function _print_containers($structure){
        $template = $this->_get_template($structure['template_id']);
        $locked = $template['locked_handlers'];
        $containers = $template['containers'];
        $containers_content = array();
        while(list(,$c_alias) = each($containers)){
            $containers_content[$c_alias] = $this->_print_container($structure, $c_alias, $locked[$c_alias]);
        }
        $output = $this->_get_admin_tpl($template['filename']);
        if ($output != false) {
            while(list($c_alias, $c_content)=each($containers_content)){
                $output = str_replace('{$'.$c_alias.'}', $c_content, $output);
            }
        } else {
            $output = '<table class="containers">';
            while(list($c_alias, $c_content)=each($containers_content)){
                $output .= '<tr><td class="item">';
                $output .= $c_content;
                $output .= '</td></tr>';
            }
            $output .= '</table>';
        }
        return $output;
    }

    function _print_container($structure, $container, $locked){
        /* rg@0606 */
        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL){
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid']);
        }
        /* /rg@0606 */
        $output = '';
        $root     = $this->cms->get_root_item();
        $query     = 'SELECT * FROM '.$this->cms->tables['toc_inh_exclusions'].' WHERE s_id='.$structure['s_id'];
            $result = $this->dbc->Execute($query);
        $excl     = array();
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else {
            while ($row = $result->FetchRow()) {
                $excl[$row['toc_id']] = 1;
            }
        }
//		echo "<prE>";print_r($structure); echo "</pre>";
        //$query     = 'SELECT toc.* FROM '.$this->cms->tables['toc'].' toc, '.$this->cms->tables['structure'].' s WHERE ((s.left_key<'.$structure['left_key'].' and s.right_key>'.$structure['right_key'].' and toc.inheritable=1 and toc.enabled=1) OR (s.s_id='.$root['s_id'].' AND toc.inheritable=1 and toc.enabled=1) OR (s.s_id='.$structure['s_id'].') ) AND toc.s_id=s.s_id and (container="'.addslashes($container).'" ) ORDER BY toc.container, toc.sort_order DESC, toc.toc_id ASC';
		$this->getAllParent($structure['s_id'], $parentArr);
//		echo "<prE>";print_r($parentArr); echo "</pre>";
//        $query     = 'SELECT toc.* FROM '.$this->cms->tables['toc'].' toc, '.$this->cms->tables['structure'].' s WHERE ((s.left_key<'.$structure['left_key'].' and s.right_key>'.$structure['right_key'].' and toc.inheritable=1 and toc.enabled=1) OR (s.s_id='.$structure['s_id'].') ) AND toc.s_id=s.s_id and (container="'.addslashes($container).'" ) ORDER BY toc.container, toc.sort_order DESC, toc.toc_id ASC'; // Убрал наследование с главной страницы на все
        $query     = 'SELECT toc.* FROM '.$this->cms->tables['toc'].' toc WHERE (toc.s_id in ('.implode(",", $parentArr).') and toc.inheritable=1 and toc.enabled=1 OR toc.s_id='.$structure['s_id'].') AND (container="'.addslashes($container).'" ) ORDER BY toc.container, toc.sort_order DESC, toc.toc_id ASC'; // Убрал наследование с главной страницы на все//А я кажись поставил:)
//		echo $query."<br><Br>";
        $result = $this->dbc->Execute($query);
		$counter = 0;
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else {
            $total_blocks = $result->RecordCount();
            $rows = '';
            while ($row = $result->FetchRow()){
                $variables = array();
//                $variables['informer'] = $this->exec_informer($row);
                if (empty($this->cms->handlers_list[$row['handler']])) {
                    $variables['plugin_name'] = 'ОШИБКА! Модуль &laquo;'.$row['handler'].'&raquo; не найден!';
                    $variables['delete'] = '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'block_delete', array('toc_id' =>$row['toc_id'], 's_id'=>$structure['s_id'])).'" onclick="return confirm(\'Удалить блок?\')">Удалить</a></span>';
                    $rows .= $this->cms->parse_template($variables, 'sitem_block_error');
                    continue;
                }
// inherited --------------------------------
                if ($row['s_id'] != $structure['s_id'] && $row['inheritable']==1) {
                    $variables['plugin_name'] = '<span title="Модуль:&nbsp;'.$row['handler'].'">'.$this->cms->translate($this->cms->handlers_list[$row['handler']], $this->plugin['name']).'</span>';
                    $inherited_img = empty($excl[$row['toc_id']]) ? 'on' : 'off';
                    $inherited_alt = empty($excl[$row['toc_id']]) ? 'Унаследованный блок включен' : 'Унаследованный блок выключен';
                    $variables['inherited'] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'inherited', array('toc_id'=>$row['toc_id'], 's_id'=>$structure['s_id'])).'"><img src="img/inherited_'.$inherited_img.'.gif" width="22" height="15" alt="'.$inherited_alt.'" title="'.$inherited_alt.'"></a>';
                    $variables['block_page'] = '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'manage', array('s_id'=>$row['s_id'])).'">Страница-родитель</a></span>';
                    $rows .= $this->cms->parse_template($variables, 'sitem_block_inherited');
                    continue;
                }
// native --------------------------------
                $variables['order_up'] = '<img src="/admin/img/px.gif" width="8" height="11">';
                $variables['order_dn'] = '<img src="/admin/img/px.gif" width="8" height="11">';
                $counter++;
                if ($total_blocks > 1) {
                    if ($counter > 1) {
                        $variables['order_up'] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'block_move', array('s_id'=>$structure['s_id'], 'toc_id'=>$row['toc_id'], 'order'=>$row['sort_order']+1)).'"><img src="/admin/img/prior_up.gif" alt="Вверх (текущий приоритет: '.$row['sort_order'].')" title="Вверх (текущий приоритет: '.$row['sort_order'].')"></a>';
                    }
                    if ($counter < $total_blocks) {
                        $variables['order_dn'] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'block_move', array('s_id'=>$structure['s_id'], 'toc_id'=>$row['toc_id'], 'order'=>$row['sort_order']-1)).'"><img src="/admin/img/prior_dn.gif" alt="Вниз (текущий приоритет: '.$row['sort_order'].')" title="Вниз (текущий приоритет: '.$row['sort_order'].')"></a>';
                    }
                }
				$titleHandlerList = $this->cms->handlers_list[$row['handler']];
				if($row['handler'] == "news_ident") {
					$sql = "SELECT title FROM pm_news_str WHERE toc_id='".$row['toc_id']."'";
					$resNews = $this->dbc->Execute($sql);
					if (!$resNews) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
					}
					else {
						$rowNews = $resNews->FetchRow();
						$titleHandlerList .= ' "'.$rowNews["title"].'"';
					}
				}
                $variables['plugin_name'] = !empty($this->cms->managers_list[$row['handler']]) ? '<a href="'.$this->cms->format_url($row['handler'], 'index', array('s_id'=>$structure['s_id'], 'toc_id'=>$row['toc_id'])).'" title="Модуль:&nbsp;'.$row['handler'].'">'.$titleHandlerList.'</a>' : '<span title="Модуль:&nbsp;'.$row['handler'].'">'.$titleHandlerList.'</span>';

                $inheritable_img = $row['inheritable'] ? 'on' : 'off';
                $inheritable_alt = $row['inheritable'] ? 'Наследуемый блок' : 'Не наследуемый блок';
               
				$variables['inheritable'] = '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'inheritable', array('toc_id'=>$row['toc_id'], 's_id'=>$structure['s_id'])).'"><img src="img/inheritable_'.$inheritable_img.'.gif" width="22" height="15" alt="'.$inheritable_alt.'" title="'.$inheritable_alt.'"></a></span>';

                $variables['block_props'] = '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'block_props', array('s_id'=>$structure['s_id'], 'toc_id'=>$row['toc_id'])).'">Свойства</a></span>';

                $switch_class = $row['enabled'] ? 'on' : 'off';
                $switch_txt = $row['enabled'] ? 'вкл' : 'выкл';
                $variables['switch'] = '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'block_switch', array('toc_id'=>$row['toc_id'], 's_id'=>$structure['s_id'])).'" class="'.$switch_class.'">'.$switch_txt.'</a></span>';

                $variables['delete'] = empty($locked[$row['handler']]) ? '<span><a href="'.$this->cms->format_url($this->plugin['name'], 'block_delete', array('toc_id' =>$row['toc_id'], 's_id'=>$structure['s_id'])).'" onclick="return confirm(\'Удалить блок?\')">Удалить</a></span>' : '';

//                $variables['content_props'] = '<span><a href="'.$this->cms->format_url($row['handler'], 'properties', array('s_id'=>$structure['s_id'], 'toc_id'=>$row['toc_id'])).'">Свойства</a></span>';
// --------------------------------
                /* rg@0606 */
                $plugin = $this->cms->plugins[$row['handler']];
                if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL && array_key_exists('permissions', $plugin) && is_array($plugin['permissions']) && count($plugin['permissions']) > 0) {
                    $permissions = $plugins_access[$row['handler']];
                    if (array_key_exists('block_add', $permissions) && $permissions['block_add']==FALSE) {
                    }
                    if (array_key_exists('block_delete', $permissions) && $permissions['block_delete']==FALSE) {
                        $variables['delete'] = '';
                    }
                    if (array_key_exists('block_move', $permissions) && $permissions['block_move']==FALSE) {
                        $variables['order_up'] = '<img src="/admin/img/px.gif" width="8" height="11">';
                        $variables['order_down'] = '<img src="/admin/img/px.gif" width="8" height="11">';
                    }
                    if (array_key_exists('block_switch', $permissions) && $permissions['block_switch']==FALSE) {
                        $variables['inherit'] = '';
                        $variables['switch'] = '';
                    }
                    if (array_key_exists('block_edit', $permissions) && $permissions['block_edit']==FALSE) {
                        $variables['plugin_name'] = '<span title="Модуль:&nbsp;'.$row['handler'].'">'.$this->cms->handlers_list[$row['handler']].'</span>';
                    }
                    if (array_key_exists('block_props', $permissions) && $permissions['block_edit']==FALSE) {
                        $variables['block_props'] = '';
                    }
                }
                /* /rg@0606 */
                $rows .= $this->cms->parse_template($variables, 'sitem_block_native');
            }
        }
        $variables = array(
            'rows'         => $rows,
            'title'        => 'Контейнер',
            'container'    => $container,
            'add_block'    => $this->_print_add_block($structure, $container)
        );
        $output = $this->cms->parse_template($variables, 'sitem_container_table');
        return $output;
    }

	function getAllParent($s_id, &$parentArr) {
		$parentArr[] = $s_id;
		$sql = "SELECT parent FROM ".$this->cms->tables['structure']." WHERE s_id='".$s_id."'";
		$result = $this->dbc->Execute($sql);
		if($result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				if($row["parent"])
					$this->getAllParent($row["parent"], $parentArr);
			}
		}
	}

    function exec_informer($toc) {
        $handler = $this->cms->handlers[$toc['handler']];
        $informer_file = $handler['path'].'informer.class.php';
        if (file_exists($informer_file)) {
            $informer_class_name         = $handler['name'].'_informer';
            if (!class_exists($informer_class_name)) {
                include_once($informer_file);
                if (!class_exists($informer_class_name)) {
                    trigger_error('Plugin interface failed, undefined class: '.$informer_class_name.' in '.basename($informer_file), E_USER_WARNING);
                    return false;
                }
            }
            $content_handler = new $informer_class_name();
            $content_handler->plugin = $handler;
            $content_handler->cms = &$this->cms;
            $content_handler->dbc = &$this->cms->dbc;
            if (file_exists($handler['path'].'plugin.config.php')) {
                include_once($handler['path'].'plugin.config.php');
                $content_handler->config = $plugin_config;
            }
            $handler_result = $content_handler->exec($toc);
            return $handler_result;
        } else {
            trigger_error('Plugin interface failed, file not exists: '.basename($handler_file), E_USER_NOTICE);
            return false;
        }
    }

    function _print_add_block($structure, $container){
        /* rg@0606 */
        if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL){
            $plugins_access = $this->cms->_get_plugins_access($this->cms->user_data['uid']);
        }
        /* /rg@0606 */
        $items = array();
        reset($this->cms->handlers_list);
        while(list($h_alias,$h_title)=each($this->cms->handlers_list)){
            /* rg@0606 */
            $plugin = $this->cms->plugins[$h_alias];
            if ($this->cms->user_data['cms_permissions'] == AP_PARTIAL) {
                $permissions = $plugins_access[$h_alias];
                if (array_key_exists('block_add', $permissions) && $permissions['block_add']==0) {
                    continue;
                }
            }
            $items[$h_title] = $this->cms->format_url($this->plugin['name'], 'block_add', array('s_id'=>$structure['s_id'], 'container'=>$container, 'handler'=>$h_alias));
            /* /rg@0606 */
        }
        return $this->cms->int_get_contex_menu($items);
    }


    function _print_short_structure_info($structure){
        $structure = $this->cms->format_structure_item($structure);
        $template = $this->_get_template($structure['template_id']);
        $variables = array();
        $output = '';
        $variables['header'] = 'Свойства страницы';
        $variables['title'] = $structure['title'];
        $variables['alias'] = $structure['alias'];
        //die(substr($structure['relative_url'], -strlen($structure['relative_url'])+2));
        $variables['preview'] = '<a href="'.(substr($structure['relative_url'], -strlen($structure['relative_url'])+2)).'" target="_blank" title="Открыть страницу в новом окне">Просмотреть страницу</a>';
        if ($structure['static'] == 1) {
            $variables['switch'] = '<span class="faded">статический алиас</span>';
        } elseif ($structure['enabled']) {
            $variables['switch']  = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch', array('s_id'=>$structure['s_id'])).'" class="on">вкл</a>';
        } else {
            $variables['preview'] = '';
            $variables['switch']  = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch', array('s_id'=>$structure['s_id'])).'" class="off">выкл</a>';
        }
        $variables['properties_title'] = 'Подробнее';
        $variables['properties_url'] = $this->cms->format_url($this->plugin['name'], 'properties', array('s_id'=>$structure['s_id']));
        $output .= $this->cms->parse_template($variables, 'sitem_info');
        return $output;
    }

    function _print_near_pages($structure, $depth_limit = 1, $current_id) {
        $output = '';
        $variables = array();
        $max_depth = $structure['level']+$depth_limit;
        $output .= '<div class="str-mini">';
        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE left_key>'.$structure['left_key'].' AND right_key<'.$structure['right_key'].' AND level<'.($max_depth+1).' AND type<6 AND s_id!='.$current_id.' ORDER BY left_key';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            $output .= '<p class="faded">Соседних страниц нет</p>';
        } else if ($result->RecordCount()>0) {
            $base_level = $structure['level']+1;
            while($structure = $result->FetchRow()) {
                if (strlen($structure['title']) > 28) {
                    $structure_title = substr($structure['title'], 0, 25).'...';
                    $structure_alt = $structure['title'].".\nАлиас:&nbsp;".$structure['alias'];
                } else {
                    $structure_title = $structure['title'];
                    $structure_alt = 'Алиас:&nbsp;'.$structure['alias'];
                }
                $suffix = '';
                $class = '';
                switch ($structure['type']) {
                    case 0:  /* page */
                    break;

                    case 1:  /* folder */
                    $class = ' f';
                    break;

                    default:
                    case 2:  /* redirect */
                    $suffix = '&nbsp;<img src="/admin/img/redir_sm.gif">';
                    break;
                }
                $edit_url = $this->cms->format_url('sim', 'man', array('s_id'=>$structure['s_id']));
                $modify_link = $prefix.'<a href="'.$edit_url.'" title="'.$structure_alt.'">'.$structure_title.$suffix.'</a>';
                $output .= '<div class="l2'.$class.'">'.$modify_link.'</div>';
            }
        }
        $output .= '</div>';
        
        
        return $output;
    }

    function _print_structure_tree($structure, $depth_limit = 1) {
        $output = '';
        $max_depth = $structure['level']+$depth_limit;
        $output .= '<div class="str-mini subpages">';
        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE level<'.($max_depth+1).' AND left_key>'.$structure['left_key'].' AND right_key<'.$structure['right_key'].' AND type<6 ORDER BY left_key';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            $output .= '<p class="faded">Вложенных страниц нет</p>';
        } else if ($result->RecordCount()>0) {
            $prev_level = 0;
            $base_level = $structure['level']+1;
            while($structure = $result->FetchRow()) {
                $structure_title = $structure['title'];
                $structure_alt = 'Алиас:&nbsp;'.$structure['alias'];
                $suffix = '';
                $class = '';
                switch ($structure['type']) {
                    case 0:  /* page */
                    break;

                    case 1:  /* folder */
                    $class = ' f';
                    break;

                    default:
                    case 2:  /* redirect */
                    $suffix = '&nbsp;<img src="/admin/img/redir_sm.gif">';
                    break;
                }
                $edit_url = $this->cms->format_url('sim', 'man', array('s_id'=>$structure['s_id']));
                $modify_link = $prefix.'<a href="'.$edit_url.'" title="'.$structure_alt.'">'.$structure_title.$suffix.'</a>';
                $output .= '<div class="l2sub'.$class.'">'.$modify_link.'</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }



    function switch_state($arguments) {
        $s_id = (int) $arguments['s_id'];
        $query = 'SELECT type, enabled, right_key, left_key FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();
        $enabled = abs($item['enabled']-1);
        if ( ($item['right_key']-$item['left_key']==1) && ($item['type']==1) ) {
            $enabled = 0;
        }
        if ($enabled!=$item['enabled']) {
            $this->switch_node($s_id, $enabled);
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'manage', array('s_id'=>$s_id));
        header('Location: '.$target_url);
        return $this->manage(array('s_id'=>$s_id));
    }

	function properties($arguments) {

		$variables = array();
		$output = '';
		$s_id = (int) $arguments['s_id'];
		$query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
		$result = $this->dbc->Execute($query);

		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}

		elseif ($result->RecordCount()<>1) {
			return false;
		}

		$structure = $result->FetchRow();

		$this->cms->int_set_hierarchy($s_id);
		$variables['tabs'] = $this->_print_tabs('properties', $s_id);
		$templates = $this->_get_templates();

//		$output = $this->cms->html_h1($this->plugin['menu'].': '.$structure['title']);
//		$output .= $this->cms->html_h2('Редактирование', '', "100%");
//		$output .= $this->cms->int_add_h2($this->cms->translate('Свойства страницы'));

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'prop');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
		$fob->add_hidden('level', $structure['level']);

		$fob->add_text(true,  'title', $structure['title'], 'Название', 'для страницы и результатов поиска', '', '', 'text', '', '');
        $fob->add_text(true,  'meta_title', $structure['meta_title'], 'Название', 'для meta-title', '', '', 'text', '', '');
		$fob->add_text(false,  'title_menu', $structure['title_menu'], 'Название', 'для меню', '', '', 'text', '', '');
		$fob->add_text(false,  'title_map', $structure['title_map'], 'Название', 'для карты', '', '', 'text', '', '');

		if ((bool) $structure['static'] ) {
			$fob->add_hidden('alias', $structure['alias']);
		}
		else {
			$fob->add_text(true, 'alias', $structure['alias'], 'Алиас', '', '', '', 'text', '', '');
		}

//		echo $s_id."<br>";

		$this->getStructureFromSelect($s_id);
//		echo $this->language."<br>";

		$onchange = 'onchange="changeParent(this, '.$structure["parent"].', \'fob[ord]\')"';
		$js = '
		<script type="text/javascript" language="JavaScript1.2">
			function changeParent(objParent, s_id, name) {
				var objOrd = document.getElementsByName(name);
//				alert(objOrd[name].name);
				if(objParent.value==s_id) objOrd[name].disabled = false;
				else objOrd[name].disabled = true;
			}
		</script>';
		if($structure["parent"])
		$fob->add_select(false, 'parent', $this->structureFromSelect, 'В разделе', '', $structure["parent"], '', '', '', '', array(), '', $onchange, $js);

		$listOrd = $this->getItemSelectFromOrd($structure["parent"], $s_id, $structure["ord"]);
//		echo "<pre>"; print_r($listOrd); echo "</pre>";
		$fob->add_select   (false, 'ord', $listOrd["items"], 'Порядок', '', $listOrd["selectedNow"]);
		
		$fob->add_select   (false, 'template_id', $templates, 'Шаблон', '', $structure['template_id'], '', '', '', 'select', '', '');
		$fob->add_textarea (false, 'meta_keywords', $structure['meta_keywords'], 'Ключевые слова', '', '', 3, '', '', 'textarea', '', '');
		$fob->add_textarea (false, 'meta_description', $structure['meta_description'], 'Описание', '', '', 3, '', '', 'textarea', '', '');
		$fob->add_select   (false, 'type', $this->structure_types, 'Тип', '', $structure['type'], '', '', '', 'select', '', '');
		$fob->add_text     (false, 'redirect_url', $structure['redirect_url'], 'Адрес редиректа', '', '', '', 'text', '', '');

		if ((bool) $structure['static'] ) {
			$fob->add_hidden('enabled', 'true');
		}
		else {
			$fob->add_checkbox(false, 'enabled', 'enabled', 'Отображать на сайте', '', '', (bool) $structure['enabled'], '', '', 'checkbox', '', '');
		}

        //$fob->add_checkbox(false, 'static',  'static',  'Статический алиас', '', '', (bool) $structure['static'], '', '', 'checkbox', '', '');

		/* rg@0606 */
		//if ($this->cms->restrict_site_access) {
		//	$fob->add_checkbox(false, 'restricted',  'restricted',  'Закрытый раздел', '', '', (bool) $structure['restricted'], '', '', 'checkbox', '', '');
		//}

		/* /rg@0606 */
		$fob->add_html('s1', 'Параметры отображения', 'header');
        //$fob->add_checkbox(false, 'show_map', 'yes', 'Показывать в карте сайта', '', '', (bool) $structure['show_map'], '', '', 'checkbox', '', '');
        //$fob->add_checkbox(false, 'show_search', 'yes', 'Показывать в результатах поиска', '', '', (bool) $structure['show_search'], '', '', 'checkbox', '', '');

		$fob->add_checkbox(false, 'show_menu_top', 'yes', 'Показывать в меню', '', '', (bool) $structure['show_menu_top'], '', '', 'checkbox', '', '');
		//$fob->add_checkbox(false, 'show_menu_side', 'yes', 'Вторая строка меню', '', '', (bool) $structure['show_menu_side'], '', '', 'checkbox', '', '');
        //$fob->add_checkbox(false, 'show_menu_top2', 'yes', 'Третья строка меню', '', '', (bool) $structure['show_menu_top2'], '', '', 'checkbox', '', '');
        //$fob->add_checkbox(false, 'second_menu', 'yes', 'Четвертая строка меню', '', '', (bool) $structure['second_menu'], '', '', 'checkbox', '', '');
        //$fob->add_checkbox(false, 'show_menu_bottom', 'yes', 'Меню в футере', '', '', (bool) $structure['show_menu_bottom'], '', '', 'checkbox', '', '');

      //$fob->add_select   (false, 'type',  $this->structure_types, 'Тип',    '', $structure['type'],  '', '', '', 'select', '', '');
        //$fob->add_select   (false, 'group_id', $this->groups,          'Группа', '', $structure['group_id'], '', '', '', 'select', '', '');

        //die($structure['group_id']);

        //die ($structure['group']);

		$fob->add_button('submit', 'submit', 'Сохранить', '', '', '', '', 'button', '', '');

		$variables['output'] = $fob->display('return');

		$output .= $this->cms->parse_template($variables, 'workarea');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {



			$values = $fob->get_submited_values();
//			echo "<pre>"; print_r($values); echo "</pre>";
//			die();
//			if ( ($structure['right_key']-$structure['left_key']==1) && ($values['type']==1) ) {
//				$values['enabled'] = 0;
//			}

			if($structure["parent"]!=$values["parent"]&&$structure["parent"]>0) {
				$sql = "SELECT level, relative_url FROM ".$this->cms->tables['structure']." WHERE s_id='".$values["parent"]."'";
				$result = $this->dbc->Execute($sql);
				$parent = $result->FetchRow();
				$level = $parent["level"]+1;

				$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord-1) WHERE ord>'".$structure["ord"]."' AND parent='".$structure["parent"]."'";
				$this->dbc->Execute($sql);

				$sql = "SELECT MAX(ord) as max_ord FROM ".$this->cms->tables['structure']." WHERE parent='".$values["parent"]."'";
				$result = $this->dbc->Execute($sql);
				$newOrdResult = $result->FetchRow();
				$newOrd = $newOrdResult["max_ord"]+1;

				$parentId = $values["parent"];
			}

			else {
				$level = (int)$values['level'];
				$parentId = $structure['parent'];
				if($structure['ord'] != $values["ord"]) {
					if($values['ord']=="last") {
						$sql = "SELECT MAX(ord) as ord FROM ".$this->cms->tables["structure"]." WHERE parent='".$values['parent']."'";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$newOrd = $row["ord"];
						$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord-1) WHERE parent='".$values['parent']."' AND ord>='".$structure["ord"]."'";
						$this->dbc->Execute($sql);
					}
					elseif($values['ord']=="first") {
						$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord-1) WHERE parent='".$values['parent']."' AND ord>='".$structure["ord"]."'";
						$this->dbc->Execute($sql);
						$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE parent='".$values['parent']."'";
						$this->dbc->Execute($sql);
						$newOrd = 1;
					}
					else {
						$sql = "SELECT ord FROM ".$this->cms->tables["structure"]." WHERE s_id='".$values["ord"]."' AND parent='".$values['parent']."'";
						$results = $this->dbc->Execute($sql);
						$row = $results->FetchRow();
						$newOrd = $row["ord"]+1;

						if($structure["ord"]>$newOrd) {
							$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE ord>='".$newOrd."' AND parent='".$values['parent']."' AND ord<'".$structure["ord"]."'";
						}
						else {
							$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord-1) WHERE ord<='".$newOrd."' AND parent='".$values['parent']."' AND ord>'".$structure["ord"]."'";
						}
						$results = $this->dbc->Execute($sql);
					}
				}
				else $newOrd = $structure['ord'];
			}

			$update = array(
//				'level'                => (int) $values['level'],
				'level'                => $level,
				'alias'                => "'".trim($values['alias'])."'",
				'title'                => "'".trim(addslashes($values['title']))."'",
                'meta_title'           => "'".trim(addslashes($values['meta_title']))."'",
				'title_map'            => "'".trim(addslashes($values['title_map']))."'",
				'title_menu'           => "'".trim(addslashes($values['title_menu']))."'",
				'meta_keywords'        => "'".trim(addslashes($values['meta_keywords']))."'",
				'meta_description'     => "'".trim(addslashes($values['meta_description']))."'",
				'template_id'          => $values['template_id'],
				'type'                 => (int) $values['type'],
				'enabled'              => (int) 0+!empty( $values['enabled']),
//				'static'               => (int) 0+!empty($values['static']),
//				'restricted'           => (int) 0+!empty( $values['restricted']),
				'redirect_url'         => "'".trim(addslashes($values['redirect_url']))."'",
				'show_map'             => (int) 0+!empty( $values['show_map']),
				'show_search'          => (int) 0+!empty( $values['show_search']),
				'show_menu_top'        => (int) 0+!empty( $values['show_menu_top']),
                'show_menu_top2'       => (int) 0+!empty( $values['show_menu_top2']),
                'second_menu'          => (int) 0+!empty( $values['second_menu']),
				'show_menu_side'       => (int) 0+!empty( $values['show_menu_side']),
				'show_menu_bottom'     => (int) 0+!empty( $values['show_menu_bottom']),
				'ord'                  => "'".$newOrd."'",
				'parent'               => $parentId,
//              'group_id'             => $values['group_id']
//				'parent'   => (int) $values['parent']
			);

            // die($values[group]);

			/* rg@0606 */
			if ($this->cms->restrict_site_access) {
				$update['restricted'] = (int) 0+!empty( $values['restricted']);
			}
			/* /rg@0606 */
//			if ($update['parent'] == $id) {unset($update['parent']);}
			if (!$this->is_valid_alias(trim($values['alias']), $structure['parent'], $s_id)) {
				$this->cms->int_set_message('top', $this->cms->translate('Недопустимый алиас. Алиас может содержать только латинские символы. Алиасы не должны совпадать для страниц одного раздела.'), 'Структура', 'error');
				return $output;
			}

			$update_sql = '';
			$update_sep = '';

			while (list($field, $value) = each($update)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}

            //die($update_sql);

			$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET '.$update_sql.' WHERE s_id='.$s_id;

//          die($update_query);

			$update_result = $this->dbc->Execute($update_query);
//			die($update_query);


			if (!$update_result) {

				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			if ($structure['alias']<>$values['alias'] || $structure["parent"]!=$values["parent"]) {
				$this->updateRelativeURL($s_id, $level);
/*
				$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET relative_url="" WHERE left_key>='.$structure['left_key'].' and right_key<='.$structure['right_key'];
				$update_result = $this->dbc->Execute($update_query);
				if (!$update_result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}

				$query = 'SELECT s_id FROM '.$this->cms->tables['structure'].' WHERE left_key>'.$structure['left_key'].' and right_key<'.$structure['right_key'];
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()>0) {
					while ($item = $result->FetchRow()) {
						$this->update_relative_url($item['s_id']);
					}
				}
/**/
			}

			if ($update['template_id']<>$structure['template_id']) {
				$this->_install_handlers($s_id, $update['template_id']);
			}

			$this->update_relative_url($s_id);
			$this->cms->update_cache_id($s_id, 0);
//			$target_url = $this->cms->format_url($this->plugin['name'], 'info', array('s_id'=>$s_id));
			$target_url = $this->cms->format_url("sim", 'index', array('s_id'=>$s_id));

			if($structure["parent"]==0) {
				$sql = "UPDATE ".$this->cms->tables['languages']." SET name='".trim(addslashes($values['title']))."', sys_name='".trim(addslashes($values['alias']))."' WHERE language_id='".$structure["language_id"]."'";
				$this->dbc->Execute($sql);
			}

			header('Location: '.$target_url);
			return $this->index(array());
		}
		return $output;
	}

	function updateRelativeURL($s_id, $level) {
		$this->update_relative_url($s_id);
		$sql = "UPDATE ".$this->cms->tables['structure']." SET level='".$level."' WHERE s_id='".$s_id."'";
		$this->dbc->Execute($sql);
//		echo $sql."<br>";
		$sql = "SELECT s_id FROM ".$this->cms->tables['structure']." WHERE parent='".$s_id."'";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			while ($item = $result->FetchRow()) {
//				$this->update_relative_url($item['s_id']);
				$this->updateRelativeURL($item['s_id'], $level+1);
			}
		}
	}

	function getStructureFromSelect($id, $childs='', $shift='') {
		if(!is_array($this->elementsStructrure) || count($this->elementsStructrure)==0) {
			$this->getAllElementsStructure($id);
//			echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";
		}

		if(!is_array($childs)) {
			$this->structureFromSelect[$this->top] = $shift.substr($this->elementsStructrure[$this->top]["title"], 0, 50);
			if(count($this->elementsStructrure[$this->top]["childs"])>0)
				$this->getStructureFromSelect($id, $this->elementsStructrure[$this->top]["childs"], $shift."&nbsp;&nbsp;&nbsp;");
		}
		else {
			foreach($childs as $s_id) {
				if($id!=$s_id) {
					$this->structureFromSelect[$s_id] = $shift.substr($this->elementsStructrure[$s_id]["title"], 0, 50);
					$this->getStructureFromSelect($id, $this->elementsStructrure[$s_id]["childs"], $shift."&nbsp;&nbsp;&nbsp;");
				}
			}
		}
	
	}
	function getAllElementsStructure($s_id) {
		if(!$this->top) {
			if(!$s_id) {
				$parent = $this->getFirstLanguage();
				$this->top = $parent["s_id"];
				$this->language = $parent["language_id"];
			}
			else {
				$parent = $this->getTopFromId($s_id);
				$this->top = $parent["s_id"];
				$this->language = $parent["language_id"];
			}
		}
		$sql = "SELECT * FROM ".$this->cms->tables['structure']." WHERE language_id='".$this->language."' ORDER BY title ASC";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			while($item = $result->FetchRow()) {
				$item["childs"] = array();
				if($item["parent"]) $this->elementsStructrure[$item["parent"]]["childs"][] = $item["s_id"];
				if(isset($this->elementsStructrure[$item["s_id"]]["childs"]) && count($this->elementsStructrure[$item["s_id"]]["childs"])) {
					$temp = $this->elementsStructrure[$item["s_id"]]["childs"];
					$this->elementsStructrure[$item["s_id"]] = $item;
					foreach($temp as $value) $this->elementsStructrure[$item["s_id"]]["childs"][] = $value;
				}
				else {
					$this->elementsStructrure[$item["s_id"]] = $item;
				}
			}
		}
//		echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";
	}	
	function getTopFromId($s_id) {
		$sql = "SELECT language_id FROM ".$this->cms->tables["structure"]." WHERE s_id='".$s_id."'";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		$row = $result->FetchRow();
		$sql = "SELECT s_id, language_id FROM ".$this->cms->tables["structure"]." WHERE language_id='".$row["language_id"]."' AND level=1";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		$row = $result->FetchRow();
		return $row;
	}
	function getFirstLanguage() {
		$sql = "SELECT s_id, language_id FROM ".$this->cms->tables['structure']." WHERE parent=0 ORDER BY ord ASC LIMIT 0,1";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()==0) {
			return false;
		}
		else {
			$item = $result->FetchRow();
			return array("s_id"=>$item["s_id"], "language_id"=>$item["language_id"]);
		}
	}
	function getItemSelectFromOrd($parent, $id=0, $ord=0) {
		$sql = "SELECT s_id, title, ord FROM ".$this->cms->tables["structure"]." WHERE parent='".$parent."'".($id?" AND s_id<>'".$id."'":"")."ORDER BY ord ASC";
		$results = $this->dbc->Execute($sql);
		$items = array();
		$items["first"] = 'первым';
		$items["last"] = 'последним';
		$selectedNow = 0;
		if ($results) {
			while ($row = $results->FetchRow()) {
				if($row["s_id"]!=$id) $items[$row["s_id"]] = $row["title"];
				if($row["ord"]<$ord) $selectedNow = $row["s_id"];
			}
		}
		return array("items"=>$items, "selectedNow"=>$selectedNow);
	}

	function words($arguments) {
		$output = '';
		$s_id = (int) $arguments['s_id'];
		$query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount()<>1) {
			return false;
		}
		$structure = $result->FetchRow();

		$arrFlip = array();
		if(!empty($structure["words"])) {
			$arrFlip = array_flip(explode("|", substr($structure["words"], 1, -1)));
		}

		$variables['tabs'] = $this->_print_tabs('words', $s_id);

		$this->cms->int_set_hierarchy($s_id);

		$fob = $this->init_fob('fob', $this->cms->admin_url);

		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'words');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);

		$fob->add_html('sep', $this->cms->int_add_h2('Теги'));

		$src = '';
		$src .= '<div class="admin-table2"><div class="width-fix">';
		$src .= '<table>';
		$words = $this->getWords();
		$countWord = count($words);
		$src .= '<tr>';
//		echo "<pre>"; print_r($arrFlip); die();
		foreach($words as $key => $word) {
			$src .= '<td class="act"><input type="checkbox" name="word[]" value="'.$word["word_id"].'"'.((isset($arrFlip[$word["word_id"]])?" checked":"")).'></td>';
			$src .= '<td class="act'.(($key%5==0 && $key>0)?" last":"").'">'.$word["word_ru"].'</td>';
			if($key%5==0 && $key>0) {
				$src .= '</tr>';
				$src .= '<tr>';
			}
		}
		$src .= '</tr>';
		$src .= '</table>';
		$src .= '</div></div>';
		$fob->add_html('sep', $src);

		$fob->add_button('submit', 'submit', 'Сохранить', '', '', '', '', 'button', '', '');

		$variables['output'] = $fob->display('return');
		$output .= $this->cms->parse_template($variables, 'workarea');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();

			$str = '';
			$arrWordId = array();

			if(!empty($_POST["word"]) && count($_POST["word"])) {
				foreach($_POST["word"] as $key => $val) {
					if((int)$val>0) {
						$arrWordId[] = (int)$val;
					}
				}
				if(count($arrWordId)>0) {
					$sql = "UPDATE ".$this->cms->tables['structure']." SET words='|".implode("|", $arrWordId)."|' WHERE s_id='".$s_id."'";
					$result = $this->dbc->Execute($sql);
					if (!$result) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
						return false;
					}
				}
			}
			else {
				$sql = "UPDATE ".$this->cms->tables['structure']." SET words='' WHERE s_id='".$s_id."'";
				$result = $this->dbc->Execute($sql);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
					return false;
				}
			}
//			echo "<pre>"; print_r($arrWordId); die();

			$target_url = $this->cms->format_url($this->plugin['name'], 'words', array('s_id'=>$s_id));
			$target_url = $this->cms->format_url("sa", 'index', array());
			header('Location: '.$target_url);
			return $this->index(array());
		}
		return $output;
	}
	function getWords() {
		$ret = array();
		$sql = "SELECT * FROM pm_words ORDER BY BINARY(word_ru) ASC";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			while($item = $result->FetchRow()) {
				$ret[] = $item;
			}
		}
		return $ret;
	}

    function init(){
        require_once(cms_LIB_PATH.'form_builder.class.php');
        $this->cms->init_dbc();
        $this->dbc = &$this->cms->dbc;
        //$this->templates = $this->_get_templates();
    }

    function main($command, $arguments) {
        $this->init();
        //$this->cms->int_set_h1($this->plugin['title']);
        if (strlen($command)>0 && $command[0]!='_' && method_exists($this, $command)) {
            $result = $this->$command($arguments);
            return $result;
        }
        switch($command) {
            default:
            case 'index':
            $result = $this->index($arguments);
            break;

            case 'man':
            $result = $this->manage($arguments);
            break;

            case 'prop':
            $result = $this->properties($arguments);
            break;

            case 'block_props':
            $result = $this->block_properties($arguments);
            break;

            case 'block_switch':
            $result = $this->block_switch_state($arguments);
            break;

            case 'inheritable':
            $result = $this->block_inheritable($arguments);
            break;

            case 'inherited':
            $result = $this->block_inherited($arguments);
            break;

            case 'switch':
            $result = $this->switch_state($arguments);
            break;

        }
        return $result;
    }


}

?>
