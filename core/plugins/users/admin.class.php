<?php
class plugin_admin_interface extends cms_plugin_admin {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $fob;
	var $cms_ai = '';
	var $structure_depth_limit = 3;

	var $settedShowAccessLevel = false;
	var $nowShowAccessLevel = 0;

	function index($arguments) {
		$variables = array();
		$output = '';
		$variables['tabs'] = $this->_print_tabs('users', 0);

		$query = 'SELECT count(uid) as total FROM '.$this->cms->tables['users'].' WHERE 1';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$users = $result->FetchRow();

		$variables['output'] .= $this->showSelectAccessLevel($arguments);

		$variables['output'] .= $this->cms->int_add_h1('Зарегистрированные пользователи');
		$variables['output'] .= $this->_print_users($arguments);

		$output .= $this->cms->parse_template($variables, 'workarea');
		return $output;
	}
	function showSelectAccessLevel($arguments) {
		$output = '';
		$output .= '<form name="sendAL" action=""><select name="arg[al]" onchange="document.location.href=\''.$this->cms->format_url($this->plugin['name'], 'index', array()).'&arg[al]=\'+this.value;">';
		foreach($GLOBALS['ACCESS_LEVELS'] as $value => $title) {
			if ($value<=$this->cms->user_data['access_level']) {
				if($this->settedShowAccessLevel == false) {
					$this->nowShowAccessLevel = $value;
					$this->settedShowAccessLevel = true;
				}
				$output .= '<option value="'.$value.'"'.(($this->nowShowAccessLevel==$value)?" selected":"").'>'.$title.' ('.$this->getCountUserFromAccessLevel($value).')'.'</option>';
			}
		}
		$output .= '</select></form>';
		return $output;
	}
	function getCountUserFromAccessLevel($al) {
		$sql = "SELECT COUNT(*) as counts FROM ".$this->cms->tables["users"]." WHERE access_level='".$al."'";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
			return false;
		}
		$item = $result->FetchRow();
		return $item["counts"];
	}
	function _print_users($arguments) {
		$output = '';
		$conditions = array();
		if ($this->nowShowAccessLevel!=-1) {
			$conditions[] = 'access_level="'.$this->nowShowAccessLevel.'"';
		}
		if ($this->cms->user_data['cms_permissions'] == AP_FULL) {
			//$cond = '';
		}
		else {
			$allowed_groups	  = $this->cms->_get_allowed_groups($this->cms->user_data['uid']);
			$conditions2 = array();
			foreach($allowed_groups as $gid => $allowed) {
				$conditions2[] = 'u.member_of_groups LIKE "%|'.$gid.'|%"';
			}
			if (count($conditions2)>0) {
				$conditions[] = '('.implode(' OR ', $conditions2).')';
			}
		}
		if (count($conditions)>0) {
			$cond = ''.implode(' AND ', $conditions).'';
		} 
		else {
			$cond = '1';
		}

		$query = 'SELECT * FROM '.$this->cms->tables['users'].' WHERE access_level<='.$this->cms->user_data['access_level'].' AND '.$cond.' ORDER BY access_level DESC, valid_account DESC, name ASC, login ASC';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return false;
		}
		elseif ($result->RecordCount()>0) {
			$output .= '<div class="admin-table2"><div class="width-fix"><table>';
			$p_access_level = -1;
			while($block = $result->FetchRow()) {
				$arg = array("uid"=>$block["uid"], "al"=>$this->nowShowAccessLevel);
				if ($block['access_level']!=$p_access_level) {
					$p_access_level = $block['access_level'];
					$output .= '<tr>';
					$output .= '<td class="title-h">'.$GLOBALS['ACCESS_LEVELS'][$p_access_level].'</td>';
					$output .= '<td class="act-h">Логин</td>';
					$output .= '<td class="act-h">&nbsp;</td>';
//					$output .= '<td class="act-h" nowrap>Доступ<br>к CMS</td>';
					if ($this->cms->restrict_site_access) {
//						$output .= '<td class="act-h" nowrap>Доступ<br>к закрытым разделам</td>';
					}
					$output .= '<td class="act-h last" colspan="2">&nbsp;</td>';
					$output .= '</tr>';
				}
				$access_level = $block['access_level'];
				$edit_url = $this->cms->format_url($this->plugin['name'], 'edit', $arg);
				$actions = array(
					  'edit'	=>  '<a href="'.$edit_url.'">Редактировать</a>',
					  'delete'  =>  '<a onClick="return confirm(\''.$this->translate('Удалить учетную запись?').'\')" href="'.$this->cms->format_url($this->plugin['name'], 'delete', $arg).'">Удалить</a>'
				);
				$output .= '<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">';
				$contact = array();
				if (!empty($block['email'])) {
					$contact[] = '<a href="mailto:'.stripslashes($block['email']).'">'.stripslashes($block['email']).'</a>';
				}
				if (!empty($block['icq'])) {
					$contact[] = 'ICQ: '.stripslashes($block['icq']);
				}
				if (!empty($block['phone'])) {
					$contact[] = 'Тел.: '.stripslashes($block['phone']);
				}
				if (!empty($block['skype'])){
					$contact[] = 'Skype: '.stripslashes($block['skype']);
				}
				$p_access_level > 50 ? $user_type = 'adm' : $user_type = 'user';
				if ($block['valid_account']==1) {
					$v_icon = '<img src="/admin/img/users_'.$user_type.'_val.gif" width="16" height="16" class="ico" alt="Администратор" title="Учетная запись активна">';
				} else {
					$v_icon = '<img src="/admin/img/users_'.$user_type.'_inval.gif" width="16" height="16" class="ico" alt="Пользователь" title="Учетная запись не активна">';
				}
				$output .= '<td class="title" nowrap><a href="'.$edit_url.'">'.$v_icon.stripslashes($block['name']).'</a></td>';
				$output .= '<td class="act">'.stripslashes($block['login']).'</td>';
				$access_cms  = $GLOBALS['ACCESS_CMS'][$access_level];
				$access_site = $GLOBALS['ACCESS_SITE'][$access_level];
				if ($access_cms == AP_PARTIAL) {
					$configure_access_cms = '<a href="'.$this->cms->format_url($this->plugin['name'], 'cms_access', $arg).'" style="color: '.$GLOBALS['AP_COLORS'][$access_cms].';">'.$GLOBALS['ACCESS_PERMISSIONS'][$access_cms].'</a>';
				} else {
					$configure_access_cms = $GLOBALS['ACCESS_PERMISSIONS'][$access_cms];
				}
				if ($access_site == AP_PARTIAL) {
					$configure_access_site = '<a href="'.$this->cms->format_url($this->plugin['name'], 'site_access', $arg).'" style="color: '.$GLOBALS['AP_COLORS'][$access_site].';">'.$GLOBALS['ACCESS_PERMISSIONS'][$access_site].'</a>';
				} else {
					$configure_access_site = $GLOBALS['ACCESS_PERMISSIONS'][$access_site];
				}
//				$output .= '<td class="act" nowrap style="color: '.$GLOBALS['AP_COLORS'][$access_cms].';">'.$configure_access_cms.'</td>';
				if ($this->cms->restrict_site_access) {
//					$output .= '<td class="act" nowrap style="color: '.$GLOBALS['AP_COLORS'][$access_site].';">'.$configure_access_site.'</td>';
				}
				$output .= '<td class="act" nowrap>'.implode("<br>", $contact).'</td>';
				$output .= '<td class="act">'.$actions['edit'].'</td>';
				$output .= '<td class="act last">'.$actions['delete'].'</td>';
				$output .= '</tr>';
			}
			$output .= '</table></div></div>';
		}
		return $output;
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////


	function _is_valid_login($login, $uid = 0) {
		$query = "SELECT * FROM ".$this->cms->tables['users']." WHERE login='".addslashes($login)."' and uid<>".$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
		   $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return true;
		} 
		elseif ($result->RecordCount()>0) {
			return false;
		}
	}

	function _get_lower_access_levels() {
		$access_levels = $GLOBALS['ACCESS_LEVELS'];
		foreach($access_levels as $level => $label) {
			if ($level>$this->cms->user_data['access_level']) {
				unset($access_levels[$level]);
			}
		}
		return $access_levels;
	}

	function _get_structure($depth_limit = 3) {
		$query = 'SELECT s_id, relative_url, title FROM '.$this->cms->tables['structure'].' WHERE enabled=1 and level<'.($depth_limit+1).' ORDER BY left_key';
		$result = $this->dbc->Execute($query);
		$structure = array();
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		else {
			while ($row = $result->FetchRow()) {
				$row['path'] = str_replace('/', ' / ', $row['relative_url']);
				$structure[] = $row;
			}
		}
		return $structure;
	}

	function _get_structure2($parent=0) {
		$query = 'SELECT s_id, alias, title FROM '.$this->cms->tables['structure'].' WHERE parent='.$parent.' ORDER BY left_key';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		else {
			while ($row = $result->FetchRow()){
				if (strlen($row['title'])>25) {
					$title = substr($row['title'], 0, 25).'...';
				} else {
					$title = $row['title'];
				}
				$row['path'] = $title;
				$structure[] = $row;
			}
		}
		return $structure;
	}

	function _print_tabs($active_tab='users', $s_id=0) {
//		echo "<prE>"; print_r($_REQUEST); echo "</pre>";
//		echo "<prE>"; print_r($_POST); echo "</pre>";
		if(isset($_REQUEST["arg"]["al"])) {
			$this->nowShowAccessLevel = (int)$_REQUEST["arg"]["al"];
			$this->settedShowAccessLevel = true;
		}
		else {
			foreach($GLOBALS['ACCESS_LEVELS'] as $value => $title) {
				if ($value<=$this->cms->user_data['access_level']) {
					if($this->settedShowAccessLevel == false) {
						$this->nowShowAccessLevel = $value;
						$this->settedShowAccessLevel = true;
					}
				}
			}
		}
		setcookie ('pm_simtab', $active_tab,time()+3600*24, '');
		$arguments = array('s_id'=>$s_id);
		$tabs  = array(
			'users'	 => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel)).'">Пользователи и группы</a>',
			'add'	   => '<a href="'.$this->cms->format_url($this->plugin['name'], 'add', array("al"=>$this->nowShowAccessLevel)).'">[+] добавить нового пользователя</a>',
