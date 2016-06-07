<?php

class plugin_admin_interface extends cms_plugin_admin {
    var $plugin         = array();
    var $config         = array();
    //var $items_per_page = 10;
    var $dbc;
    var $fob;
    var $cms         = '';
    //var $i_template     = 'default';
    //var $i_handler      = 'html_editor';
    //var $default_height = 150;
    //var $default_width  = 150;

    function init_fob($form_name, $form_action) {
        $fob = new form_builder($form_name, $form_action, 'post', '', '', '', 'multipart/form-data');
        return $fob;
    }

   	function get_zones() {
        $zones = array();
        $query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' ORDER BY id ASC';
    	$result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        } 
		$zones[0] = "выбрать...";
        while($item = $result->FetchRow()) {
            $zones[$item['id']] = $item['title'];
        }
        return $zones;
    }

    function index($arguments) {
        $output = '';
        
        $s_id        	= (int) $arguments['s_id'];
        $toc_id      	= (int) $arguments['toc_id'];
        $where_cond  	= array();
        $where_cond[] 	= 's_id = '.$s_id;
        $where_cond[] 	= 'toc_id = '.$toc_id;

        $zones = $this->get_zones();

        if (count($zones) <= 1) return "У вас нет ни одной созданой баннерной зоны";

        $query = 'SELECT * FROM '.$this->cms->tables['banners_group'].' WHERE '.join(' AND ', $where_cond);
        $result = $this->dbc->Execute($query);
        if (!$result) {
        	trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        } 
        elseif ($result->RecordCount() == 0) {
        	$item = $this->insert($arguments);
        }  
        else {
        	$item = $result->FetchRow();
		}

        $edit_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments );
        $path = array('<a href="'.$edit_url.'">'.$this->plugin['title'].'</a>');
        $this->cms->int_set_hierarchy($s_id, $path);

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
        $fob->add_hidden('$'.$this->cms->request_vars['command'], 'index');
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[s_id]', $s_id);
        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[toc_id]', $toc_id);
//        $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);

        $fob->add_select(true, 'zone_id', $zones, 'Баннерная зона', '', $item['zone_id'], '');
        $fob->add_text(true, 'lim', $item['lim'], 'Количество отображаемых баннеров', '', 'fob-text', '', 'text', '', ''); 
        $insert_modes[1] = 'HTML';
//		$insert_modes[2] = 'JavaScript'; 
//		$insert_modes[3] = 'iFrame'; 
		
		$fob->add_select(true, 'insert_mode', $insert_modes, 'Метод вставки', '', $item['insert_mode'], ''); 

		if (!empty($item['zone_id']) && !empty($item['lim']) && !empty($item['insert_mode']) && ($item['insert_mode'] != 1)) {
			$fob->add_html('insert_code_n', $this->cms->int_add_h3('Код для вставки баннера:'));
			if ($item['insert_mode'] == 2) {
				$fob->add_html('insert_code', '<div>&lt;script type="text/javascript" language="JavaScript" src="'.ROOT_URL.'banner.php?type=javascript&zone_id='.$item['zone_id'].'&lim='.$item['lim'].'"&gt;&lt;/script&gt;</div>');  
			}
			elseif ($item['insert_mode'] == 3) {
				$fob->add_html('insert_code', '<div>&lt;iframe src="'.ROOT_URL.'banner.php?type=iframe&zone_id='.$item['zone_id'].'&lim='.$item['lim'].'&id=banner_iframe_'.$s_id.'_'.$toc_id.'" scrolling="no" frameborder="0" id="banner_iframe_'.$s_id.'_'.$toc_id.'"&gt;Ваш браузер не поддерживает плавающие фреймы!&lt;/iframe&gt;</div>');  
			}
		}

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        
        $output .= $fob->display('return');

        if ($fob->is_submited()  && $fob->is_valid_submit() ) {
            $values = $fob->get_submited_values();
            //echo "<pre>";print_r($values);die;

            $item = array(
            	'lim'			=> (int) $values['lim'],
            	'insert_mode'	=> (int) $values['insert_mode'],
            	'zone_id'		=> (int) $values['zone_id'],
                );

            $update_sql = array();
            foreach ($item as $key => $val) {
                $update_sql[] = $key.'='.$val;
            }
            $query = 'UPDATE '.$this->cms->tables['banners_group'].' SET '.join(', ', $update_sql).' WHERE '.join(' AND ', $where_cond);
            $result = $this->dbc->Execute($query);
            //die($query);  
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
			$target_url = $this->cms->format_url('sim', 'man', array('s_id' => $s_id));
        	header('Location: '.$target_url);
            //$target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
            //header('Location: '.$target_url);
            return $output;
        }
        return $output;
    }

    function insert($arguments) {
        $output = '';
        $s_id        	= (int) $arguments['s_id'];
        $toc_id      	= (int) $arguments['toc_id'];
        $where_cond 	= array();
//      $where_cond[] = 's_id='.$s_id;
//      $where_cond[] = 'toc_id='.$toc_id;

        $items = array(
       		'lim'      		=> 5, 
       		'insert_mode'	=> 1, 
     		's_id'         	=> $s_id,
     		'toc_id'       	=> $toc_id,
     		'zone_id'      	=> 0,
     		);

        $query = 'INSERT INTO '.$this->cms->tables['banners_group'].' ('.join(', ', array_keys($items)).') VALUES ('.join(', ', $items).')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
        	trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        }
        return $items;
    }

    function init(){
        require_once(cms_LIB_PATH.'form_builder.class.php');
        require_once(cms_LIB_PATH.'images.lib.php');
        $this->dbc = &$this->cms->dbc;
		$this->initTables();
    }
    
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.'banners_manage/tables.php';
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
        setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }

}

?>