<?php
class plugin_admin_interface extends cms_plugin_admin {
	
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $fob;
	var $cms = '';
	var $defaultLang = '';
	var $langs = array();

	var $path = array();

//Options to move?
	var $options = array(
		"img_w" => 800,
		"img_h" => 800,
		"img_sh_w"	=> 800,
		"img_sh_h"	=> 600,
		"img_sm_w" => 160,
		"img_sm_h" => 120,

		"sep1" => "sep",

		"img_section_w" => 160,
		"img_section_h" => 120,

		"sep2" => "sep",

		"countItemInPage" => 10,
		"countItemInPageAdmin" => 10
	);
	var $options_titles = array(
		"img_w"						=> "Ширина оригинала фотографии",
		"img_h"						=> "Высота оригинала фотографии",
		
		"img_sh_w"					=> "Ширина показываемого размера",
		"img_sh_h"					=> "Высота показываемого размера",
		
		"img_sm_w"					=> "Ширина первой миниатюры",
		"img_sm_h"					=> "Высота первой миниатюры",

		"img_section_w"				=> "Ширина изображения галереи",
		"img_section_h"				=> "Высота изображения галереи",

		"countItemInPage" 			=> "Количество фото на странице",
		"countItemInPageAdmin" 		=> "Количество элементов на странице в CMS"
	);

