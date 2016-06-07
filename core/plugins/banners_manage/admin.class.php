<?php

class plugin_admin_interface extends cms_plugin_admin {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $fob;
	var $cms_ai = '';
	//var $item_per_page = 20;

	var $typeBanners = array("1"=>"Изображение", "2"=>"Флэш", "3"=>"Внешний код");

	function init() {
		require_once(cms_LIB_PATH.'form_builder.class.php');
		$this->dbc = $this->cms->dbc;
		$this->initTables();
		
		$this->cms->pathModule[] = '<a href="?'.$this->cms->request_vars['group'].'=modules">Програмные модули</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Управление баннерами</a>';
	}
	
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.$this->plugin["name"].'/tables.php';
		if(file_exists($pathToTable)) {
			include($pathToTable);
			$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			$this->cms->install->dbc = &$this->dbc;
			$this->cms->install->tables = $this->tables;
			$this->cms->install->tablesColumn = $this->tableColumn;
			$this->cms->install->runInstall();
		}
	}
	
	function main($command, $arguments) {
		$this->init();
		if (method_exists($this, $command)) {
			$result = $this->$command($arguments);
			$result = $this->cms->parse_template(array('tabs' => $this->_print_tabs($command, $arguments), 'output' => $result), 'workarea');
			return $result;
		}
		switch($command) {
			default:
			case 'index':
				$result = $this->index($arguments);
				break;
		}
		$result = $this->cms->parse_template(array('tabs' => $this->_print_tabs($command, $arguments), 'output' => $result), 'workarea');
		return $result;
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
	
	function _print_tabs($active_tab='catalogs', $id = 0) {
		$tabs = array(
			'index' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Управление баннерами</a>',
			//'adminmail' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'adminmail', array()).'">Адреса администратора</a>',
		);
		$active_tab = $this->getActiveTabs($tabs, $active_tab);
		setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');

		$output = $this->cms->int_add_tabs($tabs, $active_tab);
		return $output;
	}
	
	function index($arguments) {
		$output = '';
		$output .= $this->_print_zones($arguments);
		$add_link = '<p><input type="button" class="button" value="Добавить зону" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'add_zone', $arguments)."'".'"></p>';
		$output .= $add_link;
		return $output;
	}

	function _print_zones($arguments) {
		$output = '';
		$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' ORDER BY BINARY(title) ASC';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h" style="width: 75%">Название</td>
					<td class="act-h" style="width: 25%">Размер</td>
					<td class="act-h last" colspan="2">Действия</td>
				</tr>';
		while ($item = $result->FetchRow()) {
			$arguments['id'] = $item['id'];
			$arguments['zone_id'] = $item['id'];
			$actions = array(
				'edit' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit_zone', $arguments).'">редактировать</a>',
				'delete' => '<a onclick="return confirm(\'Удалить?\')" href="'.$this->cms->format_url($this->plugin['name'], 'delete_zone', $arguments).'">удалить</a>'
			);
			/*
			switch ($item['insert_mode']) {
				case 1:
				   	$insert_mode = 'HTML';
					break;	
				case 2:
				   	$insert_mode = 'JavaScript';
					break;	
				case 3:
				   	$insert_mode = 'iFrame';
					break;	
			}
			*/
			//$dateValid = Date('d.m.Y H:i:s', strtotime($item['created'])) ;
			$output .= '
				<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 70%"><a href="'.$this->cms->format_url($this->plugin['name'], 'banners', $arguments).'">'.$item['title'].'</a></td>
					<td class="act" style="width: 25%">'.$item['w_size'].'*'.$item['h_size'].'</td>
					<td class="act">'.$actions['edit'].'</td>
					<td class="act last">'.$actions['delete'].'</td>
				</tr>';
		}
		$output .= '</table></div></div>';
		return $output;
	}
	
	function add_zone($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'add_zone', $arguments).'">Добавление зоны</a>';
		$this->cms->intSetHierarchy();
		
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add_zone');
		$fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(true, 'w_size', '', 'Ширина', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_text(true, 'h_size', '', 'Высота', '', 'fob-text', '', 'text', '', '');
		
		/*
		$insert_modes[1] = 'HTML';
		$insert_modes[2] = 'JavaScript'; 
		$insert_modes[3] = 'iFrame'; 
		
		$fob->add_select(false, 'insert_mode', $insert_modes, 'Метод вставки', '', 0, '');   
		*/
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			//$date_ar = explode('.', $values['created']);
			//echo "<pre>"; print_r($date_ar); echo "</pre>";
			//$values['created'] = $date_ar[2]."-".$date_ar[1]."-".$date_ar[0];
			$item = array(
				'title' 		=> "'".addslashes(trim($values['title']))."'",
				'w_size' 		=> (int) $values['w_size'],
				'h_size' 		=> (int) $values['h_size'],  
				//'insert_mode' 	=> (int) $values['insert_mode'],  
			);

			$query = 'INSERT INTO '.$this->cms->tables['banners_zones'].' ('.join(', ', array_keys($item)).') VALUES ('.join(', ',$item).')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	
	function edit_zone($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit_zone', $arguments).'">Редактирование зоны</a>';
		$this->cms->intSetHierarchy();
		
		$output = '';
		$id = (int) $arguments['id'];
		$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			$this->cms->int_set_message('top', 'Required zone not found<br>'.$query, $this->plugin['name'], 'error');
			return false;
		}
		$item = $result->FetchRow();
		
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit_zone');
		//$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[page]', $page);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		
		$fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(true, 'w_size', $item['w_size'], 'Ширина', '', 'fob-text', '', 'text', '', '');  
		$fob->add_text(true, 'h_size', $item['h_size'], 'Высота', '', 'fob-text', '', 'text', '', '');
		
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		
		$output .= $fob->display('return');
		
		if ($fob->is_submited() && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$update = array(
				'title' 		=> "'".addslashes(trim($values['title']))."'",
				'w_size' 		=> (int) $values['w_size'],
				'h_size' 		=> (int) $values['h_size'],  
			);
			
			$update_sql = array();
			foreach ($update as $key=>$val) {
				$update_sql[]= $key.'='.$val;
			}
			
			$query = 'UPDATE '.$this->cms->tables['banners_zones'].' SET '.join(', ', $update_sql).' WHERE id='.$id;
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
	
	function delete_zone($arguments) {
		$id = (int) $arguments['id'];
		//$zone_id = (int) $arguments['zone_id']; 
		// удаляем зону
		$query = 'DELETE FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		} 
		// удаляем баннера  
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE zone_id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		while ($item = $result->FetchRow()) {
			@unlink(ROOT_HTML_PATH.$item['file_url']);
			if (!empty($item['preview'])) {
				@unlink(ROOT_HTML_PATH.$item['preview']);  	
			}
			
			$query = 'DELETE FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
		header('Location: '.$target_url);
	} 
	
	function banners($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'banners', $arguments).'">Просмотр баннеров</a>';
		$this->cms->intSetHierarchy();
		
		$output = '';
		$output .= $this->_print_banners($arguments);
		$add_link = '';
		$add_link .= '<input type="button" class="button" value="Добавить баннер" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'add_banner', $arguments)."&arg[bannertype]=' + document.getElementById('banner_type').value".'">';
		$add_link .= '&nbsp;<select name="banner_type" id="banner_type">';
		foreach($this->typeBanners as $keyType => $titleType) {
			$add_link .= '<option value="'.$keyType.'">'.$titleType.'</option>';
		}
//		$add_link .= '<option value="1">Изображение</option><option value="2">Флэш</option>';
		$add_link .= '</select></p>'; 
		$output .= $add_link;
		return $output;
	}
	
	function _print_banners($arguments) {
		$zone_id = (int) $arguments['zone_id'];   
		$output = '';
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE zone_id = '.$zone_id.' ORDER BY BINARY(title) ASC';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h" style="width: 35%">Название</td>
					<td class="act-h" style="width: 15%">Размер</td>
					<td class="act-h" style="width: 15%">Тип баннера</td>
					<td class="act-h" style="width: 10%">Вес</td>
					<td class="act-h" style="width: 10%">Вкл./Выкл.</td>  
					<td class="act-h" style="width: 15%">Предпоказ</td> 
					<td class="act-h last" colspan="3">Действия</td>
				</tr>';
		while ($item = $result->FetchRow()) {
			$arguments['id'] = $item['id'];
			$actions = array(
				'stat' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'stat_banners', array_merge($arguments, array('type' => $item['type']))).'">статистика</a>',
				'edit' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit_banners', $arguments).'">редактировать</a>',
				'delete' => '<a onclick="return confirm(\'Удалить?\')" href="'.$this->cms->format_url($this->plugin['name'], 'delete_banner', $arguments).'">удалить</a>'
			);
			if(isset($this->typeBanners[$item['type']])) {
				$banner_type = $this->typeBanners[$item['type']];
			}
			else {
				$banner_type = 'Неизвестный формат!!!';
			}
			//$dateValid = Date('d.m.Y H:i:s', strtotime($item['created'])) ;
			$output .= '
				<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 35%"><a href="'.$this->cms->format_url($this->plugin['name'], 'edit_banners', $arguments).'">'.$item['title'].'</a></td>
					<td class="act" style="width: 15%">'.$item['w_size'].'*'.$item['h_size'].'</td>
					<td class="act" style="width: 15%">'.$banner_type.'</td>
					<td class="act" style="width: 10%">'.$item['preference'].'</td>';
			$switch = $item['enabled'] ? '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state_banner', $arguments).'" class="on">вкл</a>' : '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state_banner', $arguments).'" class="off">выкл</a>';
			$output .= '
					<td class="act" style="width: 10%">'.$switch.'</td>
					<td class="act" style="width: 15%">';
			if (!empty($item['preview'])) {
				$output .= '
					<img src="/'.$item['preview'].'">';
			}
			$output .= '
					</td>  
					<td class="act">'.$actions['stat'].'</td> 
					<td class="act">'.$actions['edit'].'</td>
					<td class="act last">'.$actions['delete'].'</td>
				</tr>';
		}
		$output .= '</table></div></div>';
		return $output;
	}
	
	function add_banner($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'banners', array('zone_id' => $arguments['zone_id'])).'">Просмотр баннеров</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'add_banner', $arguments).'">Добавление баннера</a>';
		$this->cms->intSetHierarchy();
		
		$zone_id = (int) $arguments['zone_id'];   
		$type = (int) $arguments['bannertype']; 
		$output = '';
		
		switch ($type) {
			case 1:
				$output .= $this->add_banner_image($arguments); 
				break;	
			case 2:
				$output .= $this->add_banner_flash($arguments); 
				break;	
			case 3:
				$output .= $this->add_banner_code($arguments); 
				break;	
		}

		return $output;
	}
	
	function add_banner_image($arguments) {
		$zone_id = (int) $arguments['zone_id'];   
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add_banner_image');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);
		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', '1', 'Вес', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_text(false, 'url', '', 'Ссылка', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_file('file_url', 'Изображение'); 
		$fob->add_checkbox(false, 'gripe', 1, 'Сжать под размеры зоны', '', '', 0, 'fob-check', '');

		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', '0', 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', 0, 'fob-check', '');
		$fob->add_date('limitLifeStart',  Date('d.m.Y', time()), 'Начало показа');
		$fob->add_date('limitLifeEnd',  Date('d.m.Y', (time()+31*24*3600)), 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files(); 

			if (isset($files['file_url']) && $files['file_url']['error']==0) {
				$file_name = $this->CyrillicToLatin($files['file_url']['name']);
				//$extension = strtolower($file_info["extension"]);
				$fileName = time().'_'.$file_name;
				$file_url = 'content/banners_files/'.$fileName;
				$target = ROOT_HTML_PATH.$file_url;   
				if (!move_uploaded_file($files['file_url']['tmp_name'], $target)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$files['file_url']['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
					return $output;
				}
				
				$file_url_sm = 'content/banners_files/sm_'.$fileName;
				$target_sm = ROOT_HTML_PATH.$file_url_sm;
				if (!copy($target, $target_sm)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$target.' -> '.$target_sm, $this->plugin['name'], 'error');
					return $output;
				}
				$this->cms->files->resizeImageMode($target_sm, 100, 100, 0);
				
				$file_info = getimagesize($target);  
				
				$weight = 0;
				$height = 0;
				if ($values['gripe'] == 1) {
					$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$zone_id;
					$result = $this->dbc->Execute($query);
					if (!$result) {
						trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
						return false;
					}
					$item = $result->FetchRow();
					$this->cms->files->resizeImageMode($target, $item['w_size'], $item['h_size'], 2);
					
					$weight = $item['w_size'];
					$height = $item['h_size'];   
				}
				else {
					$weight = $file_info[0];
					$height = $file_info[1];  	
				}
				@chmod($target, 0777);
				@chmod($target_sm, 0777);

				$insert = array(
					'zone_id'		=> $zone_id,             
					'title'			=> "'".addslashes(trim($values['title']))."'",
					'url'			=> "'".addslashes(trim($values['url']))."'",      
					'file_url'		=> "'".addslashes(trim($file_url))."'",
					'preview'		=> "'".addslashes(trim($file_url_sm))."'", 
					'w_size'		=> (int) $weight,
					'h_size'		=> (int) $height,
					'preference'	=> (int) $values['preference'],
					'type'			=> 1, 
					'enabled' 		=> (int) (0 + !empty($values['enabled'])),
					'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
					'totalShow'		=> (int) $values['totalShow'],
				
				);
				if($values['limitLife'] == 1) {
					$arrDatesStart = explode(".", $values['limitLifeStart']);
					$arrDatesEnd = explode(".", $values['limitLifeEnd']);
					$insert["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
					$insert["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
					$insert["limitLife"] = (int) (0 + !empty($values['limitLife']));
				}
				$insert_fields = '';
				$insert_values = '';
				$sql_sep = '';
				
				while (list($field, $value) = each($insert)) {
					$insert_fields .= $sql_sep.$field;
					$insert_values .= $sql_sep.$value;
					$sql_sep = ', ';
				}
				$query = 'INSERT INTO '.$this->cms->tables['banners'].' ('.$insert_fields.') VALUES('.$insert_values.')';
				$result = $this->dbc->Execute($query);
//				die($query);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
					return false;
				}
			}
			else {
				$this->cms->int_set_message('top', 'Вы не выбрали файл', $this->plugin['name'], 'error');
				return $output;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}
	
	function add_banner_flash($arguments) {
		$zone_id = (int) $arguments['zone_id'];  
		
		$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$zone_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$item = $result->FetchRow();
					 
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add_banner_flash');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);
		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(true, 'w_size', $item['w_size'], 'Ширина', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_text(true, 'h_size', $item['h_size'], 'Высота', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', '1', 'Вес', '', 'fob-text', '', 'text', '', ''); 
		//$fob->add_checkbox(false, 'gripe', 1, 'Сжать под размеры зоны', '', '', 0, 'fob-check', '');
		$fob->add_file('file_url', 'Флэш-ролик'); 
		$fob->add_file('preview', 'Изображение для предпоказа');
		$fob->add_checkbox(false, 'flash_menu', 1, 'Флэш-меню', '', '', 0, 'fob-check', ''); 
		$fob->add_checkbox(false, 'transparent_b', 1, 'Прозрачный фон', '', '', 0, 'fob-check', ''); 
		$fob->add_text(false, 'background', '', 'Цвет фона', '', 'fob-text', '', 'text', '', '');  
		
		$quality[1] = 'Низкое';
		$quality[2] = 'Среднее'; 
		$quality[3] = 'Высокое'; 
		
		$fob->add_select(false, 'quality', $quality, 'Качество', '', '', ''); 

		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', '0', 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', 0, 'fob-check', '');
		$fob->add_date('limitLifeStart',  Date('d.m.Y', time()), 'Начало показа');
		$fob->add_date('limitLifeEnd',  Date('d.m.Y', (time()+31*24*3600)), 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		
		$output .= $fob->display('return');
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files(); 

			if (isset($files['file_url']) && $files['file_url']['error']==0) {
				$file_name = $this->CyrillicToLatin($files['file_url']['name']);
				//$extension = strtolower($file_info["extension"]);
				$fileName = time().'_'.$file_name;
				$file_url = 'content/banners_files/'.$fileName;
				$target = ROOT_HTML_PATH.$file_url;   
				if (!move_uploaded_file($files['file_url']['tmp_name'], $target)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$files['file_url']['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
					return $output;
				} 
				@chmod($target, 0777);

				$insert = array(
					'zone_id'		=> $zone_id,             
					'title'			=> "'".addslashes(trim($values['title']))."'",
					'file_url'		=> "'".addslashes(trim($file_url))."'",
					// существует возможность задавать размеры в процентах (%)
					'w_size'		=> "'".addslashes(trim($values['w_size']))."'",   
					'h_size'		=> "'".addslashes(trim($values['h_size']))."'",   
					'preference'	=> (int) $values['preference'],
					'type'			=> 2, 
					'enabled' 		=> (int) (0 + !empty($values['enabled'])), 
					'flash_menu' 	=> (int) (0 + !empty($values['flash_menu'])),
					'transparent_b' => (int) (0 + !empty($values['transparent_b'])),
					'background'	=> "'".addslashes(trim($values['background']))."'", 
					'quality'		=> (int) $values['quality'],    
					'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
					'totalShow'		=> (int) $values['totalShow'],
				
				);
				if($values['limitLife'] == 1) {
					$arrDatesStart = explode(".", $values['limitLifeStart']);
					$arrDatesEnd = explode(".", $values['limitLifeEnd']);
					$insert["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
					$insert["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
					$insert["limitLife"] = (int) (0 + !empty($values['limitLife']));
				}
				$insert_fields = '';
				$insert_values = '';
				$sql_sep = '';
				
				while (list($field, $value) = each($insert)) {
					$insert_fields .= $sql_sep.$field;
					$insert_values .= $sql_sep.$value;
					$sql_sep = ', ';
				}
				$query = 'INSERT INTO '.$this->cms->tables['banners'].' ('.$insert_fields.') VALUES('.$insert_values.')';
				$result = $this->dbc->Execute($query);
//				die($query);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
					return false;
				}
			}
			else {
				$this->cms->int_set_message('top', 'Вы не выбрали файл', $this->plugin['name'], 'error');
				return $output;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}
	function add_banner_code($arguments) {
		$zone_id = (int) $arguments['zone_id'];   
		$output = '';
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add_banner_code');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);
		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', '1', 'Вес', '', 'fob-text', '', 'text', '', ''); 

		$fob->add_textarea(false, 'code', '', 'Код', '', 30, 20, 'fob-textarea');
		$fob->add_checkbox(false, 'showAfterPage', 1, 'Грузить после загрузки страницы', '', '', 1, 'fob-check', '');

		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', '0', 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', 0, 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', 0, 'fob-check', '');
		$fob->add_date('limitLifeStart',  Date('d.m.Y', time()), 'Начало показа');
		$fob->add_date('limitLifeEnd',  Date('d.m.Y', (time()+31*24*3600)), 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files(); 

			$insert = array(
				'zone_id'		=> $zone_id,             
				'title'			=> "'".addslashes(trim($values['title']))."'",
				'code'			=> "'".addslashes(trim($values['code']))."'",
				'type'			=> 3, 
				'enabled' 		=> (int) (0 + !empty($values['enabled'])),
				'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
				'showAfterPage' 	=> (int) (0 + !empty($values['showAfterPage'])),
				'preference' 	=> (int)$values['preference'],
				'totalShow'		=> (int) $values['totalShow'],
			);
			if($values['limitLife'] == 1) {
				$arrDatesStart = explode(".", $values['limitLifeStart']);
				$arrDatesEnd = explode(".", $values['limitLifeEnd']);
				$insert["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
				$insert["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
				$insert["limitLife"] = (int) (0 + !empty($values['limitLife']));
			}

			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
				
			while (list($field, $value) = each($insert)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$query = 'INSERT INTO '.$this->cms->tables['banners'].' ('.$insert_fields.') VALUES('.$insert_values.')';
			$result = $this->dbc->Execute($query);
//			die($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}
	
	function edit_banners($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'banners', array('zone_id' => $arguments['zone_id'])).'">Просмотр баннеров</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'add_banner', $arguments).'">Редактирование баннера</a>';
		$this->cms->intSetHierarchy();
		
		$id = (int) $arguments['id'];
		  
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$item = $result->FetchRow();
		
		$output = '';
		
		switch ($item['type']) {
			case 1:
				$output .= $this->edit_banners_image($arguments); 
				break;
			case 2:
				$output .= $this->edit_banners_flash($arguments); 
				break;
			case 3:
				$output .= $this->edit_banner_code($arguments); 
				break;	
		}
		
		return $output;
	}
	
	function edit_banners_image($arguments) {
		$output = '';   
		$id = (int) $arguments['id'];
		$zone_id = (int) $arguments['zone_id']; 
		
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			$this->cms->int_set_message('top', 'Required banner not found<br>'.$query, $this->plugin['name'], 'error');
			return false;
		}
		$item = $result->FetchRow();
		
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit_banners_image');
		//$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[page]', $page);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);

		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', $item['preference'], 'Вес', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'url', $item['url'], 'Ссылка', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_file('file_url', 'Изображение'); 
		$fob->add_checkbox(false, 'gripe', 1, 'Сжать под размеры зоны', '', '', 0, 'fob-check', '');
		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool) $item['enabled'], 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', $item['totalShow'], 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', $item['useTotalShow'], 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', $item['limitLife'], 'fob-check', '');
		if($item['limitLifeStart']!='0000-00-00') {
			$arrDatesStart = explode("-", $item['limitLifeStart']);
			$limitLifeStart = $arrDatesStart[2].".".$arrDatesStart[1].".".$arrDatesStart[0];
		}
		else {
			$limitLifeStart = Date('d.m.Y', time());
		}
		$fob->add_date('limitLifeStart',  $limitLifeStart, 'Начало показа');
		if($item['limitLifeEnd']!='0000-00-00') {
			$arrDatesEnd = explode("-", $item['limitLifeEnd']);
			$limitLifeEnd = $arrDatesEnd[2].".".$arrDatesEnd[1].".".$arrDatesEnd[0];
		}
		else {
			$limitLifeEnd = Date('d.m.Y', time()+31*24*3600);
		}
		$fob->add_date('limitLifeEnd',  $limitLifeEnd, 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		
		$output .= $fob->display('return');
		
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files(); 

			if (isset($files['file_url']) && $files['file_url']['error']==0) {  
				@unlink(ROOT_HTML_PATH.$item['file_url']); 
				@unlink(ROOT_HTML_PATH.$item['preview']); 
				
				$file_name = $this->CyrillicToLatin($files['file_url']['name']);
				//$extension = strtolower($file_info["extension"]);
				$fileName = time().'_'.$file_name;
				$file_url = 'content/banners_files/'.$fileName;
				$target = ROOT_HTML_PATH.$file_url;   
				if (!move_uploaded_file($files['file_url']['tmp_name'], $target)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$files['file_url']['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
					return $output;
				}

				$file_url_sm = 'content/banners_files/sm_'.$fileName;
				$target_sm = ROOT_HTML_PATH.$file_url_sm;
				if (!copy($target, $target_sm)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$target.' -> '.$target_sm, $this->plugin['name'], 'error');
					return $output;
				}
				$this->cms->files->resizeImageMode($target_sm, 100, 100, 0);
				
				$file_info = getimagesize($target);  
				
				$weight = 0;
				$height = 0;
				if ($values['gripe'] == 1) {
					$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$zone_id;
					$result = $this->dbc->Execute($query);
					if (!$result) {
						trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
						return false;
					}
					$item = $result->FetchRow();
					$this->cms->files->resizeImageMode($target, $item['w_size'], $item['h_size'], 3);
					
					$weight = $item['w_size'];
					$height = $item['h_size'];   
				}
				else {
					$weight = $file_info[0];
					$height = $file_info[1];  	
				}
				@chmod($target, 0777);
				@chmod($target_sm, 0777);
			}
			else {
				$file_url = $item['file_url'];
				$file_url_sm = $item['preview']; 
				
				$weight = 0;
				$height = 0;
				if ($values['gripe'] == 1) {
					$target = ROOT_HTML_PATH.$item['file_url'];
					$query = 'SELECT * FROM '.$this->cms->tables['banners_zones'].' WHERE id = '.$zone_id;
					$result = $this->dbc->Execute($query);
					if (!$result) {
						trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
						return false;
					}
					$item = $result->FetchRow();
					$this->cms->files->resizeImageMode($target, $item['w_size'], $item['h_size'], 3);
				}
				$weight = $item['w_size'];
				$height = $item['h_size'];   
			}			
			$update = array(
				//'zone_id'		=> $zone_id,             
				'title'			=> "'".addslashes(trim($values['title']))."'",
				'url'			=> "'".addslashes(trim($values['url']))."'",
				'file_url'		=> "'".addslashes(trim($file_url))."'",
				'preview'		=> "'".addslashes(trim($file_url_sm))."'", 
				'w_size'		=> (int) $weight,
				'h_size'		=> (int) $height,
				'preference'	=> (int) $values['preference'],
				//'type'		=> 1, 
				'enabled' 		=> (int) (0 + !empty($values['enabled'])),
				'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
				'totalShow'		=> (int) $values['totalShow'],
			);
			if($values['limitLife'] == 1) {
				$arrDatesStart = explode(".", $values['limitLifeStart']);
				$arrDatesEnd = explode(".", $values['limitLifeEnd']);
				$update["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
				$update["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
				$update["limitLife"] = (int) (0 + !empty($values['limitLife']));
			}
			else {
				$update["limitLifeStart"] = "'0000-00-00'";
				$update["limitLifeEnd"] = "'0000-00-00'";
				$update["limitLife"] = 0;
			}
			
			$update_sql = array();
			foreach ($update as $key=>$val) {
				$update_sql[]= $key.'='.$val;
			}
			
			$query = 'UPDATE '.$this->cms->tables['banners'].' SET '.join(', ', $update_sql).' WHERE id='.$id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}
	
	function edit_banners_flash($arguments) {
		$output = '';   
		$id = (int) $arguments['id'];
		$zone_id = (int) $arguments['zone_id']; 
		
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			$this->cms->int_set_message('top', 'Required banner not found<br>'.$query, $this->plugin['name'], 'error');
			return false;
		}
		$item = $result->FetchRow();
		
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit_banners_flash');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);
		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(true, 'w_size', $item['w_size'], 'Шириа', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_text(true, 'h_size', $item['h_size'], 'Высота', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', $item['preference'], 'Вес', '', 'fob-text', '', 'text', '', ''); 
		//$fob->add_checkbox(false, 'gripe', 1, 'Сжать под размеры зоны', '', '', 0, 'fob-check', '');
		$fob->add_file('file_url', 'Флэш-ролик'); 
		$fob->add_checkbox(false, 'flash_menu', 1, 'Флэш-меню', '', '', (bool) $item['flash_menu'], 'fob-check', ''); 
		$fob->add_checkbox(false, 'transparent_b', 1, 'Прозрачный фон', '', '', (bool) $item['transparent_b'], 'fob-check', ''); 
		$fob->add_text(false, 'background', $item['background'], 'Цвет фона', '', 'fob-text', '', 'text', '', '');  
		
		$quality[1] = 'Низкое';
		$quality[2] = 'Среднее'; 
		$quality[3] = 'Высокое'; 
		
		$fob->add_select(false, 'quality', $quality, 'Качество', '', $item['quality'], ''); 

		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool) $item['enabled'], 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', $item['totalShow'], 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', $item['useTotalShow'], 'fob-check', '');



		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', $item['limitLife'], 'fob-check', '');
		if($item['limitLifeStart']!='0000-00-00') {
			$arrDatesStart = explode("-", $item['limitLifeStart']);
			$limitLifeStart = $arrDatesStart[2].".".$arrDatesStart[1].".".$arrDatesStart[0];
		}
		else {
			$limitLifeStart = Date('d.m.Y', time());
		}
		$fob->add_date('limitLifeStart',  $limitLifeStart, 'Начало показа');
		if($item['limitLifeEnd']!='0000-00-00') {
			$arrDatesEnd = explode("-", $item['limitLifeEnd']);
			$limitLifeEnd = $arrDatesEnd[2].".".$arrDatesEnd[1].".".$arrDatesEnd[0];
		}
		else {
			$limitLifeEnd = Date('d.m.Y', time()+31*24*3600);
		}
		$fob->add_date('limitLifeEnd',  $limitLifeEnd, 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		
		$output .= $fob->display('return');
		
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files(); 

			if (isset($files['file_url']) && $files['file_url']['error']==0) {  
				@unlink(ROOT_HTML_PATH.$item['file_url']); 
				
				$file_name = $this->CyrillicToLatin($files['file_url']['name']);
				//$extension = strtolower($file_info["extension"]);
				$fileName = time().'_'.$file_name;
				$file_url = 'content/banners_files/'.$fileName;
				$target = ROOT_HTML_PATH.$file_url;   
				if (!move_uploaded_file($files['file_url']['tmp_name'], $target)) {
					$this->cms->int_set_message('top', 'Ошибка при закачивании '.$files['file_url']['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
					return $output;
				}
				@chmod($target, 0777);
			}
			else {
				$file_url = $item['file_url'];
			}			
			$update = array(
				//'zone_id'		=> $zone_id,             
				'title'			=> "'".addslashes(trim($values['title']))."'",
				'file_url'		=> "'".addslashes(trim($file_url))."'",
				// существует возможность задавать размеры в процентах (%)
				'w_size'		=> "'".addslashes(trim($values['w_size']))."'",   
				'h_size'		=> "'".addslashes(trim($values['h_size']))."'",   
				'preference'	=> (int) $values['preference'],
				//'type'			=> 2, 
				'enabled' 		=> (int) (0 + !empty($values['enabled'])), 
				'flash_menu' 	=> (int) (0 + !empty($values['flash_menu'])),
				'transparent_b' => (int) (0 + !empty($values['transparent_b'])),
				'background'	=> "'".addslashes(trim($values['background']))."'", 
				'quality'		=> (int) $values['quality'],
				'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
				'totalShow'		=> (int) $values['totalShow'], 
			);
			if($values['limitLife'] == 1) {
				$arrDatesStart = explode(".", $values['limitLifeStart']);
				$arrDatesEnd = explode(".", $values['limitLifeEnd']);
				$update["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
				$update["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
				$update["limitLife"] = (int) (0 + !empty($values['limitLife']));
			}
			else {
				$update["limitLifeStart"] = "'0000-00-00'";
				$update["limitLifeEnd"] = "'0000-00-00'";
				$update["limitLife"] = 0;
			}

			$update_sql = array();
			foreach ($update as $key=>$val) {
				$update_sql[]= $key.'='.$val;
			}
			
			$query = 'UPDATE '.$this->cms->tables['banners'].' SET '.join(', ', $update_sql).' WHERE id='.$id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}
	function edit_banner_code($arguments) {
		$output = '';   
		$id = (int) $arguments['id'];
		$zone_id = (int) $arguments['zone_id']; 
		
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			$this->cms->int_set_message('top', 'Required banner not found<br>'.$query, $this->plugin['name'], 'error');
			return false;
		}
		$item = $result->FetchRow();

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit_banner_code');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[zone_id]', $zone_id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_html('sep', $this->cms->int_add_h2('Основная информация'));
		$fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
		$fob->add_text(false, 'preference', $item['preference'], 'Вес', '', 'fob-text', '', 'text', '', ''); 

		$fob->add_textarea(false, 'code', $item['code'], 'Код', '', 30, 20, 'fob-textarea');
		$fob->add_checkbox(false, 'showAfterPage', 1, 'Грузить после загрузки страницы', '', '', $item['showAfterPage'], 'fob-check', '');

		$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', $item['enabled'], 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по показам'));
		$fob->add_text(false, 'totalShow', $item['totalShow'], 'Лимит показов', '', 'fob-text', '', 'text', '', ''); 
		$fob->add_checkbox(false, 'useTotalShow', 1, 'Включить лимит показов', '', '', $item['useTotalShow'], 'fob-check', '');

		$fob->add_html('sep', $this->cms->int_add_h2('Ограничение жизни по времени'));
		$fob->add_checkbox(false, 'limitLife', 1, 'Включить ограничение', '', '', $item['limitLife'], 'fob-check', '');
		if($item['limitLifeStart']!='0000-00-00') {
			$arrDatesStart = explode("-", $item['limitLifeStart']);
			$limitLifeStart = $arrDatesStart[2].".".$arrDatesStart[1].".".$arrDatesStart[0];
		}
		else {
			$limitLifeStart = Date('d.m.Y', time());
		}
		$fob->add_date('limitLifeStart',  $limitLifeStart, 'Начало показа');
		if($item['limitLifeEnd']!='0000-00-00') {
			$arrDatesEnd = explode("-", $item['limitLifeEnd']);
			$limitLifeEnd = $arrDatesEnd[2].".".$arrDatesEnd[1].".".$arrDatesEnd[0];
		}
		else {
			$limitLifeEnd = Date('d.m.Y', time()+31*24*3600);
		}
		$fob->add_date('limitLifeEnd',  $limitLifeEnd, 'Конец показа');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();

			$update = array(
				'title'			=> "'".addslashes(trim($values['title']))."'",
				'code'			=> "'".addslashes(trim($values['code']))."'",
				'preference' 	=> (int)$values['preference'],
				'enabled' 		=> (int) (0 + !empty($values['enabled'])),
				'useTotalShow' 	=> (int) (0 + !empty($values['useTotalShow'])),
				'showAfterPage' 	=> (int) (0 + !empty($values['showAfterPage'])),
				'totalShow'		=> (int) $values['totalShow'],
			);
			if($values['limitLife'] == 1) {
				$arrDatesStart = explode(".", $values['limitLifeStart']);
				$arrDatesEnd = explode(".", $values['limitLifeEnd']);
				$update["limitLifeStart"] = "'".$arrDatesStart[2]."-".$arrDatesStart[1]."-".$arrDatesStart[0]."'";
				$update["limitLifeEnd"] = "'".$arrDatesEnd[2]."-".$arrDatesEnd[1]."-".$arrDatesEnd[0]."'";
				$update["limitLife"] = (int) (0 + !empty($values['limitLife']));
			}
			else {
				$update["limitLifeStart"] = "'0000-00-00'";
				$update["limitLifeEnd"] = "'0000-00-00'";
				$update["limitLife"] = 0;
			}
			$update_sql = array();
			foreach ($update as $key=>$val) {
				$update_sql[]= $key.'='.$val;
			}
			$query = 'UPDATE '.$this->cms->tables['banners'].' SET '.join(', ', $update_sql).' WHERE id='.$id;
			$result = $this->dbc->Execute($query);
//			die($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'banners', $arguments);
			//echo $target_url;
			header('Location: '.$target_url); 
			return $output; 
		}
		return $output;
	}

	function delete_banner($arguments) {
		$id = (int) $arguments['id'];
		$zone_id = (int) $arguments['zone_id'];  
		  
		$query = 'SELECT * FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$item = $result->FetchRow();
		
		@unlink(ROOT_HTML_PATH.$item['file_url']);
		if (!empty($item['preview'])) {
			@unlink(ROOT_HTML_PATH.$item['preview']);  	
		}
		
		$query = 'DELETE FROM '.$this->cms->tables['banners'].' WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'banners', array('zone_id' => $zone_id));
		header('Location: '.$target_url);
	} 
	
	function switch_state_banner($arguments) {
		$id = (int) $arguments['id'];
		$zone_id = (int) $arguments['zone_id'];  
		
		$query = 'UPDATE '.$this->cms->tables['banners'].' SET enabled = ABS(enabled - 1) WHERE id = '.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}  
		$target_url = $this->cms->format_url($this->plugin['name'], 'banners', array('zone_id' => $zone_id));
		header('Location: '.$target_url);
	}
	
	function stat_banners($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'banners', array('zone_id' => $arguments['zone_id'])).'">Просмотр баннеров</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'add_banner', $arguments).'">Статистика баннера</a>';
		$this->cms->intSetHierarchy();
		
		$id = (int) $arguments['id'];
		$type = (int) $arguments['type'];  
		   
		$output = '';
		$query = "SELECT COUNT(id) as cnt1, COUNT(DISTINCT user_ip) as cnt2 FROM ".$this->cms->tables['banners_stat']." WHERE banner_id = ".$id." AND operation = 'view'";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$views = $result->FetchRow();
		if ($type == 1) {
			//////////////////////////////
			$query = "SELECT COUNT(id) as cnt1, COUNT(DISTINCT user_ip) as cnt2 FROM ".$this->cms->tables['banners_stat']." WHERE banner_id = ".$id." AND operation = 'click'";
			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
			$clicks = $result->FetchRow();
			//////////////////////////////
		}
		$output = '';
		$output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h">Количество просмотров</td>
					<td class="act-h last">'.$views['cnt1'].'</td>
				</tr>
				<tr>
					<td class="title-h">Количество уникальных просмотров</td>
					<td class="act-h last">'.$views['cnt2'].'</td>
				</tr>';
		if ($type == 1) {
			$output .= '
				<tr>
					<td class="title-h">Количество кликов</td>
					<td class="act-h last">'.$clicks['cnt1'].'</td>
				</tr>
				<tr>
					<td class="title-h">Количество уникальных кликов</td>
					<td class="act-h last">'.$clicks['cnt2'].'</td>
				</tr>
				<tr>
					<td class="title-h">Клик./просм.</td>
					<td class="act-h last">'.(($views['cnt1']>0)?number_format(($clicks['cnt1']/$views['cnt1']), 2):'показов еще не было').'</td>
				</tr>
				<tr>
					<td class="title-h">Уникальн. клик./уникальн. просм.</td>
					<td class="act-h last">'.(($views['cnt1']>0)?number_format(($clicks['cnt2']/$views['cnt2']), 2):'показов еще не было').'</td>
				</tr>';
		}
		$output .= '
			</table></div></div>
			<h3>Ссылка для просмотра статистика данного баннера:</h3>
			<a href="'.ROOT_URL.'banners_stats.php?id='.md5($id).'">'.ROOT_URL.'banners_stats.php?id='.md5($id).'</a>';
		return $output;
	}
	
	function CyrillicToLatin($text, $replacespace=true) {
        $result = "";
        $cyr = array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ь"=>"","ы"=>"y","ъ"=>"","э"=>"e","ю"=>"yu","я"=>"ya"," "=>"_","А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E","Ё"=>"E","Ж"=>"Zh","З"=>"Z","И"=>"I","Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"Kh","Ц"=>"Ts","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Sch","Ь"=>"","Ы"=>"Y","Ъ"=>"","Э"=>"E","Ю"=>"Yu","Я"=>"Ya","/"=>"_","\\"=>"_",'"'=>"","'"=>"");
        for($i=0; $i<strlen($text); $i++) {
            if(isset($cyr[$text[$i]])&&($replacespace||($replacespace&&$text[$i]==" ")))$result.=$cyr[$text[$i]];
            else $result.=$text[$i];
        }
        return $result;
    }
}

?>