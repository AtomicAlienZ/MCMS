<?php
class sitemap_handler {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $mai = '';
	var $default_depth = 10;
	var $default_settings = array(
		'title'     => 'Map',
		'depth'     => 0,
		'handler'   => '',
		'template'  => 0,
		'parent'    => -1
	);
	var $initCatalog = false;
	var $userInfo = array();

	function exec($toc) {
		$this->initTables();
		$this->setUserInfo();
		$sql = 'SELECT * FROM '.$this->cms->tables['common_blocks'].' WHERE toc_id='.$toc['toc_id'];
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount()==1) {
			$block = $result->FetchRow();
		}
		else {
			$block = $this->default_settings;
		}
		$conditions = array();
		$conditions[] = 's.show_map=1';
		$parent = $this->_get_parent($block, $toc);
//		echo "<pre>"; print_r($parent); echo "</pre>";
/*		if (is_array($parent) && $parent['s_id']>0) {
			$conditions[] = "s.parent=".$parent['s_id'];
		}*/
		$depth = $block['depth'];
		if ($depth==-1){
			$depth = $this->default_depth;
		}
		if (!empty($depth)) {
			$max_depth = $parent['level'] + $depth+1;
			$conditions[] = 's.level<'.$max_depth.'';
		}
		else {
			$max_depth = 255;
		}
		if (!empty($block['handler'])) {
			$conditions[] = 'toc.handler="'.addslashes($block['handler']).'"';
		}
		if (!empty($block['template'])) {
			$conditions[] = 's.template_id='.$block['template'].'';
		}
		if (count($conditions)>0) {
			$cond = implode(' AND ', $conditions);
		}
		else {
			$cond = '1';
		}
		$structure = $this->getFullStructure($block, $block["items"], $parent, $cond);
		$block["countItems"] = count($block["items"]);

//	echo "<pre>"; print_r($block); echo "</pre>";

		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
			return false;
		}

		if (empty($toc['tpl_alias'])) $toc['tpl_alias'] = 'default';
		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output'=>$block));
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = $block['content'];
		}
