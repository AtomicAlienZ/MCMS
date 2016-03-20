<?php
class main_menu_handler {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $mai = '';

	var $top;
	var $language;
	var $catalogTop;
	var $catalogId;
	var $virtualPath;
	var $initCatalog = false;
	var $pathIds = array();
	var $lastKeyPathSection;
	var $userInfo = array();


	function exec($toc) {
		$this->initTables();
		$menu = array();
		$this->setPathIds();
		$this->setUserInfo();
		$menu['langs'] = $this->getAllLangs(0, 0);
		$menu['langs_total'] = count($menu['langs']);

		$menu['top'] = $this->get_items_set($this->cms->page_info['path'][0]['s_id'], 4, 1);
		$menu['countTop'] = count($menu['top']);

		$menu['top2'] = $this->get_items_set($this->cms->page_info['path'][0]['s_id'], 4, 2);
		$menu['countTop2'] = count($menu['top2']);

		$menu['top3'] = $this->get_items_set($this->cms->page_info['path'][0]['s_id'], 4, 5);
		$menu['countTop3'] = count($menu['top3']);

		$menu['side'] = $this->get_items_set($this->cms->page_info['path'][0]['s_id'], 4, 3);
		$menu['countSide'] = count($menu['side']);

		$menu['footer'] = $this->get_items_set($this->cms->page_info['path'][0]['s_id'], 4, 4);
		$menu['countFooter'] = count($menu['footer']);

		if (!empty($this->cms->page_info['path'][1]['s_id'])) {
			$menu['level2'] = $this->get_items_set($this->cms->page_info['path'][1]['s_id'], 6);
			//if($this->initCatalog === true) $this->addCatalog($menu['level2'], $this->cms->page_info['path'][1]['s_id']);
			$menu['level2_total'] = count($menu['level2']);
		}

//		echo "<pre>"; print_r($menu); echo "</pre>";

		//var_dump($menu); die();

		return $menu;
	}
	function setUserInfo() {
		$userInfo = $this->page_info["user_data"];
		$this->userInfo["site_access"] = array();
		if(isset($userInfo["uid"]) && $userInfo["uid"]>0) {
		}
		else {
		}
	}
	function setPathIds() {
		foreach($this->cms->page_info['path'] as $key => $path) {
			//echo ($path["s_id"]."<br />");
			if(isset($path["s_id"])) {
				$this->lastKeyPathSection = $key;
				$this->pathIds[] = $path["s_id"];
			}
		}
	}

	function getAllLangs() {
		$ret = array();
		$sql = "SELECT l.*, s.s_id, s.relative_url, s.title FROM ".$this->cms->tables["languages"]." l LEFT JOIN ".$this->cms->tables["structure"]." s ON s.language_id=l.language_id WHERE l.enabled='1' AND s.parent='0' ORDER BY l.ord ASC";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$sql, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return false;
		}
		elseif ($result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				$row["url"] = ROOT_URL.$row["relative_url"];
				$ret[] = $row;
			}
		}
		return $ret;
	}

	/*
    whereShow - где показывается пункт меню
    /**/
	function get_items_set($parent_id, $depth=2, $whereShow=1) {

//        echo "<br />------------------------------------------------------------<br/>";
//        echo "parent id = ".$parent_id.", depth = ".$depth." whereShow =".$whereShow."<br />";

		// Например,
		// $menu['top'] = $this->get_items_set(
		// $this->cms->page_info['path'][0]['s_id'],
		// 4,
		// 1 );

		$items = array();

		// Если родитель текущей страницы — не главная, то взять родителя
		// Иначе взять корень сайта на нужном языке

		if ($parent_id>0) { $parent = $this->get_parent($parent_id); }
		else              { $parent = $this->get_root($parent_id);   }


		// Определяем, где выводится пункт меню.

		$where = array();
		switch($whereShow) {
			case 1:
				$where[] = "show_menu_top=1";
				break;
			case 2:
				$where[] = "show_menu_top2=1";
				break;
			case 3:
				$where[] = "show_menu_side=1";
				break;
			case 4:
				$where[] = "show_menu_bottom=1";
				break;
			case 5:
				$where[] = "second_menu=1";
				break;
			default:
				break;
		}

		// Определяем глубину поиска от родителя (по сути, от корня).

		$depth_limit = $parent['level']+$depth;

		// Выбрать из базы
		// Включенные
		// С уровнем меньше заданной глубины
		// С айдишником ранее найденного родителя — в качестве родителя
		// Если пользователь — ноль, то проверяем на доступность страницы
		// Если указано где показывать — то проверяем тоже
		// Сортировать по айдишнику от малого к большому

		$query = 'SELECT *
		          FROM '.$this->cms->tables['structure'].
			' WHERE enabled=1 AND level<='.$depth_limit.
			' AND parent='.$parent['s_id'].
			' AND language_id='.$parent['language_id'].
			' AND type<6 '.
			(($this->page_info["user_data"]["uid"]==0)?" AND restricted=0" : "").
			''.
			((count($where)>0)?" AND ".implode(" AND ", $where):"").
			' ORDER BY group_id ASC';



//		echo "<br />".$query."<Br>";

		// Получаем результат.

		$result = $this->dbc->Execute($query);


		// Ошибка? Кричим.

		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}

		// Ноль результатов? Возвращаем.

		elseif ($result->RecordCount() == 0) {
			return $items;
		}

		// Больше результатов?

		elseif ($result->RecordCount()>0) {

			$menu = array();
			$prev_level = -1;
			$max_level = -1;
			$min_level = 1000;
			$menu_cache[$parent['s_id']] = 1;


			// Каждый результат...

			while($row = $result->FetchRow()) {

				//echo "<br /> ПРОВЕРЯЕМ ".$row['title']."<Br>";

				// **** Отключено **** Если у результата родитель больше, чем у корня — пропускаем

//				if ($row['parent']>$parent['level'] && empty($menu_cache[$row['parent']])) {
//					continue;
//				}

				// **** Отключено **** Если страница только для залогиненных, то проверям. Зачем? Мы в запросе это сделали.

//				if($row['restricted']==1) {
//					if(!isset($this->page_info["user_data"]["access_level"])) continue;
//					if($this->page_info["user_data"]["access_level"]!=AL_DEVELOPER && $this->page_info["user_data"]["access_level"]!=AL_ADMIN && /*!in_array($row['s_id'], $this->userInfo["site_access"])*/!isset($this->page_info["user_data"]["site_access"][$row['s_id']])) continue;
//				}

				$structure = $this->cms->format_structure_item($row);
				$structure['level'] = $prev_level;
				$structure['items'] = array();

				//echo "<br />row level = ".$row['level']." and s_id <div style='display: inline-block; min-width: 100px;'> ".$row['s_id']."</div> depth = ".$depth."<br />";
				//echo '$structure["items"] = $this->get_items_set('.$row["s_id"].', '.$depth.'); <br />';

				// **** Отключено ****
				// Если у элемента
				// level меньше глубины поиска и...
				// **** УБРАЛ **** айдишник элемента присутствует в айдишниках пути страницы, (&& in_array($row['s_id'], $this->pathIds))
				// то запускаем функцию взятия элементов заново, с родителем, который прошел...
				// и с указанием, что искомые дети находятся в том же меню.

				if($row['level']<$depth) {

					//echo "THIS LAUNCHED! <br/><br/>";

					$structure['items'] = $this->get_items_set($row["s_id"], $depth, $whereShow);
//					if($this->initCatalog === true) {
//					    $this->addCatalog($structure['items'], $row["s_id"]);
//					}
				}

				// Теперь считаем их.
				$structure['items_total'] = count($structure['items']);

				// И запоминаем минимальный и максимальный уровень среди найденного.
				$max_level = max($max_level, $structure['level']);
				$min_level = min($min_level, $structure['level']);

				if (!empty($structure['title_menu'])) {
					$structure['title'] = $structure['title_menu'];
				}

				// Записываем в структуру меню найденных детей.
				$menu[$structure['level']][$structure['s_id']] = $structure;
				$menu_cache[$structure['s_id']] = 1;
				$prev_level = $structure['level'];
			}

			if ($max_level>$min_level) {
				for ($i=$max_level;$i>$min_level; $i--) {
					foreach($menu[$i] as $item) {
						$menu[$i-1][$item['parent']]['items'][] = $item;
						$menu[$i-1][$item['parent']]['items_total'] = count($menu[$i-1][$item['parent']]['items']);
						//echo ($menu[$i][$item['s_id']]);
						unset($menu[$i][$item['s_id']]);

					}
				}
			}

			if(isset($menu[$min_level])) {
				//echo $min_level;
				$items = array_values($menu[$min_level]);
				//var_dump($items); die();
			}
		}