//			'add_group' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'group_add', array()).'">[+] добавить новую группу</a>',
		);
		$output = $this->cms->int_add_tabs($tabs, $active_tab);
		return $output;
	}


	function _get_groups_list(){
		$groups = array();
		if ($this->cms->user_data['cms_permissions'] == AP_FULL) {
			$cond = '';
		} else {
			$allowed_groups	  = $this->cms->_get_allowed_groups($this->cms->user_data['uid']);
			$allowed_groups[0] = 0;
			$cond = 'and uid IN ('.implode(',', array_keys($allowed_groups)).')';
		}
		$query = 'SELECT * FROM '.$this->cms->tables['users'].' where 1 '.$cond.' ORDER BY name asc';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		} else if ($result->RecordCount() == 0) {
			return $groups;
		}
		while($block = $result->FetchRow()) {
			$groups[$block['uid']] = $block['name'];
		}
		return $groups;
	}


	function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		require_once(cms_LIB_PATH.'validate.php');
		$this->dbc = &$this->cms->dbc;
	}



	function add($arguments) {
		$output = '';
		$variables['tabs'] = $this->_print_tabs('add', 0);

		$fob = $this->init_fob('fob-add', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'add');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[al]', $this->nowShowAccessLevel);
		$access_levels = $this->_get_lower_access_levels();
		$fob->add_select(false, 'access_level', $access_levels,  'Уровень доступа', '', $this->nowShowAccessLevel, '', '', '', 'select', '', '');
		$fob->add_text(true, 'login', '', 'Логин', '', '', '', '', '', '');
		$fob->add_password(true, 'password', '', 'Пароль', '', '', '', 'password', 'password', '');
		$fob->add_password(true, 'password_conf', '', 'Подтверждение пароля', '', '', '', 'password', 'password', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Информаця о пользователе:'));
		$fob->add_text(true, 'name', '', 'Ф.И.О', '', '', '', 'text', '', '');
		$fob->add_text(false, 'last_name', '', 'Никнейм', '', '', '', 'text', '', '');
		//$fob->add_text(false, 'patr', '', 'Отчество', '', '', '', 'text', '', '');
		$fob->add_date('birthday',  Date('d.m.Y', time()), 'Дата рождения');
		$fob->add_radio(false, 'sex', '0||1||2', 'Пол', 'неизвестен||мужской||женский', '', 0, '', '', 'radio', '', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Контактная информация:'));
		$fob->add_text(false, 'country', '', 'Страна', '', '', '', 'text', '', '');
		$fob->add_text(false, 'city', '', 'Город', '', '', '', 'text', '', '');
		$fob->add_textarea(false, 'address', '', 'Адрес', '', 30, 3, 'fob-textarea');
		$fob->add_text(true, 'email', '', 'E-mail', '', '', '', 'text', 'email', '');
		$fob->add_text(false, 'phone', '', 'Телефон', '', '', '', 'text', '', '');
		$fob->add_text(false, 'icq', '', 'ICQ', '', '', '', 'text', '', '');
		$fob->add_text(false, 'skype', '', 'Skype', '', '', '', 'text', '', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Дополнительная информация:'));
		$fob->add_text(false, 'company', '', 'Компания', '', '', '', 'text', '', '');
		$fob->add_text(false, 'position', '', 'Должность', '', '', '', 'text', '', '');
		$fob->add_file('avatar', 'Аватар', '', '', '', '', '', '');
		$fob->add_html('separator', '&nbsp;', 'html_code');
		$fob->add_checkbox(false, 'valid_account', 'yes', 'Активная учетная запись', '', '', 0, '', '', 'checkbox', '', '');

		$fob->add_button('submit', 'submit', 'Добавить', '', '', '', '', 'button', '', '');

		$variables['output'] = $this->cms->int_add_h1('Новый пользователь');
		$variables['output'] .= $fob->display('return');
		$output .= $this->cms->parse_template($variables, 'workarea');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$files = $fob->get_submited_files();

			if ( !$this->_is_valid_login($values['login']) ) {
				$this->cms->int_set_message('top', 'Этот логин уже используется. Пожалуйста, выберите другой.', 'Ошибка', 'error');
				return $output;
			}
			if ($values['password'] != $values['password_conf']) {
				$this->cms->int_set_message('top', 'Введенные вами пароли не совпадают.', 'Ошибка', 'error');
				return $output;
			}
			if (intval($values['access_level'])>$this->cms->user_data['access_level']){
				$this->cms->int_set_message('top', 'Вы не можете добавлять пользователей с уровнем доступа выше Вашего.', $this->plugin['title'], 'error');
				return $output;
			}

			$avatar 		= $files['avatar']['tmp_name'];
			$avatar_size 	= $files['avatar']['size'];
			$avatar_orgname	= $files['avatar']['name'];
			if (!empty($avatar) && eregi('(.*)\.(gif|jpg|png)', $avatar_orgname, $avatar_fname)) {
				$avatar_url = 'content/avatars/'.addslashes($values['login']).'.'.$avatar_fname[2];
				move_uploaded_file($avatar, ROOT_HTML_PATH.$avatar_url);
				chmod(ROOT_HTML_PATH.$avatar_url, 0777);  
				$avatar_size2 = getimagesize(ROOT_HTML_PATH.$avatar_url);
				if(($avatar_size2[0] > 100) || ($avatar_size2[1] > 100)) {
					$this->cms->files->resizeImageMode(ROOT_HTML_PATH.$avatar_url, 100, 100, 0);
				}
				$avatar_url = "/".$avatar_url;
			}
			else {
				$avatar_url = '';	
			}

			$dateArr = explode('.', $values['birthday']);
			$insert_user = array(
				'login'				=> "'".addslashes($values['login'])."'",
				'password'			=> "'".addslashes(md5(trim($values['password'])))."'",

				'name'				=> "'".addslashes($values['name'])."'",
				'last_name'			=> "'".addslashes($values['last_name'])."'",
				'patr'				=> "'".addslashes($values['patr'])."'",
				'birthday' => "'".$dateArr[2]."-".$dateArr[1]."-".$dateArr[0]."'",
				'sex'				=> (int) $values['sex'],

				'country'			=> "'".addslashes($values['country'])."'",
				'city'				=> "'".addslashes($values['city'])."'",
				'address'			=> "'".addslashes($values['address'])."'",
				'email'				=> "'".addslashes($values['email'])."'",
				'phone'				=> "'".addslashes($values['phone'])."'",
				'icq'				=> "'".addslashes($values['icq'])."'",
				'skype'				=> "'".addslashes($values['skype'])."'",

				'company'			=> "'".addslashes($values['company'])."'",
				'position'			=> "'".addslashes($values['position'])."'",
				'avatar'			=> "'".$avatar_url."'",

				'access_level'		=> (int)$values['access_level'],
				'reg_date'			=> 'now()',

				'valid_account'		=> 0+!empty( $values['valid_account']),
			);
			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
			while (list($field, $value) = each($insert_user)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$query  = 'INSERT INTO '.$this->cms->tables['users'].' ('.$insert_fields.') VALUES('.$insert_values.')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$uid = $this->dbc->insert_id();

			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			header('Location: '.$target_url);
			die();
		}
		return $output;
	}



	function edit($arguments) {
		$output = '';
		$variables['tabs'] = $this->_print_tabs('users', 0);

		$uid = (int) $arguments['uid'];
		$query = "SELECT * FROM ".$this->cms->tables['users']." WHERE uid=".$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		} else {
			$item = $result->FetchRow();
		}
		if ($item['access_level']>$this->cms->user_data['access_level'] && $item['uid']!=$this->cms->user_data['uid']){
			$this->cms->int_set_message('bottom', 'Вы не можете редактировать пользователя с уровнем доступа равным выше вашему.', $this->plugin['title'], 'error');
			return $output;
		}
		
		if (isset($arguments['avatar']) && ($arguments['avatar'] == 'delete')) {
			$query2 = "UPDATE ".$this->cms->tables['user_data']." SET avatar = '' WHERE uid=".$uid;
			$result2 = $this->dbc->Execute($query2);
			unlink(ROOT_HTML_PATH.$item['avatar']);
			unset($item['avatar']);
		}
		$fob = $this->init_fob('fob-user', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'edit');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[uid]', $uid);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[al]', $this->nowShowAccessLevel);
		$access_levels = $this->_get_lower_access_levels();
		$fob->add_select(false, 'access_level', $access_levels, 'Уровень доступа', '', $item['access_level'], '', '', '', 'select', '', '');
		$fob->add_text(true, 'login', $item['login'], 'Логин', '', '', '', '', '', '');
		$fob->add_password(false, 'password', '', 'Пароль', '', '', '', 'password', 'password', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Информаця о пользователе:'));
		$fob->add_text(true, 'name', $item['name'], 'Ф.И.О.', '', '', '', 'text', '', '');
		$fob->add_text(false, 'last_name', $item['last_name'], 'Никнейм', '', '', '', 'text', '', '');
		//$fob->add_text(false, 'patr', $item['patr'], 'Отчество', '', '', '', 'text', '', '');
		$fob->add_date('birthday', Date('d.m.Y', strtotime($item['birthday'])), 'Дата рождения');
		$fob->add_radio(false, 'sex', '0||1||2', 'Пол', 'неизвестен||мужской||женский', '', $item['sex'], '', '', 'radio', '', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Контактная информация:'));
		$fob->add_text(false, 'country', $item['country'], 'Страна', '', '', '', 'text', '', '');
		$fob->add_text(false, 'city', $item['city'], 'Город', '', '', '', 'text', '', '');
		$fob->add_textarea(false, 'address', $item['address'], 'Адрес', '', 30, 3, 'fob-textarea');
		$fob->add_text(true, 'email', $item['email'], 'E-mail', '', '', '', 'text', 'email', '');
		$fob->add_text(false, 'phone', $item['phone'], 'Телефон', '', '', '', 'text', '', '');
		$fob->add_text(false, 'icq', $item['icq'], 'ICQ', '', '', '', 'text', '', '');
		$fob->add_text(false, 'skype', $item['skype'], 'Skype', '', '', '', 'text', '', '');

		$fob->add_html('separator', $this->cms->int_add_h2('Дополнительная информация:'));
		$fob->add_text(false, 'company', $item['company'], 'Компания', '', '', '', 'text', '', '');
		$fob->add_text(false, 'position', $item['position'], 'Должность', '', '', '', 'text', '', '');
		$fob->add_file('avatar', 'Аватар', '', '', '', '', '', '');
		if ($item['avatar'] != '') {
			$arguments = array('uid' => $uid, 'avatar' => 'delete');
			$img = '<img src="'.ROOT_URL.$item['avatar'].'" border="0"><br><a href="'.$this->cms->format_url($this->plugin['name'], 'edit', $arguments ).'">Удалить</a>';
			$fob->add_html('img', $img, 'input');		
		}								  
		$fob->add_html('separator', '&nbsp;', 'html_code');
		$fob->add_checkbox(false,  'valid_account',  'yes', 'Активная учетная запись', '', '', $item['valid_account'], '', '', 'checkbox', '', '');

		$fob->add_button('submit', 'submit', 'Сохранить', '', '', '', '', 'button', '', '');

		$variables['output'] = $this->cms->int_add_h1('Редактирование данных пользователя');
		$variables['output'] .= $fob->display('return');
		$output .= $this->cms->parse_template($variables, 'workarea');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$avatar_file = $fob->get_submited_files();

			$avatar 		= $avatar_file['avatar']['tmp_name'];
			$avatar_size 	= $avatar_file['avatar']['size'];
			$avatar_orgname	= $avatar_file['avatar']['name'];
			if (!empty($avatar) && eregi('(.*)\.(gif|jpg|png)', $avatar_orgname, $avatar_fname)) {
				$avatar_url = 'content/avatars/'.addslashes($values['login']).'.'.$avatar_fname[2];
				move_uploaded_file($avatar, ROOT_HTML_PATH.$avatar_url);
				chmod(ROOT_HTML_PATH.$avatar_url, 0777); 
				$avatar_size2 = getimagesize(ROOT_HTML_PATH.$avatar_url);
				if(($avatar_size2[0] > 100) || ($avatar_size2[1] > 100)) {
					$this->cms->files->resizeImageMode(ROOT_HTML_PATH.$avatar_url, 100, 100, 0);
				}
			}
			else {
				$avatar_url = $item['avatar'];
			}

			if ( !$this->_is_valid_login($values['login'], $uid) ) {
				$this->cms->int_set_message('top', 'Этот логин уже используется. Пожалуйста, выберите другой.', 'Ошибка', 'error');
				return $output;
			}
			$dateArr = explode('.', $values['birthday']);
			$insert_user = array(
				'login'			   => "'".addslashes($values['login'])."'",

				'name'				=> "'".addslashes($values['name'])."'",
				'last_name'			=> "'".addslashes($values['last_name'])."'",
				'patr'			=> "'".addslashes($values['patr'])."'",
				'birthday' => "'".$dateArr[2]."-".$dateArr[1]."-".$dateArr[0]."'",
				'sex'				=> (int) $values['sex'],

				'country'				=> "'".addslashes($values['country'])."'",
				'city'				=> "'".addslashes($values['city'])."'",
				'address'			=> "'".addslashes($values['address'])."'",
				'email'			   => "'".addslashes($values['email'])."'",
				'phone'				=> "'".addslashes($values['phone'])."'",
				'icq'				=> "'".addslashes($values['icq'])."'",
				'skype'				=> "'".addslashes($values['skype'])."'",

				'company'			=> "'".addslashes($values['company'])."'",
				'position'			=> "'".addslashes($values['position'])."'",
				'avatar'			=> "'".$avatar_url."'",

				'access_level'		=> (int)$values['access_level'],
				'valid_account'	   => 0+!empty( $values['valid_account']),
			);
			if (!empty($values['password'])) {
				$insert_user['password'] = "'".addslashes(md5(trim($values['password'])))."'";
			}
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($insert_user)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$update_query = 'UPDATE '.$this->cms->tables['users'].' SET '.$update_sql.' WHERE uid='.$uid;
			$update_result = $this->dbc->query($update_query);
			if (!$update_result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			header('Location: '.$target_url);
		}
		return $output;
	}

	function delete($arguments) {
		$uid = (int) $arguments['uid'];
		$al = (int) $arguments['al'];
		$query = 'DELETE FROM '.$this->cms->tables['users'].' WHERE uid='.$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$al));
		header('Location: '.$target_url);
		die();
	}

	function cms_access($arguments) {
		$variables['tabs'] = $this->_print_tabs('users', 0);
		$output	 = $this->cms->int_add_h1('Редактирование доступа к CMS');
		$uid		= (int) $arguments['uid'];
		$site_id	= $arguments['site_id'];
		$query = "SELECT * FROM ".$this->cms->tables['users']." WHERE uid=".$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$item = $result->FetchRow();
		if ($item['access_level']>$this->cms->user_data['access_level'] && $item['uid']!=$this->cms->user_data['uid']){
			$this->cms->int_set_message('bottom', 'Вы не можете редактировать пользователя с уровнем доступа равным вашему или выше.', $this->plugin['title'], 'error');
			return $output;
		}
		$groups = array();
		$groups_index = array();
		if (!empty($item['member_of_groups'])) {
			$tmp = explode('|', $item['member_of_groups']);
			foreach($tmp as $gid) {
				if (empty($gid)) {
					continue;
				}
				$query = "SELECT uid, name FROM ".$this->cms->tables['users']." WHERE 1 and uid=".$gid;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				$group = $result->FetchRow();
				$group['plugins_access']	= $this->cms->_get_plugins_access($gid, false);
				$group['structure_access']  = $this->cms->_get_structure_access($gid, false);
				$group['groups_access']	 = $this->cms->_get_groups_access($gid, false);
				$groups[$gid] = $group;
			}
		}
		//echo "<br><pre>";print_r($groups);die("<br><br>@".__FILE__.":".__LINE__);
		$groups_index	   = array_keys($groups);
		$total_groups	   = count($groups);
		$plugins_access	 = $this->cms->_get_plugins_access($uid, false);
		$structure_access   = $this->cms->_get_structure_access($uid, false);
		$groups_access	  = $this->cms->_get_groups_access($uid, false);


		if ($total_groups>0) {
			$plugins_access_summary	 = $this->cms->_get_plugins_access($uid, true);
			$structure_access_summary   = $this->cms->_get_structure_access($uid, true);
			$groups_access_summary	  = $this->cms->_get_groups_access($uid, true);
		} else {
			$plugins_access_summary	 = $plugins_access;
			$structure_access_summary   = $structure_access;
			$groups_access_summary	  = $groups_access;
		}
		//$access_granted = array();
		//$access_granted = explode('|', $item['cms_plugins_access']);

		/************************************************************/
		/************************************************************/
		/* plugins table */
		$table  = '<table border=0 cellspacing=0 cellpadding=3 class=printable>';
		$table .= '<tr>';
		$table .= '<th width="200">Модуль</th>';
		$table .= '<th width="200">Действие</th>';
		foreach($groups_index as $gid) {
			$table .= '<th width="50">'.$groups[$gid]['name'].'</th>';
		}
		reset($groups_index);
		$table .= '<th width="50">Разрешить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(1, \'access\');permissions_hide(1, \'access\');">все</a>)</th>';
		$table .= '<th width="50">Частичный доступ</th>';
		$table .= '<th width="50">Запретить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(0, \'access\');permissions_hide(0, \'access\');">все</a>)</th>';
		if ($total_groups>-1) {
			$table .= '<th width="50">Итог</th>';
		}
		$table .= '</tr>';
		reset($this->cms->plugins);
		$block_permissions = array(
				   'block_add'	  => 'Добавление блока',
				   'block_delete'   => 'Удаление блока',
				   'block_move'	 => 'Перемещение блока',
				   'block_switch'   => 'Включение и выключение блока',
				   'block_props'	=> 'Свойства блока',
				   //'block_edit'	 => 'Редактирование блока',
				   );
		$plugins = $this->cms->plugins;
		unset($plugins['sa'], $plugins['sim']);
		array_unshift($plugins, $this->cms->plugins['sa'], $this->cms->plugins['sim']);
		while(list(,$plugin)=each($plugins)) {
			$plugin_title = $plugin['title'].' ('.$plugin['name'].')';
			$plugin['_empty'] = !empty($plugin['permissions']);
			$plugin['_false'] = ($plugin['permissions']===false);
			if (array_key_exists('permissions', $plugin) && $plugin['permissions']===false) {
				continue;
			} else  {
			/*} else if (!empty($plugin['permissions']) && is_array($plugin['permissions'])) {*/
				$permissions = array('index' => '');
				$permissions = array_merge($permissions, $plugin['permissions']);
				if (!empty($this->cms->handlers_list[$plugin['name']])) {
					$permissions = array_merge($permissions, $block_permissions);
				}
				$row_alias = 'access_'.$plugin['name'].'_';
				$row_counter = 0;
				$rows_total = count($permissions);
				$row_class = 'hide';
				$checked_partial = '';
				$checked_full	= '';
				$checked_none	= '';
				$sum_rights	  = '';
				$perm_test = array_flip($plugins_access_summary[$plugin['name']]);
				if (count($perm_test)==1 && !empty($perm_test[1])) {
					$sum_rights	  = '+';
				} else if (count($perm_test)==1 && !empty($perm_test[0])) {
					$sum_rights	  = '-';
				} else {
					$sum_rights	  = '&nbsp;';
					$row_class	   = 'show';
				}
				$perm_test = array_flip($plugins_access[$plugin['name']]);
				//echo "<br><pre>";print_r($perm_test);die("<br><br>@".__FILE__.":".__LINE__);
				if (count($perm_test)==1 && !empty($perm_test[1])) {
					$checked_full = ' checked="checked"';
					//$sum_rights	  = '+';
				} else if (count($perm_test)==1 && !empty($perm_test[0])) {
					$checked_none = ' checked="checked"';
					//$sum_rights	  = '-';
				} else {
					$row_class = 'show';
					$checked_partial = ' checked="checked"';
				}
				while(list($action,$description)=each($permissions)){
					if ($action == 'index') {
						$table .= '<tr>';
						$table .= '<td colspan="'.($total_groups+6).'">&nbsp;</td>';
						$table .= '</tr>';
						$table .= '<tr>';
						$table .= '<td width="200" colspan="2">'.$plugin_title.'</td>';
						//$table .= '<td width="200" colspan="'.($total_groups+1).'">&nbsp;</td>';
						foreach($groups_index as $gid){
							$group = $groups[$gid];
							$perm_test = array_flip($group['plugins_access'][$plugin['name']]);
							if (count($perm_test)==1 && !empty($perm_test[1])) {
								$marker = '+';
							} else if (count($perm_test)==1 && !empty($perm_test[0])) {
								$marker = '-';
							} else {
								$marker = '&nbsp;';
							}
							$table .= '<th >'.$marker.'</th>';
						}
						reset($groups_index);
						$table .= '<th width="50"><input type="radio" name="'.$row_alias.'switch" value="1" '.$checked_full.' onclick="javascript:permissions_select(1, \'access['.$plugin['name'].']\');permissions_switch(\''.$row_alias.'\', '.$rows_total.',0);"></th>';
						$table .= '<th width="50"><input type="radio" name="'.$row_alias.'switch" value="2" '.$checked_partial.' onclick="javascript:permissions_switch(\''.$row_alias.'\', '.$rows_total.',1);"></th>';
						$table .= '<th width="50"><input type="radio" name="'.$row_alias.'switch" value="0" '.$checked_none.' onclick="javascript:permissions_select(0, \'access['.$plugin['name'].']\');permissions_switch(\''.$row_alias.'\', '.$rows_total.',0);"></th>';
						$table .= '<th width="50">'.$sum_rights.'&nbsp;</th>';
						$table .= '</tr>';
						$plugin_title = '&nbsp;';
					}

					if (!empty($plugins_access[$plugin['name']][$action]) && $plugins_access[$plugin['name']][$action]==true) {
						$checked_allow = ' checked="checked"';
						$checked_disallow = '';
					} else {
						$checked_allow = '';
						$checked_disallow = ' checked="checked"';
					}
					$table .= '<tr class="'.$row_class.'" id="'.$row_alias.($row_counter++).'">';
					$table .= '<td>'.$plugin_title.'</td>';
					$table .= '<td>'.$description.' ('.$action.')</td>';
					foreach($groups_index as $gid){
						$group = $groups[$gid];
						if (!empty($group['plugins_access'][$plugin['name']][$action]) && $group['plugins_access'][$plugin['name']][$action]==true) {
							$marker = '+';
						} else {
							$marker = '-';
						}
						$table .= '<th >'.$marker.'</th>';
					}
					reset($groups_index);
					$table .= '<th><input type="radio" name="access['.$plugin['name'].']['.$action.']" value="1"'.$checked_allow.'></th>';
					$table .= '<th>&nbsp;</th>';
					$table .= '<th><input type="radio" name="access['.$plugin['name'].']['.$action.']" value="0"'.$checked_disallow.'></th>';
					if ($total_groups>-1) {
						if (!empty($plugins_access_summary[$plugin['name']][$action]) && $plugins_access_summary[$plugin['name']][$action]==true) {
							$marker = '+';
						} else {
							$marker = '-';
						}
						$table .= '<th >'.$marker.'</th>';
					}
					$table .= '</tr>';
					$plugin_title = '&nbsp;';
				}
			/*
			} else {
				$action = 'index';
				if (!empty($plugins_access[$plugin['name']][$action]) && $plugins_access[$plugin['name']][$action]==true) {
					$checked_allow = ' checked="checked"';
					$checked_disallow = '';
				} else {
					$checked_allow = '';
					$checked_disallow = ' checked="checked"';
				}
				$table .= '<tr>';
				$table .= '<td colspan="2" width="200">'.$plugin_title.'</td>';
				$table .= '<th width="50"><input type="radio" name="access['.$plugin['name'].'][index]" value="1"'.$checked_allow.'></th>';
				$table .= '<th width="50"><input type="radio" name="access['.$plugin['name'].'][index]" value="0"'.$checked_disallow.'></th>';
				$table .= '</tr>';
			*/
			}

		}
		$table .= '</table>';
		$plugins_table = $table;
		reset($this->cms->plugins);

		/************************************************************/
		/************************************************************/
		/* structure */
		//$access_granted = array();
		//$access_granted = explode('|', $item['cms_structure_access']);
		$site_structure = $this->_get_structure();

		$table  = '<table border=0 cellspacing=0 cellpadding=3 class=printable>';
		$table .= '<tr>';
		$table .= '<th width="400">Страница</th>';
		foreach($groups_index as $gid) {
			$table .= '<th width="50">'.$groups[$gid]['name'].'</th>';
		}
		reset($groups_index);
		$table .= '<th width="50">Разрешить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(1, \'structure\');">все</a>)</th>';
		$table .= '<th width="50">Запретить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(0, \'structure\');">все</a>)</th>';
		if ($total_groups>0) {
			$table .= '<th width="50">Итог</th>';
		}
		$table .= '</tr>';
		while(list(,$s_item)=each($site_structure)) {
			if (array_key_exists($s_item['s_id'], $structure_access) && $structure_access[$s_item['s_id']]==true) {
				$checked_allow = ' checked="checked"';
				$checked_disallow = '';
			} else {
				$checked_allow = '';
				$checked_disallow = ' checked="checked"';
			}
			$table .= '<tr>';
			$table .= '<td>'.$s_item['title'].'<br /><small>'.$s_item['path'].'</small></td>';

			foreach($groups_index as $gid){
				$group = $groups[$gid];
				if (array_key_exists($s_item['s_id'], $group['structure_access']) && $group['structure_access'][$s_item['s_id']]==true) {
					$marker = '+';
				} else {
					$marker = '-';
				}
				$table .= '<th >'.$marker.'</th>';
			}
			reset($groups_index);

			$table .= '<th><input type="radio" name="structure['.$s_item['s_id'].']" value="1"'.$checked_allow.'></th>';
			$table .= '<th><input type="radio" name="structure['.$s_item['s_id'].']" value="0"'.$checked_disallow.'></th>';
			if ($total_groups>0) {
				if (array_key_exists($s_item['s_id'], $structure_access_summary) && $structure_access_summary[$s_item['s_id']]==true) {
					$marker = '+';
				} else {
					$marker = '-';
				}
				$table .= '<th >'.$marker.'</th>';
			}

			$table .= '</tr>';
		}
		$table .= '</table>';
		$structure_table = $table;

		/************************************************************/
		/************************************************************/
		/* groups */
		$groups_list = $this->_get_groups_list();

		$table  = '<table border=0 cellspacing=0 cellpadding=3 class=printable>';
		$table .= '<tr>';
		$table .= '<th width="400">Страница</th>';
		foreach($groups_index as $gid) {
			$table .= '<th width="50">'.$groups[$gid]['name'].'</th>';
		}
		reset($groups_index);
		$table .= '<th width="50">Разрешить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(1, \'groups\');">все</a>)</th>';
		$table .= '<th width="50">Запретить<br />(<a href="javascript:void(0);" onclick="javascript:permissions_select(0, \'groups\');">все</a>)</th>';
		if ($total_groups>0) {
			$table .= '<th width="50">Итог</th>';
		}
		$table .= '</tr>';
		reset($groups);
		//echo "<br><pre>";print_r($groups_access_summary);die("<br><br>@".__FILE__.":".__LINE__);
		while(list($gid,$gname)=each($groups_list)) {
			if (array_key_exists($gid, $groups_access) && $groups_access[$gid]==true) {
				$checked_allow = ' checked="checked"';
				$checked_disallow = '';
			} else {
				$checked_allow = '';
				$checked_disallow = ' checked="checked"';
			}
			$table .= '<tr>';
			$table .= '<td>'.$gname.'</td>';
			reset($groups_index);
			foreach($groups_index as $g_id){
				$group = $groups[$g_id];
				if (array_key_exists($g_id, $groups) && array_key_exists($g_id, $group['groups_access'])  && $group['groups_access'][$g_id]==true) {
					$marker = '+';
				} else {
					$marker = '-';
				}
				$table .= '<th >'.$marker.'</th>';
			}
			$table .= '<th><input type="radio" name="groups['.$gid.']" value="1"'.$checked_allow.'></th>';
			$table .= '<th><input type="radio" name="groups['.$gid.']" value="0"'.$checked_disallow.'></th>';
			if ($total_groups>0) {
				if (array_key_exists($gid, $groups_access_summary) && $groups_access_summary[$gid]==true) {
					$marker = '+';
				} else {
					$marker = '-';
				}
				$table .= '<th >'.$marker.'</th>';
			}
			$table .= '</tr>';
		}
		$table .= '</table>';
		$groups_table = $table;

		/************************************************************/
		/************************************************************/
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'cms_access');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[uid]', $uid);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[site_id]', $site_id);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[al]', $this->nowShowAccessLevel);

		$fob->add_html('separator', '<b>Пользователь: '.$item['name'].' ('.$item['login'].')</b>',	  'html_code');

		$fob->add_html('separator', '<b>Доступ к модулям</b>',	  'html_code');
		$fob->add_html('separator', $plugins_table,	  'html_code');

		$fob->add_html('separator', '<b>Редактирование страниц</b>',	  'html_code');
		$fob->add_html('separator', $structure_table,	  'html_code');

		$fob->add_html('separator', '<b>Работа с группами пользователей</b>',	  'html_code');
		$fob->add_html('separator', $groups_table,	  'html_code');

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$values['cms_plugins_access']	 = array();
			$values['cms_structure_access']   = array();
			if (!empty($_POST['access'])) {
				$values['plugins'] = $_POST['access'];
			}
			if (!empty($values['plugins']['site'])) {
				$values['site'] = (int) $values['plugins']['site'];
				unset($values['plugins']['site']);
			} else {
				$values['site'] = 0;
			}
			if (!empty($_POST['structure'])) {
				$values['structure'] = $_POST['structure'];
			}
			if (!empty($_POST['groups'])) {
				$values['groups'] = $_POST['groups'];
			}

			$query = 'DELETE FROM '.$this->cms->tables['user_permissions'].' WHERE site_id="'.addslashes($site_id).'" and uid='.$uid;
			$result = $this->dbc->query($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			$insert = array(
						  'site_id' => '"'.addslashes($site_id).'"',
						  'type'	=> '"site"',
						  'value'   => (int) $values['site'],
						  'uid'	 => $uid
						  );
			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
			while (list($field, $value) = each($insert)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$query = 'INSERT INTO '.$this->cms->tables['user_permissions'].' ('.$insert_fields.') VALUES('.$insert_values.')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}

			while(list($plugin,$actions)=each($values['plugins'])) {
				while(list($action,$value)=each($actions)) {
					$insert = array(
								  'site_id' => '"'.addslashes($site_id).'"',
								  'plugin'  => '"'.addslashes($plugin).'"',
								  'object'  => '"'.addslashes($action).'"',
								  'type'	=> '"action"',
								  'value'   => (int) $value,
								  'uid'	 => $uid
								  );
					$insert_fields = '';
					$insert_values = '';
					$sql_sep = '';
					while (list($field, $value) = each($insert)) {
						$insert_fields .= $sql_sep.$field;
						$insert_values .= $sql_sep.$value;
						$sql_sep = ', ';
					}
					$query = 'INSERT INTO '.$this->cms->tables['user_permissions'].' ('.$insert_fields.') VALUES('.$insert_values.')';
					$result = $this->dbc->Execute($query);
					if (!$result) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
						return false;
					}
				}
			}

			while(list($structure_id,$value)=each($values['structure'])) {
				$insert = array(
							  'site_id' => '"'.addslashes($site_id).'"',
							  'object'  => '"'.addslashes($structure_id).'"',
							  'type'	=> '"structure"',
							  'value'   => (int) $value,
							  'uid'	 => $uid
							  );
				$insert_fields = '';
				$insert_values = '';
				$sql_sep = '';
				while (list($field, $value) = each($insert)) {
					$insert_fields .= $sql_sep.$field;
					$insert_values .= $sql_sep.$value;
					$sql_sep = ', ';
				}
				$query = 'INSERT INTO '.$this->cms->tables['user_permissions'].' ('.$insert_fields.') VALUES('.$insert_values.')';
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
			}

			if(isset($values['groups']) && count($values['groups'])>0) {
				while(list($structure_id,$value)=each($values['groups'])) {
					$insert = array(
								  'site_id' => '"'.addslashes($site_id).'"',
								  'object'  => '"'.addslashes($structure_id).'"',
								  'type'	=> '"group"',
								  'value'   => (int) $value,
								  'uid'	 => $uid
								  );
					$insert_fields = '';
					$insert_values = '';
					$sql_sep = '';
					while (list($field, $value) = each($insert)) {
						$insert_fields .= $sql_sep.$field;
						$insert_values .= $sql_sep.$value;
						$sql_sep = ', ';
					}
					$query = 'INSERT INTO '.$this->cms->tables['user_permissions'].' ('.$insert_fields.') VALUES('.$insert_values.')';
					$result = $this->dbc->Execute($query);
					if (!$result) {
						$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
						return false;
					}
				}
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			redirect($target_url);
		}
		$variables['output'] = $output;
		$output = $this->cms->parse_template($variables, 'workarea');
		return $output;
	}



	function site_access($arguments) {
		$variables['tabs'] = $this->_print_tabs('users', $s_id);
		$output	 = $this->cms->int_add_h1('Доступ к закрытым разделам сайта');
		$uid		= (int) $arguments['uid'];
		$site_id	= $arguments['site_id'];
		$query	  = "SELECT * FROM ".$this->cms->tables['users']." WHERE uid=".$uid;
		$result	 = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		} else {
			$item = $result->FetchRow();
		}
		if ($item['access_level']>$this->cms->user_data['access_level'] && $item['uid']!=$this->cms->user_data['uid']){
			$this->cms->int_set_message('bottom', $this->translate('You can only edit users with privilegies lower than yours'), $this->plugin['title'], 'error');
			return $output;
		}
		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'site_access');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[uid]', $uid);
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[al]', $this->nowShowAccessLevel);

		$fob->add_html('separator', '<b>Пользователь: '.$item['name'].' ('.$item['login'].')</b>',	  'html_code');

		$fob->add_html('separator', 'Разрешить доступ к выбранным разделам',	  'html_code');
		$access_granted = array();
		$access_granted = explode('|', $item['site_access']);
		$query = "SELECT s_id, title FROM ".$this->cms->tables['structure']." WHERE restricted=1 ORDER BY title";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		} else if ($result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				if (array_search($row['s_id'], $access_granted) === false) {
					$checked = 0;
				} else {
					$checked = 1;
				}
				$fob->add_checkbox(false, '$access['.$row['s_id'].']', '1', '', $row['title'], '', $checked, 'fob-check', '');
			}
		}

		$fob->add_button('submit', 'submit', 'Сохранить', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$values['site_access'] = array();
			if (!empty($_POST['access'])) {
				$values['site_access'] = $_POST['access'];
			}
			$insert_user = array(
						  'site_access'			  => "'|".implode('|', array_keys($values['site_access']))."|'",
						  );
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($insert_user)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$update_query = "UPDATE ".$this->cms->tables['users']." SET ".$update_sql." WHERE uid=".$uid;
			$update_result = $this->dbc->query($update_query);
			if (!$update_result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			redirect($target_url);
		}
		$variables['output'] = $output;
		$output = $this->cms->parse_template($variables, 'workarea');
		return $output;
	}


	function group_add($arguments) {
		$variables['tabs'] = $this->_print_tabs('add_group', $s_id);

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'group_add');
		//$access_levels = $this->_get_lower_access_levels();
		$fob->add_text(true, 'name', '', 'Название группы', '', '', '', 'text', '', '');
		//$fob->add_html('separator', '&nbsp;', 'html_code');
		//$fob->add_checkbox(false, 'valid_account', 'yes', 'Активная учетная запись', '', '', 0, '', '', 'checkbox', '', '');
		$fob->add_button('submit', 'submit', 'Добавить', '', '', '', '', 'button', '', '');

		$variables['output'] = $this->cms->int_add_h1('Новая группа');
		$variables['output'] .= $fob->display('return');
		$output .= $this->cms->parse_template($variables, 'workarea');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$insert_user = array(
						  'access_level'	=> 0,
						  'reg_date'		=> 'now()',
						  'name'			=> "'".addslashes($values['name'])."'",
						  //'login'		   => "'".addslashes($values['login'])."'",
						  //'password'		=> "'".addslashes(md5(trim($values['password'])))."'",
						  'valid_account'   => 1,
						  //'valid_account'   => 0+!empty( $values['valid_account']),
						  //'email'		   => "'".addslashes($values['email'])."'"
						  );
			$insert_fields = '';
			$insert_values = '';
			$sql_sep = '';
			while (list($field, $value) = each($insert_user)) {
				$insert_fields .= $sql_sep.$field;
				$insert_values .= $sql_sep.$value;
				$sql_sep = ', ';
			}
			$query  = 'INSERT INTO '.$this->cms->tables['users'].' ('.$insert_fields.') VALUES('.$insert_values.')';
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$uid = $this->dbc->insert_id();

			$values['access'] = array();
			if (!empty($_POST['access'])) {
				$values['access'] = $_POST['access'];
			}

			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			header('Location: '.$target_url);
		}
		return $output;
	}

	function group_edit($arguments) {
		$variables['tabs'] = $this->_print_tabs('users', $s_id);

		$uid	 = (int) $arguments['uid'];
		$query = "SELECT * FROM ".$this->cms->tables['users']." WHERE 1 and uid=".$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		} else {
			$item = $result->FetchRow();
		}
		if ($item['access_level']>=$this->cms->user_data['access_level'] && $item['uid']!=$this->cms->user_data['uid']){
			$this->cms->int_set_message('bottom', 'Вы не можете редактировать пользователя с уровнем доступа равным вашему или выше.', $this->plugin['title'], 'error');
			return $output;
		}

		$fob = $this->init_fob('fob-user', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'group_edit');
		$fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[uid]', $uid);
		$fob->add_text(true, 'name', $item['name'], 'Имя', '', '', '', 'text', '', '');
		$fob->add_button('submit', 'submit', 'Сохранить', '', '', '', '', 'button', '', '');

		$variables['output'] = $this->cms->int_add_h1('Редактирование данных группы');
		$variables['output'] .= $fob->display('return');
		$output .= $this->cms->parse_template($variables, 'workarea');

		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();
			$insert_user = array(
						  'name'				=> "'".addslashes($values['name'])."'",
						  );
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($insert_user)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$update_query = 'UPDATE '.$this->cms->tables['users'].' SET '.$update_sql.' WHERE uid='.$uid;
			$update_result = $this->dbc->query($update_query);
			if (!$update_result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
			header('Location: '.$target_url);
		}
		return $output;
	}

	function group_delete($arguments) {
		$uid = (int) $arguments['uid'];
		$query = 'DELETE FROM '.$this->cms->tables['users'].' WHERE uid='.$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$query = 'DELETE FROM '.$this->cms->tables['user_data'].' WHERE uid='.$uid;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array("al"=>$this->nowShowAccessLevel));
		header('Location: '.$target_url);
	}

}

?>