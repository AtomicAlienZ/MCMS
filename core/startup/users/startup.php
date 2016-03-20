<?php

	$this->init_dbc();
	$this->dbc->query("SET NAMES utf8");
	$cookie_expire_period = 1209600;
	$iplog_timeout = 1800;
	$user_ip = _getIP();
	$this->user_data = array(
		'name' => '',
		'login' => '',
		'uid' => 0,
		'ip' => $user_ip,
		'access' => array()
	);

	if (empty($_COOKIE[$this->site_id])) {
		if (!empty($_COOKIE["member"])) {
			$sql = "SELECT *, password(uid) as pas_uid FROM ".$this->tables['users']." WHERE password(uid)='".addslashes(strip_tags($_COOKIE["member"]))."'";
			$result = $this->dbc->Execute($sql);
			if ($result->RecordCount() == 1) {
				$user = $result->FetchRow();
				js_setcookie("member", $user["pas_uid"], 3600*24*14, "/", "");
				$session_id =  md5(uniqid(rand(),1).time().$this->site_id.'Alpha!');
				$insert = array(
					'uid' => $user["uid"],
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
				$sql = "INSERT INTO ".$this->tables['user_sessions']." (".$insert_fields.") VALUES(".$insert_values.")";
//				die($sql);
				$result = $this->dbc->Execute($sql);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
					return false;
				}
				js_setcookie($this->site_id, $session_id, 3600*24*14, "/", "");
//				header("Location: ".cms_ADMIN_URL);
				header("Location: ".$_SERVER["REQUEST_URI"]);
				die();
//				return $user;
			}
		}
		else {
			log_event($this->user_data, 'unauthorized user');
		}
	}
	elseif (strlen($_COOKIE[$this->site_id])!=32) {
		log_event($this->user_data, 'ERROR: wrong session ID ('.$this->site_id.' = '.$_COOKIE[$this->site_id].')');
	}
	else {
		$session_id = addslashes($_COOKIE[$this->site_id]);
//		$query = 'SELECT * FROM '.$this->tables['user_sessions'].' s, '.$this->tables['users']." u, ".$this->tables['user_data'].' udata WHERE u.uid=udata.uid AND s.session_id="'.$session_id.'" AND s.uid=u.uid';
		$query = 'SELECT * FROM '.$this->tables['user_sessions'].' s, '.$this->tables['users'].' u WHERE s.session_id="'.$session_id.'" AND s.uid=u.uid ORDER BY s.id DESC LIMIT 0,1';
		$result = $this->dbc->Execute($query);

		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
		}
		elseif ($result->RecordCount() < 1) {
			log_event($this->user_data, 'ERROR: session not found');
		}
		elseif ($result->RecordCount() > 1) {
			log_event($this->user_data, 'ERROR: multiply sessions');
		}
		else {
			if (!empty($_COOKIE[$this->site_id.'_rp'])) {
				$remember_password = true;
			}
			else {
				$remember_password = false;
			}

			$user_data = $result->FetchRow();

			if ( $user_data['last_refresh'] < Date('Y-m-d H:i:s', time() - $cookie_expire_period ) ) {
				log_event($this->user_data, 'ERROR: sessions timed out');
			} 
			else {
				if ($remember_password) {
					setcookie ($this->site_id, $session_id,	time()+ $cookie_expire_period, "/", "");
					setcookie ($this->site_id.'_rp', '1', time()+ $cookie_expire_period, "/", "");
					$event = 'permanent session prolonged, password saved';
				}
				else{
					setcookie ($this->site_id, $session_id, 0, "/", "");
					$event = 'temporary session prolonged';
				}
				$user_data['cms_permissions'] = $GLOBALS['ACCESS_CMS'][$user_data['access_level']];
				$user_data['site_permissions'] = $GLOBALS['ACCESS_SITE'][$user_data['access_level']];
				if ($user_data['cms_permissions'] == AP_PARTIAL) {
					$user_data['cms_structure_access'] = array_flip(explode('|', $user_data['cms_structure_access']));
					$user_data['cms_plugins_access'] = array_flip(explode('|', $user_data['cms_plugins_access']));
					unset($user_data['cms_structure_access']['']);
					unset($user_data['cms_plugins_access']['']);
				} else {
					$user_data['cms_structure_access'] = '';
					$user_data['cms_plugins_access'] = '';
				}
				if ($user_data['site_permissions'] == AP_PARTIAL) {
					$user_data['site_access'] = array_flip(explode('|', $user_data['site_access']));
					unset($user_data['site_access']['']);
				} else {
					$user_data['site_access'] = '';
				}
				$user_data['ip'] = $user_ip;
				unset($user_data['password']);

				$this->user_data = $user_data;
				log_event($this->user_data, $event);
				$query = 'UPDATE '.$this->tables['user_sessions'].' SET last_refresh=now(), ip="'.$user_data['ip']['ip'].'" WHERE session_id="'.$session_id.'"';
				$result = $this->dbc->Execute($query);
				if (!$result) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				}
//				if
//				define('IS_ADMINISTRATION_MODE',	true);

			}
		}
	}

	function _getIP() {
//		Get some headers that may contain the IP address
		$SimpleIP = $_SERVER['REMOTE_ADDR'];
		$TrueIP = '';

		if (empty($TrueIP) && (!empty($_SERVER['HTTP_FORWARDED_FOR'])) ) {
			$TrueIP = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		if (empty($TrueIP) && (!empty($_SERVER['HTTP_X_FORWARDED'])) ) {
			$TrueIP = $_SERVER['HTTP_X_FORWARDED'];
		}
		if (empty($TrueIP) && (!empty($_SERVER[''])) ) {
			$TrueIP = $_SERVER['HTTP_FORWARDED'];
		}

		$GetProxy = ($TrueIP == '' ? 0:1);

		if ($GetProxy == 0) {
			if (!empty($_SERVER['HTTP_VIA'])) {
				$TrueIP = $_SERVER['HTTP_VIA'];
			}
			if (empty($TrueIP) && (!empty($_SERVER['HTTP_X_COMING_FROM'])) ) {
				$TrueIP = $_SERVER['HTTP_X_COMING_FROM'];
			}
			if (empty($TrueIP) && (!empty($_SERVER['HTTP_COMING_FROM'])) ) {
				$TrueIP = $_SERVER['HTTP_COMING_FROM'];
			}
			if (!empty($TrueIP)) {
				$GetProxy = 2;
			}
		};

		if ($TrueIP == $SimpleIP) $GetProxy = 0;

		// Return the true IP if found, else the proxy IP with a 'p' at the begining
		switch ($GetProxy) {
			case '0':
//				True IP without proxy
				$IP = $SimpleIP;
				$proxy = '';
				break;
			case '1':
				$b = ereg ("^([0-9]{1,3}\.){3,3}[0-9]{1,3}", $TrueIP, $IP_array);
				if ($b && (count($IP_array)>0)) {
//					True IP behind a proxy
					$IP = $IP_array[0];
					$proxy = '';
				}
				else {
//					Proxy IP
					$IP = "".$SimpleIP;
					$proxy = 'p';
				};
				break;
			case '2':
//				Proxy IP
				$IP = "".$SimpleIP;
				$proxy = 'p';
		};
		$result = array (
			'ip'	=> $IP,
			'proxy' => $proxy);
		return $result;
	}

	function log_event($user_data, $event){
		//return false;
		$log = array();
		$log[] = Date('d.m.Y');
		$log[] = Date('H:i:s');
		$log[] = $user_data['ip']['ip'];
		$log[] = $user_data['login'];
		$log[] = $user_data['uid'];
		$log[] = $event;
		$log[] = $_SERVER["REQUEST_METHOD"];
		$log[] = $_SERVER['REQUEST_URI'];
		if (empty($_COOKIE[SITE_ID])) {
			$log[] = '-x-';
		} else {
			$log[] = $_COOKIE[SITE_ID];
		}
		if(isset($_SERVER["HTTP_USER_AGENT"])) $log[] = str_replace(';', '::', $_SERVER["HTTP_USER_AGENT"]);
		$log_file = cms_DATA_PATH.'access_events.csv';
		$log_data = implode(';', $log)."\n";
		if ( !file_exists($log_file) || ( file_exists($log_file) && is_writable($log_file) ) ) {
			if (!$handle = fopen($log_file, 'a+')) {
				trigger_error('failed openning event log: '.$log_file, E_USER_NOTICE);
				return false;
			}
			if (!fwrite($handle, $log_data)) {
				trigger_error('failed writing to event log: '.$log_file, E_USER_NOTICE);
				return false;
			}
			fclose($handle);
		}
		else {
			trigger_error('event log is not writeable: '.$log_file, E_USER_NOTICE);
			return false;
		}
	}

?>