//		echo $parent['s_id']."<br>";
//		$items = $this->check_closed($items);

		//var_dump($items); die();

		return $items;
	}

	function get_root($s_id) {
		$sql = "SELECT s_id, language_id, level FROM ".$this->cms->tables['structure']." WHERE parent=0 ORDER BY ord ASC LIMIT 0,1";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()==0) {
			return false;
		}
		else {
			$structure = $result->FetchRow();
			return $structure;
		}
	}

	function get_parent($s_id){
		$query = 'SELECT s_id, language_id, level FROM '.$this->cms->tables['structure'].' WHERE enabled=1 and s_id='.$s_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() == 0) {
			return false;
		}
		$structure = $result->FetchRow();
		return $structure;
	}



	function find_block($s_id) {
		$query = 'SELECT * FROM '.$this->cms->tables['toc'].' WHERE handler="closed_c" AND enabled=1 AND s_id='.$s_id;
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		elseif ($result->RecordCount() != 1) {
			return false;
		}
		else {
			return true;
		}
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
	/*function addCatalog(&$level2, $s_id) {
//		echo $s_id."<pre>"; print_r($level2); echo "</pre>";
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
//				echo "<pre>"; print_r($this->virtualPath); echo "</pre>";
//				$this->getStructureCatalog($this->catalogTop, $level2, $this->cms->page_info['path'][$this->lastKeyPathSection]['url']);
				$this->getStructureCatalog($this->catalogTop, $level2, $this->cms->page_info['structure']['url']);
			}
		}
	}*/
	function getStructureCatalog($parent, &$level2, $startPath) {
//		$sql = "SELECT * FROM ".$this->cms->tables["catalog"]." WHERE parent='".$parent."' AND catalog_id='".$this->catalogId."' AND type='1' /*ORDER BY priority DESC */ORDER BY c.type DESC, ch.ord DESC, BINARY(title) ASC";

		$sql = "SELECT c.* FROM ".$this->cms->tables['catalog']." c LEFT JOIN ".$this->cms->tables['catalog_hierarchy']." ch ON c.id=ch.cat_id WHERE ch.parent=".$parent." AND c.catalog_id=".$this->catalogId." AND c.enabled=1 AND c.type=1 ORDER BY c.type DESC, ch.ord DESC, BINARY(title) ASC";

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
				$row["s_id"] = "c".$row["id"];
				$row["items"] = array();
				if(is_array($this->virtualPath) && in_array($row["id"], $this->virtualPath)) {
					$row["selected"] = 1;
					$this->getStructureCatalog($row["id"], $row["items"], $row["url"]);
				}
				$row["items_total"] = count($row["items"]);
				$level2[] = $row;
			}
		}
	}

}

?>