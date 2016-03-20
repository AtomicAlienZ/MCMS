<?php				   
class registration_handler {
	
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $cms = '';
	
	var $emailNewFirm = '';

	var $titleMyAccount = 'Регистрация';

	var $email = array("'ibolosig@gmail.com'");
	var $password;
	
	
	function exec($toc) {
		require_once(cms_LIB_PATH."validate.php");
		$block = array();
		
		$block['messageSend'] = 0;
		
		$block["countError"] = 0;
		$block["error"] = 0;
		//обработка формы восстановления пароля
		//if (isset($this->page_info["virtuals"][0]) && $this->page_info["virtuals"][0]=='member') {
		if ($toc['tpl_alias'] == 'recover') {
			$toc['tpl_alias'] = 'recover';
			require_once(cms_LIB_PATH.'protection_image.php');
			$protection_image = new protection_image;

			if (isset($_POST["send"])) {
				$send = $_POST["send"];
				if (!isset($send['email']) || trim($send['email'])=='') {
					$block["error"] = 1;
				}
				elseif (!is_email($send['email'])) {
					$block["error"] = 2;
				}
				else {
					$query = 'SELECT count(login) as total FROM '.$this->cms->tables['users'].' WHERE login LIKE "'.trim(addslashes($send['email'])).'"';
					
					$c_result = $this->dbc->Execute($query);
					if (!$c_result) {
						trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
						return false;
					}
					$users = $c_result->FetchRow();
					if ($users['total'] = 0) {
						$block["error"] = 3;
					}
				}
				if (!empty($send['code'])) {
					if ($protection_image->check_code($send['code']) != true) {
						$block["error"] = 4;
					}
				}
				else {
					$block["error"] = 4;
				}
				if($block["error"] == 0){
					$password = $this->passgen();
					$passwordmd5 = md5($password);
					$email = $send['email'];
					$sql = "UPDATE ".$this->cms->tables['users']." SET password = '".$passwordmd5."' WHERE email = '".$email."'";
					$this->dbc->Execute($sql);
					require_once(cms_LIB_PATH.'mail_event.class.php');
					require_once(cms_LIB_PATH.'email_message.class.php');
					require_once(cms_LIB_PATH.'strcom_parser.class.php');
					$mail_event = &new mail_event($this->plugin['path'].'tpls/mail/', '.tpl');
					$form_data["site"] = str_replace(array("http://", "www."), array("", ""), substr(ROOT_URL, 0, -1));
					$form_data['login'] =  $email;
					$form_data['password'] = $password;
					$form_data['email'] =  $email;
					$mail_event->event('forgot', $form_data);
					$block["error"] = -1;
				}
			}
		}


		if ($this->page_info["user_data"]["uid"]>0) {
			$this->titleMyAccount = 'Мои данные';
			$this->page_info["title"] = $this->titleMyAccount;
			$this->page_info["path"][count($this->page_info["path"])-1]["title"] = $this->titleMyAccount;
			$this->page_info["path"][count($this->page_info["path"])-1]["url"] = ROOT_URL.$this->page_info["lang"]."/registration/";
			$block["values"] = $this->getUserInfo($this->page_info["user_data"]["uid"]);
		}
//		echo "<pre>"; print_r($block["values"]); echo "</pre>";
		if (isset($_POST["send"]))
			$this->checkForm($block);
			
		if ($block["countError"]==0 && isset($block["values"]) && isset($_POST["send"])) {
			$this->saveForm($block);	 
			if($this->page_info["user_data"]["uid"]>0) {
				$block["values"] = $this->getUserInfo($this->page_info["user_data"]["uid"]);  	
			}
		}

//		echo "<pre>"; print_r($block["values"]); echo "</pre>";

		if (empty($toc['tpl_alias'])) {
			$toc['tpl_alias'] = 'default';
		}
		
		$block['protect_img'] = ROOT_URL.'protect_image.php';
		
		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output'=>$block));
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = false;
		}
		//echo "<pre>"; print_r($block); echo "</pre>";
		return $output;
	}

	function checkForm(&$block) {
		$countError = 0;
		$errors = array();
		$block["countError"] = 0;
                //При регистрации генерируем пароль сами, отсылаем его пользователю для проверки валидности e-mail-а
		//далее он его сам может изменить
		if (!isset($_POST["uid"]) || !$_POST["uid"])
		{
			$_POST["send"]["password"]=$this->generate_password(8);
			$_POST["send"]["re_password"]=$_POST["send"]["password"];
		}

		if (isset($_POST["send"]["email"])) $_POST["send"]["email"]=trim($_POST["send"]["email"]);

		$send = $_POST["send"];
		
		$this->prepareVar($send);
		
		if (!isset($_POST["uid"]) || !$_POST["uid"]) {
			if (!isset($send['email']) || trim($send['email'])=='') {
				$countError++;
				$errors['email'] = 1;
			}
			elseif (!is_email($send['email'])) {
				$countError++;
				$errors['email'] = 2;
			}
			else {
				$query = 'SELECT count(login) as total FROM '.$this->cms->tables['users'].' WHERE login LIKE "'.$send['email'].'"';
				$c_result = $this->dbc->Execute($query);
				if (!$c_result) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
					return false;
				}
				$users = $c_result->FetchRow();
				if ($users['total'] > 0) {
					$countError++;
					$errors['email'] = 3;
				}
			}
		}
		if (!isset($_POST["uid"]) || !$_POST["uid"]) {

			if(!isset($send['re_password']) || trim($send['re_password'])=='') {
				$countError++;
				$errors['re_password'] = 1;
			}
			if(!isset($send['password']) || trim($send['password'])=='') {
				$countError++;
				$errors['password'] = 1;
			}
			elseif(!is_password($send['password'])) {
				$countError++;
				$errors['password'] = 2;
			}
			elseif(strlen($send['password'])<5) {
				$countError++;
				$errors['password'] = 3;
			}
			elseif(!compare_passwords($send['password'] ,$send['re_password'])) {
				$countError++;
				$errors['re_password'] = 2;
			}
			
		}
		else {
			if(isset($send['password'])  && trim($send['password']) != '' && !is_password($send['password'])) {
				$countError++;
				$errors['password'] = 1;
			}
			elseif(isset($send['password']) & trim($send['password']) != '' && strlen($send['password'])<5) {
				$countError++;
				$errors['password'] = 1;
			}
			
			if(isset($send['password']) && isset($send['password']) && !compare_passwords($send['password'] ,$send['re_password'])) {
				$countError++;
				$errors['re_password'] = 2;
			} else {
				$this->password = $send['password'];
			}
		}
		if (!isset($send['name']) || trim($send['name'])=='') {
			$countError++;
			$errors['name'] = 1;
		}
		
		require_once(cms_LIB_PATH.'protection_image.php');
		$protection_image = new protection_image;
		if (!empty($send['code'])) {
				if ($protection_image->check_code($send['code']) != true) {
					$countError++;
					$errors['code'] = '1';
				}
		}
		else{
			$countError++;
			$errors['code'] = '1';
		}
		
		$block["countError"] = $countError;
		$block["errors"] = $errors;
		$block["values"] = $send;

		return true;
	}
	
	
	function saveForm(&$block) {
		if(isset($block["values"]["subscribe"])) {
			$subscribe = 1;
		}
		else {
			$subscribe = 0;
		}
		$item = array (
			"password" 			=> "'".addslashes(md5($block["values"]["password"]))."'",
			"email"				=> "'".((isset($block["values"]["email"]))?addslashes($block["values"]["email"]):"")."'",
			"last_name"                 	=> "'".addslashes($block["values"]["last_name"])."'",
			"name" 				=> "'".addslashes($block["values"]["name"])."'",
			"phone"		 	 	=> "'".addslashes($block["values"]["phone"])."'",
			"city" 				=> "'".addslashes($block["values"]["city"])."'",
			"country"			=> "'".addslashes($block["values"]["country"])."'",
			"subscribe"			=> "'".$subscribe."'",
			"reg_date" 			=> "NOW()",
			"valid_account" 	=> "1",
			"access_level" 		=> AL_USER,
		);
		if (isset($_POST["uid"]) && $_POST["uid"]) {
			if(isset($_POST["send"]["password"]) && trim($_POST["send"]["password"])=='') {
				unset($item["password"]);
			}
			unset ($item["valid_account"]);
			unset ($item["reg_date"]);
			unset ($item["access_level"]);
			unset ($item["email"]);
			$update_sql = '';
			$update_sep = '';
			while (list($field, $value) = each($item)) {
				$update_sql .= $update_sep.$field.'='.$value.' ';
				$update_sep = ', ';
			}
			$sql = "UPDATE ".$this->cms->tables["users"]." SET ".$update_sql." WHERE uid='".(int)$_POST["uid"]."'";
		}
		else {
			$item["login"] = "'".addslashes($block["values"]["email"])."'";   
			$sql = "INSERT INTO ".$this->cms->tables["users"]." (".join(" , ", array_keys($item)).") VALUES (".join(" , ",$item).")";
//			die($sql);
		}
		$result = $this->dbc->Execute($sql);
		if (isset($_POST["uid"]) && $_POST["uid"]) {
			$newUID = (int) $_POST["uid"];
		}
		else {
			$newUID = $this->dbc->insert_id();
		}
		if (isset($block["values"]["email"]) AND $block["values"]["email"]) $this->updateSubscribe($block["values"]["email"], $subscribe);
		
		$form_data = $block["values"];

		if (isset($_POST["uid"]) && $_POST["uid"]) {
			$form_data["login"] = $this->page_info["user_data"]["login"];
			if (empty($block["values"]["password"])) {
				$form_data["password"] = "Ваш пароль не изменялся"; 
			} 
		}

		$form_data["site"] = str_replace(array("http://", "www."), array("", ""), substr(ROOT_URL, 0, -1));

		$form_data["dates"] = date('d.m.Y H:h:s');

		require_once(cms_LIB_PATH.'mail_event.class.php');
		require_once(cms_LIB_PATH.'email_message.class.php');
		require_once(cms_LIB_PATH.'strcom_parser.class.php');
		$mail_event = &new mail_event($this->plugin['path'].'tpls/mail/', '.tpl');
		
		$query2 = "SELECT email FROM ".$this->cms->tables['users']." WHERE access_level>=".AL_ADMIN." AND email<>''";
		$result2 = $this->dbc->Execute($query2);
		if ($result2->RecordCount()>0) {
			while($row = $result2->FetchRow()){
				$form_data['manager_mail'] = trim($row['email']);
				if (isset($_POST["uid"]) && $_POST["uid"]) {
					$mail_event->event('send_update', $form_data);
				}
				else {
					$mail_event->event('send', $form_data);
				}
			}
		}

		if(count($this->email)>0) {
			foreach($this->email as $mlA){
				$form_data['manager_mail'] = trim($mlA);
				if (isset($_POST["uid"]) && $_POST["uid"]) {
					$mail_event->event('send_update', $form_data);
				}
				else {
					$mail_event->event('send', $form_data);
				}
			}
		}
		if (isset($block['values']['email'])) { $form_data['manager_mail'] = trim($block['values']['email']); }
		if (isset($_POST["uid"]) && $_POST["uid"]) {
			$mail_event->event('send_user_update', $form_data);
		}
		else {
			$mail_event->event('send_user', $form_data);
		}
	//	echo "<pre>"; print_r($form_data); echo "</pre>";
		
		if (isset($_POST["uid"]) && $_POST["uid"]) {
			$block['messageSend'] = 2;
		}
		else {
			$block['messageSend'] = 1;
		}
	}
	function updateSubscribe($email, $do) {
		$sql = "SELECT * FROM pm_subscribe WHERE email='".$email."'";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()==0) {
			if($do==1) {
				$sql = "INSERT INTO pm_subscribe SET email='".$email."', `date`=NOW()";
				$result = $this->dbc->Execute($sql);
				$new_id = $this->dbc->insert_id();

				$sql = "INSERT INTO pm_subscribe_groups_users SET subscribe_groups_id=1, users_id='".$new_id."'";
				$result = $this->dbc->Execute($sql);
			}
		}
		else {
			$item = $result->FetchRow();
			if($do==0) {
				$sql = "DELETE FROM pm_subscribe WHERE id='".$item["id"]."'";
				$result = $this->dbc->Execute($sql);

				$sql = "DELETE FROM pm_subscribe_groups_users WHERE users_id='".$item["id"]."'";
				$result = $this->dbc->Execute($sql);
			}
		}
