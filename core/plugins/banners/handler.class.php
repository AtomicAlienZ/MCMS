<?php

class banners_handler {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $mai = '';

	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.'banners_manage/tables.php';
		if(file_exists($pathToTable)) {
			include($pathToTable);
			$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			//$this->cms->install->dbc = &$this->dbc;
			//$this->cms->install->tables = &$this->tables;
			//$this->cms->install->tablesColumn = &$this->tableColumn;
		}
	}
	
    function exec($toc) {		
    	$this->initTables();
    	
    	$block = array();  
    	
		$s_id    =      $toc['s_id'];
		$toc_id  =      $toc['toc_id'];
		
		$block['ROOT_URL'] = ROOT_URL;   
		$block['s_id'] = $s_id; 
		$block['toc_id'] = $toc_id; 
		
		$where 	 = array();
		$where[] = 's_id = '.$s_id;
		$where[] = 'toc_id = '.$toc_id;
		
		$query = 'SELECT * FROM '.$this->cms->tables['banners_group'].' WHERE '.join(' AND ', $where);
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		$item = $result->FetchRow();
		$block['insert_mode'] = $item['insert_mode']; 
		$block['zone_id'] = $item['zone_id'];
		$block['lim'] = $item['lim'];
		
		// если метод вставки - HTML  
		if ($item['insert_mode'] == 1) {
			$user_ip = $this->getip();     
			
			$query2 = "SELECT * FROM ".$this->cms->tables["banners"]." WHERE enabled=1 AND zone_id='".$item['zone_id']."' AND (useTotalShow=0 OR useTotalShow=1 AND totalShow>0) && (limitLife=0 OR limitLife=1 AND limitLifeStart>=NOW() AND limitLifeEnd<=NOW())";
//			echo $query2."<br>";
			$result2 = $this->dbc->Execute($query2);
			if (!$result2) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query2, E_USER_ERROR);
				return false;
			}
			$all_banners = array();  
			while ($item2 = $result2->FetchRow()) {			
				switch ($item2['quality']) {
					case 1:
						$item2['quality'] = 'Low';
						break;
					case 2:
						$item2['quality'] = 'Medium';
						break;
					case 3:
						$item2['quality'] = 'High';
						break;
					default:
						$item2['quality'] = 'Autohigh';		
						break;					
				}
				switch ($item2['flash_menu']) {
					case 0:
					default:  
						$item2['flash_menu'] = 'False';
						break;
					case 1:
						$item2['flash_menu'] = 'True';			
						break;  				
				}
				$item2["url"] = rawurlencode($item2["url"]);
				for($i = 0; $i < $item2['preference']; $i++) {
					$all_banners[] = $item2;
				}			
			}
			
			if ($item['lim'] > $result2->RecordCount()) {
				$item['lim'] = $result2->RecordCount();	
			}
//			echo "<pre>"; print_r($all_banners); echo "</pre>";
			
			$sel_banners = array();
			$banner_id = 0; 
			while (count($sel_banners) < $item['lim']) {
				$sel_banners[$all_banners[$banner_id]['id']] = $all_banners[$banner_id];	
				$banner_id++; 
			}  
			
			foreach ($sel_banners as $v) {
				$query3 = "INSERT INTO ".$this->cms->tables['banners_stat']." (banner_id, operation, user_ip, dates) VALUES(".$v['id'].", 'view', '".$user_ip."', NOW())";
				$result3 = $this->dbc->Execute($query3);
				if (!$result3) {
					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query3, E_USER_ERROR);
					return false;
				}
//				$query3 = "INSERT INTO ".$this->cms->tables['banners_stat']." (banner_id, operation, user_ip, dates) VALUES(".$v['id'].", 'view', '".$user_ip."', NOW())";
//				$result3 = $this->dbc->Execute($query3);
//				if (!$result3) {
//					trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query3, E_USER_ERROR);
//					return false;
//				}
				if($v["useTotalShow"] == 1) {
					$sql = "UPDATE ".$this->cms->tables['banners']." SET totalShow=(totalShow-1) WHERE id='".$v['id']."'";
					$res = $this->dbc->Execute($sql);
					if (!$res) {
						trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
						return false;
					}
				}
				if($v["type"]==3 && $v["showAfterPage"]==1) {
					$codeForJsArr = str_replace(array("\r\n", "\r", "\n"), array("{its_line}", "{its_line}", "{its_line}"), $v["code"]);
					$codeForJsArr = str_replace(array("</script>", "</noscript>"), array("<'+'/'+'script>", "<'+'/'+'noscript>", "{its_line}"), $codeForJsArr);
					$v["codeForJs"] = explode("{its_line}", $codeForJsArr);
					$this->page_info["outerBanner"][] = $v;
					$this->page_info["countOuterBanner"] = count($this->page_info["outerBanner"]);
				}
			}
			
			//$block['all_banners'] = $all_banners;
			$block['banners'] = $sel_banners;
		}
		$block['countBanners'] = count($block['banners']);
		
		////////////////////////////////////////////////////////////////////////////
		if ($toc['tpl_alias'] == '') $toc['tpl_alias'] = 'default';
		$block['pathTemplate'] = $tpl_file = $this->plugin['path'].'tpls/';


		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output' => $block));
			$this->cms->smarty->assign($this->page_info);
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = false;
		}
//		echo "<pre>"; print_r($this->page_info["outerBanner"]); echo "</pre>";
		if($toc["toc_id"]==26) {
//			echo "<pre>"; print_r($block); echo "</pre>"; die();
		}
		return $output;
    }
    
    function getip() {
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");

  		elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");

  		elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");

  		elseif (!empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
	  
  		else
			$ip = "unknown";
	  
  		return $ip;
	}

}
?>