	function index($arguments) {
		$parent = 0;
		if(isset($arguments['parent'])) {
			$parent = (int)$arguments['parent'];
		}

		$this->setPath($parent);
		$this->cms->intSetHierarchy();

		$addSection = '<input type="button" class="button" value="Добавить '.(($parent)?"галерею":"раздел").'" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'add', array("type"=>0, "parent"=>$parent))."'".'"><SCRIPT LANGUAGE="JavaScript" SRC="/js/gallery_manage.js"></SCRIPT>&nbsp;&nbsp;&nbsp;&nbsp;';
		$addPhoto   = '<input type="button" class="button" value="Добавить фото" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'add', array("type"=>1, "parent"=>$parent))."'".'">&nbsp;&nbsp;&nbsp;&nbsp;';
		$addArchive = '<input type="button" class="button" value="Добавить архив" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'addarchive', array("type"=>1, "parent"=>$parent))."'".'">&nbsp;&nbsp;&nbsp;&nbsp;';
		$addImport  = '<input type="button" class="button" value="Импортировать папку" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'import', array("type"=>1, "parent"=>$parent))."'".'"><SCRIPT LANGUAGE="JavaScript" SRC="/js/gallery_manage.js"></SCRIPT>';
		$output = '';

		$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE gallery_id='".$parent."'";
		$result = $this->dbc->Execute($sql);
		if ($result && $parent) {
			$item = $result->FetchRow();
		}
//		echo "<pre>"; print_r($this->path); echo "</pre>";

		$countSections = $this->getCountItemsByType($parent);
		$countItems = $this->getCountItemsByType($parent, 1);

		$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE parent_id='".$parent."' ORDER BY type ASC, ord DESC, created DESC, BINARY(title_".$this->defaultLang.") ASC";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query!: '.$sql, 'SQL Error', 'error');
			return false;
		}
		elseif($result->RecordCount()>0) {
			/*if(!isset($this->path[1])) {
				if($countItems==0) {
					$output .= $addSection;
				}
			}*/
			if ($countSections==0) {
                $output .= $addSection;
				$output .= $addPhoto;
				$output .= $addArchive;
				$output .= $addImport;
			} else {
			    $output .= $addSection;
				//$output .= $addPhoto;
				$output .= $addArchive;
				$output .= $addImport;
			}			

			$output .= '<br><br><div class="admin-table2"><div class="width-fix"><table>';
			$output .= '<tr>';
			$output .= '<td class="title-h">Название</td>';
			if($countItems>0) {
				$output .= '<td class="act-h">&nbsp;</td>';
			}
			if($parent) {
				$output .= '<td class="act-h" nowrap>Приор.</td>';
				$output .= '<td class="act-h">Вкл/Выкл</td>';
			}
			$output .= '<td class="act-h last" colspan="2">Операции</td>';
			$output .= '</tr>';

			while($item = $result->FetchRow()) {
				$arg = array('id'=>$item['gallery_id'], 'parent'=>$parent);
				$actions = array(
					'edit'      => '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit', $arg).'">изменить</a>',
					'delete'    => '<a onclick="return confirm(\''.$this->translate('Все подразделы и статьи этого раздела также будут удалены. Вы уверены').'?\')" href="'.$this->cms->format_url($this->plugin['name'], 'delete', $arg).'">удалить</a>'
				);
				$output .= '<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">';
				if($item["type"]==0) {
					$output .= '<td class="title" title="'.$item['alias'].'"><a href="'.$this->cms->format_url($this->plugin['name'], 'index', array('parent'=>$item['gallery_id'])).'" class="folder">'.$item['title_'.$this->defaultLang].'</a></td>';
				}
				else {
					$output .= '<td class="title" title="'.$item['alias'].'"><a href="'.$this->cms->format_url($this->plugin['name'], 'edit', $arg).'" class="page">'.$item['title_'.$this->defaultLang].'</a></td>';
				}
				if($countItems>0) {
					$output .= '<td class="act">';
					if($item['img_sm']!='') {
						$output .= '<img src="'.$item['img_sm'].'">';
					}
					else {
						$output .= '&nbsp;';
					}
					$output .= '</td>';
				}
				//Приоритеты, и включенность
				if($parent) {
					$output .= '<td class="act"><input type="text" size="3" id="'.$item['gallery_id'].'" value="'.$item['ord'].'" onkeyup="move(this)"></td>';
					$switch = $item['enabled'] ? '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arg).'" class="on">вкл</a>' : '<a href="'.$this->cms->format_url($this->plugin['name'], 'switch_state', $arg).'" class="off">выкл</a>';
					$output .= '<td class="act">'.$switch.'</td>';
				}
				//Приоритеты и включенность
				$output .= '<td class="act">'.$actions['edit'].'</td>';
				$output .= '<td class="act last">'.$actions['delete'].'</td>';
				$output .= '</tr>';
			}
			$output .= '</table></div></div>';
		}

			/*if(!isset($this->path[1])) {
				if($countItems==0) {
					$output .= $addSection;
				}
			}*/
			if ($countSections==0) {
                $output .= $addSection;
				$output .= $addPhoto;
				$output .= $addArchive;
				$output .= $addImport;
			} else {
			    $output .= $addSection;
				//$output .= $addPhoto;
				$output .= $addArchive;
				$output .= $addImport;
			}	
		return $output;
	}

	//Добавление.
	function add($arguments) {
		$parent = 0;
		$type = 0;
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }
		if(isset($arguments['type'])) { $type = (int)$arguments['type']; }

		$this->setPath($parent);
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'add', array()).'">Добавление</a>';
		$this->cms->intSetHierarchy();

		$output = '';

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[type]', $type);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[parent]', $parent);
		$fob->add_hidden('$parent', $arguments['parent']);

		foreach($this->langs as $lang) {
			if($type==0) {
				$fob->add_text($this->defaultLang==$lang["alias"], 'title_'.$lang["alias"], '', 'Название', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
			else {
				$fob->add_text(false, 'title_'.$lang["alias"], '', 'Название', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
		}

		if($parent>0) {
			$fob->add_text(true, 'alias', time(), 'Алиас', '', 'fob-text', 'text', '', '');

			$fob->add_html('meta', $this->cms->int_add_h2('Мета теги:'));
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_title_'.$lang["alias"], '', 'Тег &lt;title&gt;', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_description_'.$lang["alias"], '', 'Meta-тег &lt;description&gt;', $lang["short_title"], 'fob-text', '',     'text', '', '');
			}
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_keywords_'.$lang["alias"], '', 'Meta-тег &lt;keywords&gt;',$lang["short_title"], 'fob-text', '', 	  'text', '', '');
			}
			$fob->add_html('meta', $this->cms->int_add_h2('Информация:'));
			foreach($this->langs as $lang) {
				$fob->add_wysiwyg(false, 'descr_'.$lang["alias"], '', 'Краткое описание', $lang["short_title"], 30, 3, 'fob-textarea');
			}
			if($type==0) {
				foreach($this->langs as $lang) {
					$fob->add_wysiwyg(false, 'content_'.$lang["alias"], '', 'Содержимое', $lang["short_title"], 30, 20, 'fob-textarea');
				}
			}
			$fob->add_text(false, 'ord', '0', 'Порядок', '', 'fob-text', 'text', '', '');
			$fob->add_file('img', 'Изображение');

			$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool) 1, 'fob-check', '');

			if($type==1) {
				$fob->add_html('meta', $this->cms->int_add_h2('Информация о авторе и источнике:'));
				$fob->add_text(false, 'author', '', 'Автор', '', 'fob-text', 'text', '', '');
				$fob->add_text(false, 'source', '', 'Источник', '', 'fob-text', 'text', '', '');
				$fob->add_text(false, 'source_url', '', 'Ссылка на источник', '', 'fob-text', 'text', '', '');
			}
		}
		else {
			$fob->add_hidden('enabled', 1);
		}

		$fob->add_button('submit', 'submit', 'Сохранить', '');

		$output .= $fob->display('return');		

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files();
			
			if($parent>0) {
				if($this->is_valid_alias($values['alias'])===false) {
					$this->cms->int_set_message('top', 'Алиас '.$values['alias'].' имеет не корректный формат. Алиас может содержать латинские буквы a-z, цифры 0-9 и символы "-", "_"', 'SQL Error', 'error');
					return false;
				}
				if($this->check_exist_alias($parent, $values['alias'])===true) {
					$this->cms->int_set_message('top', 'Алиас '.$values['alias'].' уже существует', 'SQL Error', 'error');
					return false;
				}
				$relative = $this->get_relative_url($parent, $values['alias']);
			}
			
			$insert = array(
				"alias" => "'".addslashes(trim($values['alias']))."'",
				"parent_id" => "'".addslashes(trim($parent))."'",
				"type" => "'".addslashes(trim($type))."'",
				"ord" => "'".(int)$values['ord']."'",
				"created" => "NOW()",
				'enabled'	=> (0 + !empty($values['enabled'])),
				'relative_url'	=> "'".addslashes(trim($relative['relative_url']))."'",
			);
			foreach($this->langs as $lang) {
				$insert['title_'.$lang["alias"]] = "'".addslashes(trim($values['title_'.$lang["alias"]]))."'";
				$insert['meta_title_'.$lang["alias"]] = "'".addslashes(trim($values['meta_title_'.$lang["alias"]]))."'";
				$insert['meta_description_'.$lang["alias"]] = "'".addslashes(trim($values['meta_description_'.$lang["alias"]]))."'";
				if(empty($values['meta_keywords_'.$lang["alias"]])) {
					if($type==1) {
						$values['meta_keywords_'.$lang["alias"]] = implode(",", $this->getKeyWords($values['descr_'.$lang["alias"]]));
					}
					else {
						$values['meta_keywords_'.$lang["alias"]] = implode(",", $this->getKeyWords($values['content_'.$lang["alias"]]));
					}
//					die($values['meta_keywords_'.$lang["alias"]]." ".strlen($values['meta_keywords_'.$lang["alias"]]));
				}
				$insert['meta_keywords_'.$lang["alias"]] = "'".addslashes(trim($values['meta_keywords_'.$lang["alias"]]))."'";
				$insert['descr_'.$lang["alias"]] = "'".addslashes(trim($values['descr_'.$lang["alias"]]))."'";
				$insert['content_'.$lang["alias"]] = "'".addslashes(trim($values['content_'.$lang["alias"]]))."'";
			}

			if($type==1) {
				$insert['author'] = "'".addslashes(trim($values['author']))."'";
				$insert['source'] = "'".addslashes(trim($values['source']))."'";
				$insert['source_url'] = "'".addslashes(trim($values['source_url']))."'";
			}

			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
			while (list($field, $value) = each($insert)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$sql = 'INSERT INTO '.$this->cms->tables['gallery'].' ('.$insert_fields.') VALUES('.$insert_values.')';
//			die($sql);
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
				return false;
			}
			$newId = $this->dbc->insert_id();

			if (!empty($files['img']['name'])) {
				$this->upload_img($files['img'], $newId, $type);
			}

			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("parent"=>$parent));
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}

	function import($arguments) {
		set_time_limit(0);
		$parent = 0;
		$added = 0;
		$output = '';
		$files = array();
		$dir = ROOT_HTML_PATH."upload/";
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }

		$this->setPath($parent);
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'addarchive', array()).'">Добавление</a>';
		$this->cms->intSetHierarchy();

		if(isset($arguments['import'])){
                        $output .= '<p>Добавление папки '.$arguments["import"].':</p>';
			$this->get_dirs($dir.$arguments['import'], $files);
			
                        $alias = time()*100;
			foreach($files as $image){
				if(pathinfo($image, PATHINFO_EXTENSION) == 'jpg' || pathinfo($image, PATHINFO_EXTENSION) == 'gif') {
					$alias++;
					$relative = $this->get_relative_url($parent, $alias);
					$insert = array(
						"alias" => "'".$alias."'",
						"parent_id" => "'".addslashes(trim($parent))."'",
						"type" => "'1'",
						"ord" => "'0'",
						"created" => "NOW()",
						'enabled'	=> (0 + !empty($values['enabled'])),
						'relative_url'	=> "'".addslashes(trim($relative['relative_url']))."'",
					);
					$insert_fields = '';
					$insert_values = '';
					$sql_sep = '';
					while (list($field, $value) = each($insert)) {
						$insert_fields .= $sql_sep.$field;
						$insert_values .= $sql_sep.$value;
						$sql_sep = ', ';
					}
					$sql = 'INSERT INTO '.$this->cms->tables['gallery'].' ('.$insert_fields.') VALUES('.$insert_values.')';
					$result = $this->dbc->Execute($sql);
					if (!$result) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
					return false;
					}
					$newId = $this->dbc->insert_id();

					$this->upload_img($image, $newId, 1);
					$added++;
				}
			}
			$output .= '<p>Добавлено '.$added.' фотографий.</p>';
			

		}

		
		if(isset($arguments['delete'])){
                        $this->rrmdir($dir.$arguments['delete']);
			$output = 'Удалена папка '.$arguments['delete'];
		}
                
		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh))) {
			if(is_dir($dir.$filename) 
			   && $filename != '.' 
			   && $filename != '..')  $folders[] = $filename;
		}
		closedir($dh);	
		$output .= '<div class="admin-table2"><div class="width-fix"><table><tr><td class="title-h">Папки</td><td class="act-h last" colspan="2">Действия</td></tr>';
		if(count($folders)  > 0){
			foreach($folders as $folder){
				$output .= '<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">';
				$output .=		'<td class="title" style="width: 70%" valign="top">'.$folder.'</td>';
				$output .=		'<td class="act last" valign="top"><a href="'.$this->cms->format_url($this->plugin['name'], 'import', array('parent' => $parent, 'import' => $folder)).'">импортировать</a></td>';
				$output .= '<td class="act last" valign="top"><a onclick="return confirm(\'Удалить?\')" href="'.$this->cms->format_url($this->plugin['name'], 'import', array('parent' => $parent, 'delete' => $folder)).'">удалить</a></td>';
				$output .=	'</tr>';
			}
		}
		return $output;
	}

	function addarchive($arguments) {
		$parent = 0;
		$added = 0;
		$failed = ' ';
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }

		$this->setPath($parent);
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'addarchive', array()).'">Добавление</a>';
		$this->cms->intSetHierarchy();

		$output = '';

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'addarchive');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[type]', $type);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[parent]', $parent);
		$fob->add_hidden('$parent', $arguments['parent']);

		if($parent>0) {

			$fob->add_file('zip', 'Архив');
			$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool) 1, 'fob-check', '');

		}
		else {
			$fob->add_hidden('enabled', 1);
		}

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');		

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files();
			if (!empty($files['zip']['name'])) {
					
					//получаем файл.
					$file = $files['zip'];
					$file_info = pathinfo($file['name']);
					$extension = strtolower($file_info["extension"]);
					$fileName = "archive";
					$img_url = 'content/gallery/'.$fileName.'.'.$extension;
					$target = ROOT_HTML_PATH.$img_url;
					if (!move_uploaded_file($file['tmp_name'], $target)) {
						$this->cms->int_set_message('top', 'Ошибка при закачивании '.$file['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
						return $output;
					}
					
					//обработка файлов
					$zip = zip_open($target);
					$alias = time()*100;
					if(is_resource($zip)){
						while($zip_entry = zip_read($zip)){
							
							//извлечение
							zip_entry_open($zip, $zip_entry);
							$path = ROOT_HTML_PATH."content/gallery/".end(explode('/', zip_entry_name($zip_entry)));
							if((end(explode('.', zip_entry_name($zip_entry))) == 'jpg') || (end(explode('.', zip_entry_name($zip_entry))) == 'gif')){ //мне действительно стыдно
								$fp = fopen($path, "w");
								$zip_entry_size = zip_entry_filesize($zip_entry);
								$buf = zip_entry_read($zip_entry, $zip_entry_size);
								fwrite($fp, $buf);
								zip_entry_close($zip_entry);
								fclose($fp);
							
								//добавление фотографии
								$alias++;
								$relative = $this->get_relative_url($parent, $alias);
								$insert = array(
									"alias" => "'".$alias."'",
									"parent_id" => "'".addslashes(trim($parent))."'",
									"type" => "'1'",
									"ord" => "'0'",
									"created" => "NOW()",
									'enabled'	=> (0 + !empty($values['enabled'])),
									'relative_url'	=> "'".addslashes(trim($relative['relative_url']))."'",
								);
								$insert_fields = '';
								$insert_values = '';
								$sql_sep = '';
								while (list($field, $value) = each($insert)) {
									$insert_fields .= $sql_sep.$field;
									$insert_values .= $sql_sep.$value;
									$sql_sep = ', ';
								}
								$sql = 'INSERT INTO '.$this->cms->tables['gallery'].' ('.$insert_fields.') VALUES('.$insert_values.')';
								$result = $this->dbc->Execute($sql);
								if (!$result) {
									$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
								return false;
								}
								$newId = $this->dbc->insert_id();

								$this->upload_img($path, $newId, 1);
								$added++;
							} else {
								$failed .= zip_entry_name($zip_entry) . ' ';
								zip_entry_close($zip_entry);
							}
						}; 
					}
					zip_close($zip);
					
					//удаляем
					unlink($target);
					
					//выводим результат
					if($failed != ' ') $this->cms->int_set_message('top', 'Обработано '.$added.'. Не обработаны следующие файлы: '.$failed, 'Результат', 'error');
					else $this->cms->int_set_message('top', 'Обработано '.$added, 'Результат', 'error');
				
				}
				
				
			}
		return $output; 
	}
	
	
	function edit($arguments) {
		$output = '';
		$parent = 0;
		$id = 0;
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }
		if(isset($arguments['id'])) { $id = (int)$arguments['id']; }

		$this->setPath($parent);
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit', $arguments).'">Изменение</a>';
		$this->cms->intSetHierarchy();

		$query = 'SELECT * FROM '.$this->cms->tables['gallery'].' WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount() != 1) {
			$this->cms->int_set_message('top', 'Элемент не найден', 'Error', 'error');
			return false;
		}

		$item = $result->FetchRow();

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[parent]', $parent);

		foreach($this->langs as $lang) {
			if($item["type"]==0) {
				$fob->add_text($this->defaultLang==$lang["alias"], 'title_'.$lang["alias"], $item['title_'.$lang["alias"]], 'Название', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
			else {
				$fob->add_text(false, 'title_'.$lang["alias"], $item['title_'.$lang["alias"]], 'Название', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
		}

		if($item["parent_id"]>0) {
			$fob->add_text(true, 'alias', $item['alias'], 'Алиас', '', 'fob-text', 'text', '', '');

			$fob->add_html('meta', $this->cms->int_add_h2('Мета теги:'));
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_title_'.$lang["alias"], $item['meta_title_'.$lang["alias"]], 'Тег &lt;title&gt;', $lang["short_title"], 'fob-text', '', 'text', '', '');
			}
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_description_'.$lang["alias"], $item['meta_description_'.$lang["alias"]], 'Meta-тег &lt;description&gt;', $lang["short_title"], 'fob-text', '',     'text', '', '');
			}
			foreach($this->langs as $lang) {
				$fob->add_text(false, 'meta_keywords_'.$lang["alias"], $item['meta_keywords_'.$lang["alias"]], 'Meta-тег &lt;keywords&gt;',$lang["short_title"], 'fob-text', '', 	  'text', '', '');
			}
			$fob->add_html('meta', $this->cms->int_add_h2('Информация:'));
			foreach($this->langs as $lang) {
				$fob->add_wysiwyg(false, 'descr_'.$lang["alias"], $item['descr_'.$lang["alias"]], 'Краткое описание', $lang["short_title"], 30, 3, 'fob-textarea');
			}
			if($item["type"]==0) {
				foreach($this->langs as $lang) {
					$fob->add_wysiwyg(false, 'content_'.$lang["alias"], $item['content_'.$lang["alias"]], 'Содержимое', $lang["short_title"], 30, 20, 'fob-textarea');
				}
			}

			$fob->add_text(false, 'ord', $item['ord'], 'Порядок', '', 'fob-text', 'text', '', '');
			$fob->add_file('img', 'Изображение');
			if (!empty($item['img_sm'])) {
				 $img = '<img src="'.$item['img_sm'].'" border="0"><br><a href="'.$this->cms->format_url($this->plugin['name'], 'deleteImg', $arguments).'" onclick="return confirm(\'Удалить?\');">Удалить</a>';
				 $fob->add_html('img', $img, 'html_code');
			}
			$fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', $item['enabled'], 'fob-check', '');

			if($item["type"]==1) {
				$fob->add_html('meta', $this->cms->int_add_h2('Информация о авторе и источнике:'));
				$fob->add_text(false, 'author', $item['author'], 'Автор', '', 'fob-text', 'text', '', '');
				$fob->add_text(false, 'source', $item['source'], 'Источник', '', 'fob-text', 'text', '', '');
				$fob->add_text(false, 'source_url', $item['source_url'], 'Ссылка на источник', '', 'fob-text', 'text', '', '');
			}
		}
		else {
			$fob->add_hidden('enabled', '1');
		}
		
		$fob->add_button('submit', 'submit', 'Сохранить', '');

		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files();

			if($item["parent_id"]>0) {
				if (!empty($files['img']['name'])) {
					$this->upload_img($files['img'], $id, $item["type"]);
				}

				if($this->is_valid_alias($values['alias'])===false) {
					$this->cms->int_set_message('top', 'Алиас '.$values['alias'].' имеет не корректный формат. Алиас может содержать латинские буквы a-z, цифры 0-9 и символы "-", "_"', 'SQL Error', 'error');
					return false;
				}
				if($this->check_exist_alias($parent, $values['alias'], $id)===true) {
					$this->cms->int_set_message('top', 'Алиас '.$values['alias'].' уже существует', 'SQL Error', 'error');
					return false;
				}

				$relative = $this->get_relative_url($parent, $values['alias']);
				if($values['alias']!=$item['alias']) {
					$this->update_child_relative($item, $relative);
				}
			}

			$update = array();
			$update = array(
				"alias" => "'".addslashes(trim($values['alias']))."'",
				"ord" => "'".(int)$values['ord']."'",
				'enabled'	=> (0 + !empty($values['enabled'])),
				'relative_url'	=> "'".addslashes(trim($relative['relative_url']))."'",
			);
			foreach($this->langs as $lang) {
				$update['title_'.$lang["alias"]] = "'".addslashes(trim($values['title_'.$lang["alias"]]))."'";
				$update['meta_title_'.$lang["alias"]] = "'".addslashes(trim($values['meta_title_'.$lang["alias"]]))."'";
				$update['meta_description_'.$lang["alias"]] = "'".addslashes(trim($values['meta_description_'.$lang["alias"]]))."'";
				$update['meta_keywords_'.$lang["alias"]] = "'".addslashes(trim($values['meta_keywords_'.$lang["alias"]]))."'";
				$update['descr_'.$lang["alias"]] = "'".addslashes(trim($values['descr_'.$lang["alias"]]))."'";
				$update['content_'.$lang["alias"]] = "'".addslashes(trim($values['content_'.$lang["alias"]]))."'";
			}
			if($item["type"]==1) {
				$update['author'] = "'".addslashes(trim($values['author']))."'";
				$update['source'] = "'".addslashes(trim($values['source']))."'";
				$update['source_url'] = "'".addslashes(trim($values['source_url']))."'";
			}
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($update)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$query = 'UPDATE '.$this->cms->tables['gallery'].' SET '.$update_sql.' WHERE gallery_id='.$id;
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			//Редиректим в тот раздел, куда добавляли новый.
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("parent"=>$parent));
			header('Location: '.$target_url);
			return $output;
		}
		return $output;
	}
	function deleteImg($arguments) {
		$parent = (int) $arguments['parent'];
		$id = (int) $arguments['id'];
		$query = 'SELECT * FROM '.$this->cms->tables['gallery'].' WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount()==0) {
			return false;
		}
		$item = $result->FetchRow();
		$this->dieImg($item);
		$query = 'UPDATE '.$this->cms->tables['gallery'].' SET img="", img_sh="", img_sm="" WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'edit', $arguments);
		header('Location: '.$target_url);
	}
	function dieImg($item) {
		$filename = ROOT_HTML_PATH.substr($item['img'],1, strlen($item['img']));
		$this->cms->files->deleteFile($filename);
		
		$filename = ROOT_HTML_PATH.substr($item['img_sh'],1, strlen($item['img_sh']));
		$this->cms->files->deleteFile($filename);

		$filename = ROOT_HTML_PATH.substr($item['img_sm'],1, strlen($item['img_sm']));
		$this->cms->files->deleteFile($filename);

	}

	function setPath($id) {
		if($id>0) {
			$sql = "SELECT * FROM ".$this->cms->tables['gallery']." WHERE gallery_id='".$id."'";
			$result = $this->dbc->Execute($sql);
			if ($result && $result->RecordCount() > 0) {
				$item = $result->FetchRow();
				$this->setPath($item["parent_id"]);
				$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array("parent"=>$item["gallery_id"])).'">'.$item["title_ru"].'</a>';
				$this->path[] = $item;

			}
		}
	}
	function getKeyWords($content) {
		$clearSymb = array(",", ".", '"', "'", "-", ">", "&nbsp;", "&quot;", "(", ")", "/", "\\", "-", "|", ";", ":");
		$content = strtolower(strip_tags($content));
		foreach($clearSymb as $symb) {
			$content = str_replace($symb, "", $content); 
		}
		$arrWord = explode(" ", $content);
		$countsWord = array_count_values($arrWord);
		arsort($countsWord);
		$counterWord = 0;
		$keywords = array();
		foreach($countsWord as $word => $count) {
			if(strlen(trim($word))>5 && strlen(implode(",", array_keys($keywords)).",".$word)<255) {
				$keywords[trim($word)]=$count;
				$counterWord++;
			}
			if(strlen(implode(",", array_keys($keywords)))>255) break;
		}
		return array_keys($keywords);
	}

	//Удаление раздела.	
    function delete($arguments, $redirect=true){
		$parent = 0;
		$id = 0;
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }
		if(isset($arguments['id'])) { $id = (int)$arguments['id']; }

		$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE gallery_id='".$id."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
            return false;
        }
		elseif ($result->RecordCount()>0) {
			$item = $result->FetchRow();

			$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE parent_id='".(int)$item["gallery_id"]."'";
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
				return false;
			}
			elseif ($result->RecordCount()>0) {
				while($itemForDelete = $result->FetchRow()) {
					$arg = $arguments;
					$arg["id"] = $itemForDelete["gallery_id"];
					$this->delete($arg, false);
					$this->dieImg($itemForDelete);
				}
			}

			$sql = "DELETE FROM ".$this->cms->tables["gallery"]." WHERE gallery_id='".$id."'";
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
				return false;
			}
		}
		
		if($redirect!==false) {
	        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array("parent"=>$parent));
	        header('Location: '.$target_url);
			die();
		}
	}
	
		
	//Изменения состояния отображения.
	function switch_state($arguments) {
		$parent = (int) $arguments['parent'];
		$id = (int) $arguments['id'];
		$query = 'UPDATE '.$this->cms->tables['gallery'].' SET enabled=abs(enabled-1) WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("parent"=>$parent));
		header('Location: '.$target_url);
		return $this->index(array());
	}

	//Заливка изображений.
	function upload_img($img, $id, $type=1) {

		$query = 'SELECT * FROM '.$this->cms->tables['gallery'].' WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if ($result && $result->RecordCount() >0 ) {
			$item = $result->FetchRow();
			$this->dieImg($item);
		}

		$items=array(
			'img' => "",
			'img_sh' => "",
			'img_sm' => "",
		);
		if(is_array($img)){ 
			$file = $img;
			$file_info = pathinfo($file['name']);
			$extension = strtolower($file_info["extension"]);
		} else { 
			$file_info = pathinfo($img);
			$extension = strtolower($file_info["extension"]);
		}
		if($type==0) {
			$fileName = "section";
		}
		else {
			$fileName = "photo";
		}
		$fileName .= "_".$id;
		$img_url = 'content/gallery/'.$fileName.'.'.$extension;
		$target = ROOT_HTML_PATH.$img_url;
		if (!move_uploaded_file($file['tmp_name'], $target)) {
			if(!rename($img, $target)){
				$this->cms->int_set_message('top', 'Ошибка при закачивании '.$file['tmp_name'].' -> '.$target, $this->plugin['name'], 'error');
				return $output;
			}
		}
		$this->cms->files->resizeImageMode($target, $this->options["img_w"], $this->options["img_h"], 0);
		@chmod($target, 0777);
		$items['img'] = '/'.$img_url;
		
		if(file_exists(ROOT_HTML_PATH."/img/gallery/watermark.png")){
			$watermark = imagecreatefrompng(ROOT_HTML_PATH."/img/gallery/watermark.png");
			imageAlphaBlending($watermark, false);
			imageSaveAlpha($watermark, true);
			if($extension == 'jpg' || $extension == 'jpeg') $image = imagecreatefromjpeg($target);
			if($extension == 'gif') $image = imagecreatefromgif($target);
			$size = getimagesize($target);
			$this->imagecopymerge_alpha($image, $watermark, $size[0] - imagesx($watermark) - 5, $size[1] - imagesy($watermark) - 5, 0, 0, imagesx($watermark), imagesy($watermark), 50);
			if($extension == 'jpg' || $extension == 'jpeg') imagejpeg($image, $target);
			if($extension == 'gif') imagegif($image, $target);  
			imagedestroy($image);  
			imagedestroy($watermark);  
		}

		$img_url = 'content/gallery/'.$fileName.'_sm.'.$extension;
		$target2 = ROOT_HTML_PATH.$img_url;
		if (!copy($target, $target2)) {
			$this->cms_ai->message_box->add('top', 'Ошибка при копировании '.$target.' -> '.$target2, $this->plugin['name'], 'error');
			return $output;
		}
		$this->cms->files->resizeImageMode($target2, $this->options["img_sm_w"], $this->options["img_sm_h"], 2);
		@chmod($target2, 0777);
		$items['img_sm'] = '/'.$img_url;
		
		$img_url = 'content/gallery/'.$fileName.'_sh.'.$extension;
		$target3 = ROOT_HTML_PATH.$img_url;
		if (!copy($target, $target3)) {
			$this->cms_ai->message_box->add('top', 'Ошибка при копировании '.$target.' -> '.$target3, $this->plugin['name'], 'error');
			return $output;
		}
		$this->cms->files->resizeImageMode($target3, $this->options["img_sh_w"], $this->options["img_sh_h"], 0);
		@chmod($target3, 0777);
		$items['img_sh'] = '/'.$img_url;

		$update = array(
			'img' => "'".addslashes(trim($items['img']))."'",
			'img_sh' => "'".addslashes(trim($items['img_sh']))."'",
			'img_sm' => "'".addslashes(trim($items['img_sm']))."'",
		);
		$update_sql = '';
		$update_sep = '';
		while (list($field, $value) = each($update)) {
			$update_sql .= $update_sep.$field.'='.$value.' ';
			$update_sep = ', ';
		}
		$query = 'UPDATE '.$this->cms->tables['gallery'].' SET '.$update_sql.' WHERE gallery_id='.$id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}

	}


	//............................................................................... Функции алиасов (начало)
	// проверка на уникальность алиаса
	function check_exist_alias($parent_id, $alias, $record_id=0) {
		$ret = true;
		$sql = "SELECT COUNT(*) as counts FROM ".$this->cms->tables["gallery"]." WHERE parent_id='".$parent_id."' AND alias='".addslashes(trim($alias))."'";
		if (!empty($record_id)) {
			$sql .= " AND gallery_id<>'".$record_id."'";
		}
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return $ret;
		}
		$item = $result->FetchRow();
		if($item["counts"]==0) {
			$ret = false;
		}
		return $ret;
	}
	// проверка на валидность алиаса
	function is_valid_alias($alias){
		if (preg_match('/^[a-zA-Z0-9_\-]{1,32}$/', $alias) ) {
			return true;
		}
		else {
			return false;
		}
    }
	// возврат полного пути к элементу
	function get_relative_url($parent_id, $alias) {
		$ret = array(
			"relative_url" => "",
		);

		$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE gallery_id='".$parent_id."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return $ret;
		}
		if ($result->RecordCount() > 0) {
			$item = $result->FetchRow();
			$ret["relative_url"] = (!empty($item["relative_url"])?$item["relative_url"]:"/").$alias."/";
		}
		else {
			$ret["relative_url"] = "/".$alias."/";
		}

		return $ret;
	}
	// обновление полных путей для всех детей элемента
	function update_child_relative($item, $relative) {
		$sql = "SELECT gallery_id, relative_url FROM ".$this->cms->tables["gallery"]." WHERE relative_url LIKE '".addslashes($item["relative_url"])."%' AND gallery_id<>'".$item["gallery_id"]."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return false;
		}
		if ($result->RecordCount() > 0) {
//			echo "<pre>"; print_r($relative); echo "</pre>";
//			echo "<pre>"; print_r($item); echo "</pre>";
			while ($row = $result->FetchRow()) {
				$new_relative_url = substr($relative["relative_url"], 0, -1).substr($row["relative_url"], strlen($item["relative_url"])-1);
				$sql = "UPDATE ".$this->cms->tables["gallery"]." SET relative_url='".$new_relative_url."' WHERE gallery_id='".$row["gallery_id"]."'";
				$resultUpdate = $this->dbc->Execute($sql);
				if (!$resultUpdate) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
					return false;
				}
				echo $sql."<hr>";
			}
		}