//		die();
	}
	
	function getUserInfo($uid) {
		$ret = array();
		if ($uid < 1) return $ret;
		$sql = "SELECT * FROM ".$this->cms->tables["users"]." WHERE uid='".(int)$uid."' AND valid_account=1";
		//echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()==0) return $ret;
		else {
			$ret = $result->FetchRow();//date("d.m.Y H:i", strtotime($item['dates']))
		}
		return $ret;
	}

	function prepareVar(&$send) {
		foreach($send as $key => $val) {
			if (is_array($val)) {
				$val = $this->prepareVar($val);
			}
			else {
				$val = strip_tags($val);
				$val = htmlspecialchars($val);
				$send[$key] = $val;
			}
		}
	}

	function passgen() {
		$result = '';
		$vals = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		while(strlen($result) < 8){
			$result.=$vals[rand(0, strlen($vals))];
		}
		return $result;
	}

// Параметр $number - число символов в пароле  	
  function generate_password($number)  
  {  
    $arr = array('a','b','c','d','e','f',  
                 'g','h','i','j','k','l',  
                 'm','n','o','p','r','s',  
                 't','u','v','x','y','z',  
                 'A','B','C','D','E','F',  
                 'G','H','I','J','K','L',  
                 'M','N','O','P','R','S',  
                 'T','U','V','X','Y','Z',  
                 '1','2','3','4','5','6',  
                 '7','8','9','0');
    // Генерируем пароль  
    $pass = "";  
    for($i = 0; $i < $number; $i++)  
    {  
      // Вычисляем случайный индекс массива  
      $index = rand(0, count($arr) - 1);  
      $pass .= $arr[$index];  
    }  
    return $pass;  
  }  

}
?>