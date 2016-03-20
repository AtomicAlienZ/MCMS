<?php
class plugin_admin_interface extends cms_plugin_admin {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $fob;
	var $cms = '';
	var $cookie_expire_period = 1200;

	function _is_valid_login($login, $password) {
		$query = 'SELECT uid, access_level, password(uid) as pas_uid FROM '.$this->cms->tables['users'].' WHERE access_level>'.AL_USER.' and login="'.addslashes($login).'" and password="'.addslashes(md5($password)).'"';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], 'SQL Error', 'error');
			//die($query);
			return false;
		}
		elseif ($result->RecordCount() == 1) {
			$user = $result->FetchRow();
			return $user;
		}
		elseif ($result->RecordCount()>0) {
			return false;
		}
		return false;
	}

	function login($arguments) {
		$output  = '';

		$fob = $this->init_fob('fob', $this->cms->admin_url);
		$fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
		$fob->add_hidden('$'.$this->cms->request_vars['command'], 'login');

		$fob->add_text(true, 'login',	'', 'Login', '', 'fob-text', '', '', '', '');
		$fob->add_password(true, 'password', '', 'Password', '', 'fob-text', '', '', '', '');
		$fob->add_checkbox(false, 'member', 1, 'Запомнить', '', '', 0, 'fob-check', ''); 
		$fob->add_button('submit', 'submit', 'Login', '');
		$output .= $fob->display('return');
		if ($fob->is_submited()  && $fob->is_valid_submit() ) {
			$values = $fob->get_submited_values();   
			$login = trim($values['login']);
			$password = trim($values['password']);
			$member = trim($values['member']);
			$user = $this->_is_valid_login($login, $password);
			if ($user === false) {
				sleep(1);
				$this->cms->int_set_message('top', 'Неверно введен логин или пароль. Попробуйте еще раз.', '', 'error');
			}
			elseif ($user['access_level']<AL_MANAGER) {
				sleep(1);
				$this->cms->int_set_message('top', 'У Вас нет доступа к системе управления сайтом', '', 'error');
			}
			else {
				if($member && isset($user["pas_uid"])) {
					js_setcookie("member", $user["pas_uid"], 3600*24*14, "/", "");
//					die('member');
				}
				else {
//					die('dont member');
				}
				$this->_login($user['uid']);
				echo('<br>');
				redirect($this->cms->admin_url);
				return false;
			}
		}
		return $output;
	}

	function _login($uid) {
		if (!empty($this->user_data['uid'])) {
			redirect($this->cms->admin_url);
			header('location: /alpha/');
		}
		$output = array(
			'template' => 'login'
		);	
		if (!empty($_COOKIE[$this->cms->site_id])) {
			$session_id = $_COOKIE[$this->cms->site_id];
			$query = "DELETE FROM ".$this->cms->tables['user_sessions']." WHERE session_id='".addslashes($session_id)."' ";
			$result = $this->dbc->Execute($query);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			}
			js_setcookie ($this->cms->site_id, '', time()- 3600, "/", "");
		}

		$session_id =  md5(uniqid(rand(),1).time().$this->cms->site_id.'Alpha!');
		$insert = array(
			'uid' => $uid,
			'session_id' => "'".addslashes($session_id)."'",
			'login_time' => "now()",
			'last_refresh' => "now()"
		);
		$insert_fields = '';
		$insert_values = '';
		$sql_sep = '';
		while (list($field, $value) = each($insert)) {
			$insert_fields .= $sql_sep.$field;
			$insert_values .= $sql_sep.$value;
			$sql_sep = ', ';
		}
		$query = "INSERT INTO ".$this->cms->tables['user_sessions']." (".$insert_fields.") VALUES(".$insert_values.")";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$result = js_setcookie($this->cms->site_id, $session_id, time()+ $this->cookie_expire_period, "/", "");
		return true;
	}	
	
	
	function logout($arguments) {
		if (!empty($_COOKIE[$this->cms->site_id])) {
			$session_id = $_COOKIE[$this->cms->site_id];
			setcookie ($this->cms->site_id, '', time()- 3600, '/', '');
			$query = 'DELETE FROM '.$this->cms->tables['user_sessions'].' WHERE session_id="'.addslashes($session_id).'" LIMIT 1';
			$result = $this->dbc->Execute($query);
			if (!$result) {}
		}
		js_setcookie("member", '', 0, "/", "");
		redirect($this->cms->admin_url."?".time());
		$this->cms->int_set_message('top', 'Ваш сеанс работы завершен. Рекомендуется закрыть это окно браузера.', '', 'warning');
	}
	
	function init(){
		require_once(cms_LIB_PATH.'form_builder.class.php');
		require_once(cms_LIB_PATH.'validate.php');
		$this->cms->init_dbc();
		$this->dbc = &$this->cms->dbc;
	}

	function main($command, $arguments) {
		$this->init();
		switch($command) {
			default:
			case 'login':
				$result = $this->login($arguments);
				break;
			case 'logout':
				$result = $this->logout($arguments);
				break;			
		}
		return $result;
	}
	
	
}

?>