//		die();
	}
	//............................................................................... Функции алиасов (конец)

//	Возврат элементов определенного типа
	function getCountItemsByType($parent, $type=0) {
		$ret = 0;
		$sql = "SELECT COUNT(*) as counts FROM ".$this->cms->tables["gallery"]." WHERE parent_id='".$parent."' AND type='".$type."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return $ret;
		}
		$item = $result->FetchRow();
		$ret = $item["counts"];
		return $ret;
	}

	//Инициализирующие и интерфейсные функции.
	
	function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		require_once(cms_LIB_PATH.'cms_structure.class.php');
		require_once(cms_LIB_PATH.'images.lib.php');
		$this->cms->init_dbc();
		$this->dbc = &$this->cms->dbc;
		$this->initTables();

		$this->setDefaultVar();

		$this->cms->pathModule[] = '<a href="index.php?'.$this->cms->request_vars['group'].'=modules">Програмные модули</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Галерея</a>';
		
		$this->langs = $this->cms->getAllLanguage();
		$this->defaultLang = $this->cms->defaultLang;
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

        if (method_exists($this, $command)) {
            $result = $this->$command($arguments);
	        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
            return $result;
        }

		switch($command) {
			default: $result = $this->index($arguments); break;
			case 'words': $result = $this->words($arguments); break;
            case 'index': $result = $this->index($arguments); break;
        }
        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
        return $result;
    }
	
	function getActiveTabs(&$tabs, $activeTab) {
		if(array_key_exists($activeTab, $tabs)) return $activeTab;
		switch($activeTab) {
			case "words": $ret = "words";
                        break;
			default: 
				$ret = "sections";
				break;
		}
		return $ret;
	}
	
	function print_tabs($active_tab='sections', $id = 0) {

         $tabs  = array(
			'sections' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array(id=>$id['id'],parent=>$id['parent'])).'">Галереи</a>',
			'options'  => '<a href="'.$this->cms->format_url($this->plugin['name'], 'options', array()).'">Настройки</a>',
            'words'    => '<a href="'.$this->cms->format_url($this->plugin['name'], 'words', array(id=>$id['id'],parent=>$id['parent'])).'">Теги</a>',
		);
		$active_tab = $this->getActiveTabs($tabs, $active_tab);
		setcookie('pm_simtab', $active_tab, time()+3600*24, '');

		$output = $this->cms->int_add_tabs($tabs, $active_tab);
		return $output;
	}

	function setDefaultVar() {
		$this->installDefaultOptions();

		$sql = "SELECT * FROM ".$this->cms->tables["gallery_options"]." ORDER BY name DESC";
		$result = $this->dbc->Execute($sql);

		while($row=$result->FetchRow()) {
			$this->options[$row["name"]] = $row["value"];
		}
	}
	function options($arguments) {
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'options', array()).'">Настройки</a>';
		$this->cms->intSetHierarchy();

		$output = $this->cms->int_set_h1("Изменение настроек модуля");

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'options');
		foreach($this->options as $name => $val) {
			if($val == "sep") {
				$fob->add_html('sep', $this->cms->int_add_h2(''));
			}
			else {
				$title = $this->getNameOption($name);
				if($title) {
					$fob->add_text(true, $name, $val, $title, '', 'fob-text', '', 'text', '', '');
				}
			}
		}
		$fob->add_button('submit', 'submit', 'Сохранить', '');
		
		$fob1 = $this->init_fob('fob1', $this->cms->admin_url);
		$fob1->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob1->add_hidden('$'.$this->cms->request_vars['command'], 'options');
		$fob1->add_file('img', 'Водяной знак(Watermark) на изображения');
		$fob1->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		$output .= "<p><p>В качестве водяных знаков подходят изображения в формате png(рекомендуем png-24).";
		if(file_exists(ROOT_HTML_PATH."img/gallery/watermark.png"))
			$output	.= "<br><p><img src=\"/img/gallery/watermark.png\"/>";
		$output .= $fob1->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			foreach($values as $name => $value) {
				$sql = "UPDATE ".$this->cms->tables['gallery_options']." SET value='".(int)$value."' WHERE name='".$name."'";
				$result = $this->dbc->Execute($sql);
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'options', array());
			header('Location: '.$target_url);
			return $output;
		}
		if ($fob1->is_submited()  && $fob1->is_valid_submit() ) {
			$files = $fob1->get_submited_files();
			$target = ROOT_HTML_PATH."img/gallery/watermark.png";
			if (!empty($files['img']['name'])) {
				if($files['img']['type'] == "image/png"){
					move_uploaded_file($files['img']['tmp_name'], $target);
				} else {
					trigger_error($this->dbc->ErrorMsg().'Загруженный файл - не png.', E_USER_ERROR);
				}
			}

			
			$target_url = $this->cms->format_url($this->plugin['name'], 'options', array());
			header('Location: '.$target_url);
			return $output;
		}



		return $output;
	}
	function installDefaultOptions() {
		foreach($this->options as $key => $val) {
			if($val != "sep") {
				$this->insertOption($key, $val);
			}
		}
	}
	function insertOption($name, $value) {
		$sql = "SELECT id FROM ".$this->cms->tables["gallery_options"]." WHERE name='".$name."'";
		$result = $this->dbc->Execute($sql);
		/*if ($result->RecordCount()==0) {
			$sql = "INSERT INTO ".$this->cms->tables["gallery_options"]." SET name='".$name."', value='".$value."'";
			$this->dbc->Execute($sql);
		}*/
	}
	function getValueOption($name, $field="value") {
		$sql = "SELECT ".$field." FROM ".$this->cms->tables["gallery_options"]." WHERE name='".$name."'";
		$result = $this->dbc->Execute($sql);
		$item = $result->FetchRow();
		return $item['value'];
	}
	function getNameOption($name) {
		$ret = 'имя переменной не найдено';
		if(!empty($this->options_titles[$name])) {
			$ret = $this->options_titles[$name];
		}
		return $ret;
	}

	function move($arguments) {
		$ord = $arguments['ord'];
		$id = $arguments['id'];
		$query = 'UPDATE '.$this->cms->tables['gallery'].' SET ord = "'.$ord.'" WHERE gallery_id="'.$id.'"';
		$result = $this->dbc->Execute($query);	
	}

	function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        	$opacity=$pct;
	        // getting the watermark width
        	$w = imagesx($src_im);
	        // getting the watermark height
        	$h = imagesy($src_im);
         
	        // creating a cut resource
        	$cut = imagecreatetruecolor($src_w, $src_h);
	        // copying that section of the background to the cut
        	imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
	        // inverting the opacity
        	$opacity = 100 - $opacity;
         
	        // placing the watermark now
        	imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
	        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
	}

	function get_dirs($dir, &$files){ 
	    if(substr($dir,-1) !== '/'){$dir .= '/';} 
	    if ($handle = opendir($dir)){ 
	        while (false !== ($file = readdir($handle))){ 
	            if ($file != "." && $file != ".."){ 
	                clearstatcache(); 
	                if (filetype($dir.$file) === 'dir') $this->get_dirs($dir . $file, $files);
				else $files[] = $dir.$file;
	            } 
	        } 
	        closedir($handle); 
	    } 
	    return 1; 
	}

 function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   }
 }


	function words($arguments) {
		$output = '';
		$parent = 0;
		$id = 0;
		if(isset($arguments['parent'])) { $parent = (int)$arguments['parent']; }
		if(isset($arguments['id'])) { $id = (int)$arguments['id']; }

		$this->setPath($parent);
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'edit', $arguments).'">Изменение</a>';
		$this->cms->intSetHierarchy();
		
		$query = 'SELECT * FROM '.$this->cms->tables['gallery'].' WHERE gallery_id='.$id;
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

		$this->cms->int_set_hierarchy($id);

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		
		//echo $parent;

		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'words');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[id]', $id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[parent]', $parent);

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
			
						//echo "<pre>"; print_r($values); die();

			if(!empty($_POST["word"]) && count($_POST["word"])) {
				foreach($_POST["word"] as $key => $val) {
					if((int)$val>0) {
						$arrWordId[] = (int)$val;
					}
				}
				if(count($arrWordId)>0) {
					$sql = "UPDATE ".$this->cms->tables['gallery']." SET words='|".implode("|", $arrWordId)."|' WHERE gallery_id='".$id."'";
					$result = $this->dbc->Execute($sql);
					if (!$result) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
						return false;
					}
				}
			}
			else {
				$sql = "UPDATE ".$this->cms->tables['gallery']." SET words='' WHERE gallery_id='".$id."'";
				$result = $this->dbc->Execute($sql);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
					return false;
				}
			}
			
			$target_url = $this->cms->format_url($this->plugin['name'], 'words', array('id'=>$id, 'parent'=>$parent));
			//$target_url = $this->cms->format_url("sa", 'index', array());
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



}
?>