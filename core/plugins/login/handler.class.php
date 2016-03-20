<?php

class login_handler {

	var $cookie_expire_period_day = 14;

    function exec($toc) {
		$block = array();
//		$block["type"] = "form";

		$this->cookie_name = $this->cms->site_id;
		$block['url'] = $this->page_info['structure']['url'];
		if (!empty($this->cms->page_info['user_data']['uid'])) {
			$uid = $this->cms->page_info['user_data']['uid'];
			$pages = $this->get_permitted_pages($uid,$toc);
			$block['pages'] = $pages;
		}
		else {
			$uid = 0;
		}
		if (empty($toc['tpl_alias'])) {
			$toc['tpl_alias'] = 'default';
		}

		if (!empty($_POST['form']['login']) and !empty($_POST['form']['pass'])) {
			if (!empty($_COOKIE[$this->cookie_name])) {
				$session_id = $_COOKIE[$this->cookie_name];
				$query = "DELETE FROM ".$this->cms->tables['user_sessions']." WHERE session_id='".$session_id."' ";
				$result = $this->dbc->query($query);
				if (!$result) {
					trigger_error($this->dbc->err_message.'<br>Query: '.$query, E_USER_ERROR);
				}
				setcookie ($this->cookie_name, '', time()- 3600, "/", "");
				setcookie ($this->cookie_name.'_rp', '', time()- 3600, "/", "");
			}

			$login    = $_POST['form']['login'];
			$password = $_POST['form']['pass'];
			if (!empty($_POST['form']['remember'])) {
				$remember_password = true;
			}
			else {
				$remember_password = false;
			}
			$query = "SELECT * FROM ".$this->cms->tables['users']." WHERE login='".$login."' and valid_account='1' and password='".md5($password)."'";
			$result = $this->dbc->query($query);
			if (!$result) {
				trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
				return false;
			}
			if ($result->RecordCount() <> 1) {
				$this->page_info['err_logins'] = $_POST['form']['login'];
				$block['user']['error'] = 'Неправильный логин';
				$block['user_data'] = $this->page_info['user_data'];
				$block['lang'] = $this->page_info['lang'];
                                //if ($block['user']['error'] !== 'Неправильный логин') {
                                    $tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
                                    if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
                                            $this->cms->smarty->assign(array('output'=>$block));
                                            $output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
                                    }
                                    else {
                                            $output = false;
                                    }
                               // }
				return $output;
			}
			$user = $result->FetchRow();
			$this->clear_sessions($user['uid']);
			$session_id =  md5($this->cms->user_data['ip']['ip'].'-'.$_SERVER['HTTP_USER_AGENT']);

			$insert = array(
				'uid'           => $user['uid'],
				'session_id'    => "'".$session_id."'",
				'login_time'    => "now()",
				'last_refresh'  => "now()"
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
//			echo $query;
			$result = $this->dbc->query($query);
			if (!$result) {
				trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
				return false;
			}
			if ($remember_password) {
				setcookie ($this->cookie_name, $session_id, time()+($this->cookie_expire_period_day*24*60*60), "/", "");
				setcookie ($this->cookie_name.'_rp', '1', time()+ ($this->cookie_expire_period_day*24*60*60), "/", "");
				$event = 'logged in, session started, password saved';
			}
			else {
				setcookie ($this->cookie_name, $session_id, 0, "/", "");
				setcookie ($this->cookie_name.'_rp', '', time()-3600, "/", "");
				$event = 'logged in, temporary session started';
			}

			$url = $this->page_info["request_url"];
			header('Location: '.$url);//updated by tr
		}


		$block['user_data'] = $this->cms->page_info['user_data'];
		$block['lang'] = $this->page_info['lang'];
		$block["pathTemplate"] = $tpl_file = $this->plugin['path'].'tpls/';

		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		$block['lang'] = $this->cms->page_info['path'][0]['alias'];
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output'=>$block));
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = false;
		}
		return $output;
	}

	function clear_sessions($uid) {
		$query = "DELETE FROM ".$this->cms->tables['user_sessions']." WHERE uid=".$uid; //."' ";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
			return false;
		}
		else return true;
    }

	function _is_valid_login($login, $password) {
		$query = 'SELECT uid, login, access_level FROM '.$this->cms->tables['users'].' WHERE access_level>'.AL_USER.' and valid_account="1" and login="'.addslashes($login).'" and password="'.addslashes(md5($password)).'"';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
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

	function get_permitted_pages($uid,$toc) {
		$level = $this->cms->page_info['user_data']['access_level'];

		$query = "SELECT * FROM ".$this->cms->tables['structure']." WHERE language_id<='".$this->cms->page_info["language_id"]."' AND restricted=1 AND enabled=1 AND level=2";
//		echo $query;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() > 0) {
			while ($row = $result->FetchRow()) {
//				$pages[] = $this->get_page_info($row['s_id']);
				$pages[] = $row;
			}
			return $pages;
		}
	}
	function get_page_info($s_id) {
		$query = "SELECT * FROM ".$this->cms->tables['structure']." WHERE s_id=".$s_id;
//		echo $query;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() > 0) {
			$row = $result->FetchRow();
			$page['title'] = $row['title'];
			$page['relative_url'] = $row['relative_url'];
			return $page;
		}
		else return false;
	}

	function logout() {
		$url = str_replace("logout/", "", $this->page_info["request_url"]);
//		die($url);
		if (!empty($this->page_info['user_data']['login'])) {
			if (!empty($_COOKIE[$this->cms->site_id]) && strlen($_COOKIE[$this->cms->site_id])==32 ) {
				$session_id = $_COOKIE[$this->cms->site_id];
				$query = "DELETE FROM ".$this->cms->tables['user_sessions']." WHERE session_id='".addslashes($session_id)."' ";
				$result = $this->dbc->query($query);
				if (!$result) {
					trigger_error($this->dbc->err_message.'<br>Query: '.$query, E_USER_ERROR);
					return false;
				}
				setcookie ($this->cms->site_id, '', time()- 3600, "/", "");
				setcookie ($this->cms->site_id.'_rp', '1', time()-3600, "/", "");
				log_event($this->page_info['user_data'], 'logged out, session terminated');
			}
			else {
				setcookie ($this->cms->site_id, '', time()- 3600, "/", "");
				setcookie ($this->cms->site_id.'_rp', '1', time()-3600, "/", "");
				$query = "DELETE FROM ".$this->cms->tables['forum_sessions']." WHERE uid='".$this->page_info['user_data']['uid']."' ";
				$result = $this->dbc->query($query);
				if (!$result) {
					trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
					return false;
				}
				log_event($this->page_info['user_data'], 'ERROR: wrong session ID');
			}
			header("Location: ".$url);
		}
		else header("Location: ".$url);
	}

}
?>