<?php

class logout_handler {
	var $cookie_name = 'sid';
	var $cookie_expire_period = 3600;
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $mai = '';


	function exec($structure_id) {
		if (!empty($this->page_info['user_data']['login']) && isset($_GET["logout"])) {
			$this->cms->vars_falseget['logout'] = 'true';
			if (!empty($_COOKIE[$this->cms->site_id])  && strlen($_COOKIE[$this->cms->site_id])==32 ) {
				$session_id = $_COOKIE[$this->cms->site_id];

				$query = "DELETE FROM ".$this->cms->tables['user_sessions']." WHERE session_id='".addslashes($session_id)."' ";
				$result = $this->dbc->query($query);
				if (!$result) {
					trigger_error($this->dbc->err_message.'<br>Query: '.$this->dbc->query_log[$this->dbc->query_counter-1], E_USER_ERROR);
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
			@session_start();
			unset($_SESSION['consult_uid']);
			unset($_SESSION['consult_name']);
			unset($_SESSION['consult_email']);
			unset($_SESSION['consult_permissions']);

			header('Location: '.$this->page_info["request_url"]);
		}
	}
}

?>
