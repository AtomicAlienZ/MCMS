<?php

class feedback_handler {
	var $items_per_page = 10;
	var $separator = '?';
	var $default_settings = array(
		'title'         => 'Map',
		'depth'         => 0,
		'handler'         => '',
		'template'         => 0,
		'parent'         => -1
	);
	var $sendMessage = 0;

	function exec($toc) {
		$this->initTables();
		$block=Array();

		$query = 'SELECT * FROM '.$this->cms->tables['feedback'].' WHERE toc_id='.$toc['toc_id'].' and enabled=1  ORDER BY feedback_id ASC';
//		die($query);
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$block['mail'] = array();
		while($item = $result->FetchRow()) {
			$block['mail'][] = $item;
		}

		if (empty($toc['tpl_alias'])) {
			$toc['tpl_alias'] = 'default';
		}
		if (isset($_POST["fio"])) {
			$form_info = $_POST;
			$this->checkForm($form_info, $block);

	//	echo"<pre>";print_r($form_info);die();
		if ($block['errors']['countErrors'] == 0) {
			require_once(cms_LIB_PATH.'mail_event.class.php');
			require_once(cms_LIB_PATH.'email_message.class.php');
			require_once(cms_LIB_PATH.'strcom_parser.class.php');
			$mail_event = &new mail_event($this->plugin['path'].'tpls/mail/', '.tpl'); 

			foreach ($block['mail'] as $key=>$val) {
				if ($val!='')
					$form_info['to'] = $val['mail'];
				$form_info['from'] = $val['mail'];
				
				//$form_info['phone'] = $form_info['phone'];
				//$form_info['company'] = $form_info['company'];
				//$form_info['theme'] = $form_info['theme'];
				
				$form_info['site'] = str_replace("http://","",$this->cms->page_info['root_url']);
				$form_info['site'] = str_replace("/","",$form_info['site']);
				$form_info['site_url'] = $this->cms->page_info['root_url'];

				$mail_event->event('query', $form_info);
			}

			$toc['tpl_alias'] = 'sanks';
		}
		}
//		$block = $this->default_settings;
//		$block = array_merge($search, $block);
		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		
		//echo $tpl_file;
//		echo '<pre>';print_r($block);echo '</pre>';	 die();
		
		$block['protect_img'] = ROOT_URL.'protect_image.php';	
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output'=>$block));
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = $block['content'];
		}

		return $output;
	}
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.$this->plugin['name'].'/tables.php';
		if(file_exists($pathToTable)) {
			require_once($pathToTable);
			if(isset($this->tables)) {
				$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			}
			$this->initCatalog = true;
		}
		include_once(cms_LIB_PATH.'validate.php');
	}
	
	function checkForm($send, &$block){
		$errors = array();
		$errors['countErrors'] = 0;
        
        if (!isset($send['fio']) || trim($send['fio']) == '') {
            $errors['fio'] = 1;
            $errors['countErrors']++;
        }
        
 //       if (!isset($send['phone']) || trim($send['phone']) == '') {
 //         $errors['phone'] = 1;
 //         $errors['countErrors']++;
 //     }

	  	
        if (!isset($send['query']) || trim($send['query']) == '') {
			$errors['query'] = 1;
			$errors['countErrors']++;
		}
		
		 if (!isset($send['email']) || trim($send['email']) == '') {
			$errors['email'] = 1;
			$errors['countErrors']++;
		}

		/*if ($send['email']!='' || trim($send['email']) != ''){
			if(!is_email($send['email'])) {
				$errors['email'] = 1;
				$errors['countErrors']++;
			}
		}*/

		/*require_once(cms_LIB_PATH.'protection_image.php');
       	$protection_image = new protection_image;
       
		if (!empty($send['code'])) {
			if ($protection_image->check_code($send['code']) != true) {
				$errors['countErrors']++;
				$errors['code'] = 1;
			}
		}
		else{
			$errors['code'] = 1;
			$errors['countErrors']++;
		}*/
		
		

		$block['send'] = $send;
		$block['errors'] = $errors;

		if(isset($send['fio']))
         	$send['fio'] 	= strip_tags($send['fio']);
       	if(isset($send['query']))
        	$send['query'] 	= strip_tags($send['query']);
		if(isset($send['email']))
		 	$send['email'] = strip_tags($send['email']);
		 	
		if(isset($send['phone']))
         	$send['phone'] 	= strip_tags($send['phone']);
		if(isset($send['company']))
         	$send['company'] 	= strip_tags($send['company']);
		if(isset($send['theme']))
         	$send['theme'] 	= strip_tags($send['theme']);
         	
		if(isset($send['fio']))
         	$send['fio'] 	= strip_tags($send['fio']);
		 	

			return $send;
	}

}

?>