//		echo "<prE>"; print_r($block); echo "</pre>";
		return $output;
	}

	function setUserInfo() {
		$userInfo = $this->page_info["user_data"];
		$this->userInfo["site_access"] = array();
		if(isset($userInfo["uid"]) && $userInfo["uid"]>0) {
			$groups = explode("|", substr($userInfo["member_of_groups"], 1, -1));
			if(count($groups)>0) {
				foreach($groups as $group) {
					$this->userInfo["groups"][$group] = $this->getInfoGroup($group);
					$siteAccessId = explode("|", substr($this->userInfo["groups"][$group]["site_access"], 1, -1));
					foreach($siteAccessId as $sId) {
						$this->userInfo["site_access"][$sId] = $sId;
					}
				}
			}
		}
		else {
		}
	}
	function getInfoGroup($groupId) {
		$sql = "SELECT * FROM ".$this->cms->tables["users"]." WHERE uid='".$groupId."' AND valid_account=1";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() != 1) {
			return false;
		}
		$row = $result->FetchRow();
		return $row;
	}

	function getFullStructure(&$block, &$items, &$parent, $cond) {
//		echo "<prE>"; print_r($this->cms->user_data); echo "</pre>";
		if($this->initCatalog === true && isset($parent["relative_url"])) $this->addCatalog($items, $parent["s_id"], ROOT_URL.$parent["relative_url"], $parent["level"]);
		if(!isset($this->cms->user_data['access_level'])) $this->cms->user_data['access_level']=0;
		$cond1 = " AND s.parent='".$parent["s_id"]."'";
		$cond1 .= " AND s.access_level<='".$this->cms->user_data['access_level']."'";
//		$cond1 = "";
        if (!empty($block['handler'])) {
			$sql = "SELECT distinct(s.s_id), s.restricted, s.access_level, s.level, s.parent, s.type, s.title, s.relative_url FROM ".$this->cms->tables["toc"]." toc, ".$this->cms->tables["structure"]." s  WHERE ".$cond.$cond1." and s.enabled=1 and s.s_id=toc.s_id ORDER BY s.ord";
		}
		else {
			$sql = "SELECT s.s_id, s.restricted, s.access_level, s.parent, s.level, s.type, s.title, s.relative_url FROM ".$this->cms->tables["structure"]." s  WHERE ".$cond.$cond1." and s.enabled=1 ORDER BY s.ord";
		}
//		echo $sql."<br><br>";
//		echo "<pre>"; print_r($this->page_info["user_data"]["site_access"]); echo "</pre>";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
			return false;
		}

		while($structure = $result->FetchRow()) {
            //var_dump($structure);
			$structure = $this->cms->format_structure_item($structure);
			if ($structure['type'] > 5) continue;
			if($structure['restricted']==1) {
				if(!isset($this->page_info["user_data"]["access_level"])) continue;
				if($this->page_info["user_data"]["access_level"]!=AL_DEVELOPER && $this->page_info["user_data"]["access_level"]!=AL_ADMIN/* && !in_array($structure['s_id'], $this->userInfo["site_access"])*/ && !isset($this->page_info["user_data"]["site_access"][$structure['s_id']])) continue;
			}
			$prev_level = $structure['parent'];
//			$structure["items"] = array();

			if($structure['restricted']==1&&$this->cms->user_data['uid']==0) {
			}
			else {
//				$this->getFullStructure($block, $structure["items"], $structure, $cond);
//				$structure["countItems"] = count($structure["items"]);
				$items[] = $structure;
				$this->getFullStructure($block, $items, $structure, $cond);
			}
		}
	}

	function _get_parent($block, $toc) {
		$parent = array('s_id'=>0, 'level'=>1);
		if ($block['parent']>0) {
			$sql = 'SELECT s_id, level, type FROM '.$this->cms->tables['structure'].' WHERE s_id='.$block['parent'];
//			echo $sql."<br><br>";
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
				return false;
			}
			elseif ($result->RecordCount()==1) {
				$parent = $result->FetchRow();
			}
			else {
				$block['parent'] = -1;
			}
		}
		if ($block['parent']==-1) {
			$block['parent'] = $toc['s_id'];
			$sql = "SELECT s_id, level, type FROM ".$this->cms->tables["structure"]." WHERE parent=0 AND language_id='".$this->page_info["language_id"]."'";
//			echo $sql."<br><br>";
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
				return false;
			}
			elseif ($result->RecordCount()==1) {
				$parent = $result->FetchRow();
			}
		}
		return $parent;
	}

	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.'catalog/tables.php';
		if(file_exists($pathToTable)) {
			require_once($pathToTable);
			if(isset($this->tables)) {
				$this->cms->tables = array_merge($this->tables, $this->cms->tables);
			}
			$this->initCatalog = true;
		}
	}

	function addCatalog(&$level2, $s_id, $startPath, $level) {
		$sql = "SELECT ci.cat_id FROM ".$this->cms->tables["catalog_item"]." ci LEFT JOIN ".$this->cms->tables["toc"]." t ON t.toc_id=ci.toc_id WHERE ci.s_id='".$s_id."' AND t.s_id='".$s_id."' LIMIT 0,1";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return false;
		}
		elseif ($result->RecordCount()>0) {
			$row = $result->FetchRow();
			$this->catalogId = $row["cat_id"];
			$sql = "SELECT id FROM ".$this->cms->tables["catalog"]." WHERE catalog_id='".$this->catalogId."' AND parent='0' AND enabled=1";
			$result = $this->dbc->Execute($sql);
			if (!$result) {
				trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
				return false;
			}
			elseif ($result->RecordCount() == 0) {
				return false;
			}
			elseif ($result->RecordCount()>0) {
				$row = $result->FetchRow();
				$this->catalogTop = $row["id"];
				foreach($this->cms->page_info["virtuals"] as $id) {
					if(is_numeric($id)) $this->virtualPath[] = (int)$id;
				}
				$this->getStructureCatalog($this->catalogTop, $level2, $startPath, $level+1);
			}
		}
	}

	function getStructureCatalog($parent, &$level2, $startPath, $level) {
		$sql = "SELECT c.* FROM ".$this->cms->tables['catalog']." c LEFT JOIN ".$this->cms->tables['catalog_hierarchy']." ch ON c.id=ch.cat_id WHERE ch.parent=".$parent." AND c.enabled=1 AND c.type=1 ORDER BY c.type DESC, ch.ord DESC, BINARY(title) ASC";
//echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return false;
		}
		elseif ($result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				$row["url"] = $startPath.$row["id"]."/";
				$row["level"] = $level;
				$level2[] = $row;
				$this->getStructureCatalog($row["id"], $level2, $row["url"], $level+1);
			}
		}
	}

}

?>