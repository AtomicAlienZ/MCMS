<?php

class news_handler {

	var $plugin          = array();
	var $config          = array();
	var $dbc;
	var $mai             = '';
	var $url;
	var $email           = array('');
	var $countItemInPage = 1;
	var $page            = 1;
	var $totalItem       = 0;
	var $sortOrder       = array(
		'title' => 'title ',
		'dates' => 'dates DESC',
		'id' => 'id ',
	);
	var $possibleGETVars = array("page", "nid", "tag", "dt");
	var $GETVar          = array();
	var $where           = array();
	var $forse404        = false;

	function exec($toc) {

		// Initializing DB tables.
		$this->initTables();

		$block = array();

		$this->parseGetVar();

		$this->settings = $this->getSettings($toc);
		$this->countItemInPage = $this->settings['lim'];
		$block['settings'] = $this->settings;


		// We have a list-starting news

		$block['GETVars'] = $this->GETVar;
		$block['rubric-one'] = 'many';

		if (isset($this->rubric)) {
			$block['rubrics'] = array($this->rubric['rubric_id'] => $this->rubric['title_menu']);
			$block['rubric-one'] = 'one';
		}

		$this->setWhere($block);

		$this->totalItem = $this->getTotalItem();
		$this->setPage();

		// OK
		// $this->page = ($toc['tpl_alias'] == 'widget') ? 1 : $this->page;

		$this->setNavi($block);

		$block["setFilter"] = 0;

		$block['items'] = $this->getItems($block);

		$block['countItems'] = count($block['items']);
		$this->parseVirtuals($toc, $block, true);

		//echo($this->state);

		if (!isset($this->state)) {
			$block['state'] = 'list';
		} else {
			$block['state'] = $this->state;
		}

		$block['type'] = 'items';

		if ($this->page !== 1) {
			(isset($this->cms->page_info["meta_title"])) && $this->cms->page_info["meta_title"] .= ' - страница ' . $this->page;
			(isset($this->cms->page_info["structure"]["meta_keywords"])) && $this->cms->page_info["structure"]["meta_keywords"] .= ' - страница ' . $this->page;
			(isset($this->cms->page_info["structure"]["keywords"])) && $this->cms->page_info["structure"]["keywords"] .= ' - страница ' . $this->page;
			(isset($this->cms->page_info["structure"]["meta_description"])) && $this->cms->page_info["structure"]["meta_description"] .= ' - страница ' . $this->page;
			(isset($this->cms->page_info["structure"]["description"])) && $this->cms->page_info["structure"]["description"] .= ' - страница ' . $this->page;
		}


		// Processing data to template.
		if ($toc['tpl_alias'] == '') {
			$toc['tpl_alias'] = 'default';
		}


		$tpl_file = $this->plugin['path'] . 'tpls/' . $toc['tpl_alias'] . '.tpl';

		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output' => $block));
			$this->cms->smarty->assign($this->page_info);
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		} else {
			$output = false;
		}

		// Returning ready html to output.
		return $output;
	}


	// ------------------------------------
	// Here go additional functions

	function initTables() {
		$pathToTable = cms_PLUGINS_PATH . 'news_manage/tables.php';
		if (file_exists($pathToTable)) {
			$requiredFiles = get_required_files();
			if (!in_array($pathToTable, $requiredFiles)) {
				include($pathToTable);
				$this->cms->tables = array_merge(@$this->tables, $this->cms->tables);
			}
		}
	}

	function parseGetVar() {
		$name = '';

		$arr_path = array();
		foreach ($this->page_info["virtuals"] as $virt) {
			$relative_url = '/';
			if (isset($arr_path[count($arr_path) - 1])) {
				$relative_url = $arr_path[count($arr_path) - 1];
			}
			$relative_url .= $virt . "/";
			$arr_path[] = $relative_url;
		}
		$count_item = count($arr_path) - 1;

		foreach ($this->page_info["virtuals"] as $val) {
			if (strstr($val, "_")) {
				$varArr = explode("_", $val, 2);
				if (in_array($varArr[0], $this->possibleGETVars)) {
					$this->GETVar[$varArr[0]] = $varArr[1];
				}
			}
		}

	}

	function getSettings($toc) {
		$sql = "SELECT * FROM " . $this->cms->tables["news_structure"] . ' WHERE s_id=' . $toc['s_id'] . ' AND toc_id=' . $toc['toc_id'];
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount() > 0) {
			$settings = $result->FetchRow();
		}
		if (!empty($settings['rubrics']))
			$settings['rubrics'] = str_replace('|', '', $settings['rubrics']);
		return $settings;
	}

	function setWhere($block) {
		$this->where[] = "enabled=1";
		//        $this->where[] = "(auto_start=0 OR (auto_start=1 AND dates_start<>'0000-00-00' AND dates_end<>'0000-00-00' AND dates_start<=NOW() AND dates_end>=NOW()))";
		$where = array();

		if (!empty($this->rubric)) {
			$where[] = "rubrics LIKE '%|" . $this->rubric['rubric_id'] . "|%'";
		} else {
			$where[] = "rubrics LIKE '%|" . $this->settings['rubrics'] . "|%'";
		}

		if (count($where) > 0) {
			$this->where[] = "(" . implode(" OR ", $where) . ")";
		}
	}

	function getTotalItem() {
		$ret = 0;
		$sqlWhere = '';
		if (count($this->where) > 0) {
			$sqlWhere .= " WHERE " . join(" AND ", $this->where);
		}

		$sql = "SELECT id FROM " . $this->cms->tables["news"] . "" . $sqlWhere . "";
		//		echo $sql."<Br>";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			return $ret;
		} else {
			$cnt = $result->RecordCount();
		}
		return $cnt;
	}

	function setPage() {
		if (!empty($this->GETVar["page"])) {
			//$this->cms->vars_404['news'] = 'true';
			if ($this->GETVar["page"] == "all") {
				$this->page = 1;
				$this->countItemInPage = $this->totalItem;
			} else {
				$this->page = (int)$this->GETVar["page"];

			}
		}
		if (empty($this->page) || ($this->page < 1)) {
			$this->page = 1;
		}
	}

	function setNavi(&$block) {
		$arrGet = array();
		$al = (isset($block['one_rubric']['alias'])) ? $block['one_rubric']['alias'] . '/' : '';
		if (isset($this->page_info["virtuals"][0]) AND count($this->page_info["virtuals"]) > 1)
			$startPath = $this->page_info["structure"]["url"] . $this->page_info["virtuals"][0] . "/";
		else $startPath = $this->page_info["structure"]["url"] . $al;
		if (count($this->GETVar) > 0) {
			foreach ($this->GETVar as $key => $value) {
				if ($key != "page") {
					$startPath .= $key . "/" . $value . "/";
				}
			}
		}
		if (isset($_GET) && count($_GET) > 0) {
			foreach ($_GET as $key => $value) {
				if ($key != "page" && $key != "id") {
					$arrGet[] = $key . "=" . $value;
				}
			}
		}

		$get = implode("&", $arrGet);

		$this->totalPage = $this->totalItem / $this->countItemInPage;
		if (($this->totalItem / $this->countItemInPage) != 0) {
			$this->totalPage = ceil($this->totalPage);
		}

		$pages["items_total"] = $this->totalItem;
		$pages["pages_total"] = $this->totalPage;
		$pages["page"] = $this->page;
		if ($this->page > 1) {
			$pages["first"] = $startPath . (($get) ? "?" . $get : "");
			$pages["prev"] = $startPath . "page_" . ($this->page - 1) . "/" . (($get) ? "?" . $get : "");
		} else {
			$pages["first"] = '';
			$pages["prev"] = '';
		}
		if ($this->page < $this->totalPage) {
			$pages["next"] = $startPath . 'page_' . ($this->page + 1) . "/" . (($get) ? "?" . $get : "");
			$pages["last"] = $startPath . 'page_' . $this->totalPage . "/" . (($get) ? "?" . $get : "");
		} else {
			$pages["next"] = '';
			$pages["last"] = '';
		}
		$counter = 1;
		$pages["pages"] = array();
		while ($counter <= $this->totalPage) {
			$temp["title"] = $counter;
			$temp["url"] = $startPath;
			if ($counter > 1) {
				$temp["url"] .= "page_" . $counter . "/";
			}
			$temp["url"] .= (($get) ? "?" . $get : "");
			$pages["pages"][] = $temp;
			$counter++;
		}
		$pages["allItemUrl"] = $startPath . 'page_all/' . (($get) ? "?" . $get : "");
		$block["navi"] = $pages;
	}

	function getItems($block) {

		$ret = array();
		$ordBy = ' ORDER BY ';

		if (isset($this->sortOrder[$this->settings['sorttype']])) {
			$ordBy .= $this->sortOrder[$this->settings['sorttype']];
		} else {
			$ordBy .= $this->sortOrder['dates'];
		}

		$limitSql = " LIMIT " . ($this->page - 1) * $this->countItemInPage . ", " . $this->countItemInPage;

		$sqlWhere = '';

		if (count($this->where) > 0) {
			$sqlWhere .= " WHERE " . join(" AND ", $this->where);
		}

		$sql = "SELECT * FROM " . $this->cms->tables["news"] . "" . $sqlWhere . $ordBy . $limitSql;
		$result = $this->dbc->Execute($sql);

		if (!$result) {
			return $ret;
		} elseif ($result->RecordCount() == 0) {
			return $ret;
		} else {
			while ($row = $result->FetchRow()) {

				/* Lang copies */
				$row["lang_copies"] = '';

				$sql = "SELECT title, meta_title, meta_keywords, meta_description, tags, descr, content, original_id, language FROM " . $this->cms->tables["news"] . " WHERE original_id=" . $row["id"];
				$res = $this->dbc->Execute($sql);

				if (!$res) {

				} elseif ($res->RecordCount() == 0) {

				} else {
					while ($copy_list = $res->FetchRow()) {

						//var_dump($copy_list); die();

						foreach ($copy_list as $key => $copy_row) {
							//var_dump($copy_row);
							//print_r("/n");
							$row["lang_copies"][$copy_list["language"]] = $copy_list;
						}
					}

					//var_dump($row["lang_copies"]); die();
				}

				//var_dump($row["lang_copies"]); die();

				foreach ($row["lang_copies"][$this->cms->page_info["lang"]] as $key => $copy_line) {
					//die ($row[$key]);
					if ($copy_line) {
						$row[$key] = $copy_line;
					} else {
						$row[$key] = '';
					}

				}


				/* ! Lang copies */

				$row["url"] = $this->url . "nid_" . $row['id'] . "/";

				$arrRubrics = explode("|", substr($row["rubrics"], 1, -1));

				$row["rubricsInfo"] = array();

				foreach ($arrRubrics as $rubricId) {
					if (isset($block["rubrics"][$rubricId]))
						$row["rubricsInfo"][] = $block["rubrics"][$rubricId];
				}

				$ruuuu = str_replace('|', '', $row["rubrics"]);

				$s = "SELECT * FROM pm_news_rubrics WHERE rubric_id=" . $ruuuu;
				$r = $this->dbc->Execute($s);
				$rw = $r->FetchRow();

				$row['categoryurl'] = $rw['alias'];

				$row["countRubricsInfo"] = count($row["rubricsInfo"]);
				$ret[] = $row;
			}
		}

		return $ret;
	}

	function parseVirtuals($toc, &$block, $static_rubric = false) {

		//die("lol");

		if ($static_rubric == false) {
			switch (count($this->page_info['virtuals'])) {

				case 1:
					//die('1');
					$this->state = 'list';
					if ($this->page_info['virtuals']['0'] !== '') {
						$this->getRubric($this->page_info['virtuals']['0']);

						if ($toc['tpl_alias'] == 'start_from_menu' && isset($this->rubric)) {
							$this->cms->page_info["path"][] = array("title" => $this->rubric["title"]);
							$block['one_rubric'] = $this->rubric;
							$this->cms->page_info["meta_title"] = (!empty($this->rubric["meta_title"])
								? $this->rubric["meta_title"]
								: $this->rubric["title"]);
							$this->cms->page_info["structure"]["keywords"] = $this->rubric["meta_keywords"];
							$this->cms->page_info["structure"]["description"] = $this->rubric["meta_description"];
							$this->cms->vars_404['news'] = 'true';
						} else {
							$this->cms->forse404 = true;
						}

					}
					break;

				case 2:
					//die('2');
					//var_dump($this->cms->vars_404); die();
					$this->getRubric($this->page_info['virtuals']['0']);
					//var_dump(substr($this->page_info['virtuals'][1], 0, 5)); die();
					if (substr($this->page_info['virtuals'][1], 0, 5) !== 'page_') {
						$this->state = 'item';
						$block['item'] = $this->getItem($this->page_info['virtuals']['1'], $block);

						if (!empty($block['item'])) {
							if ($toc['tpl_alias'] == 'start_from_menu' && isset($this->rubric)) {
								$y = $this->cms->smarty->_tpl_vars['lang_ru'];
								$this->cms->page_info["path"][] = array("title" => $this->rubric ["title"], 'url' => $y . $this->rubric ['alias']);
								$this->cms->page_info["path"][] = array("title" => $block['item']['title']);
							}
						} else {
							$this->cms->vars_404['news'] = 'false';
						}
					} else {
						$this->state = 'list';
						$block['rubrics'] = array($this->rubric['rubric_id'] => $this->rubric['title_menu']);
						$block['rubric-one'] = 'one';
					}
//                        $this->cms->vars_404['news'] = 'true';
					break;

				default :
					$this->cms->vars_404['news'] = 'false';
					break;
			}
		} // If rubric is static

		else {

			//echo(count($this->page_info['virtuals']));

			// If there is one virtual and
			if (count($this->page_info['virtuals']) == 1 && $this->page_info['virtuals'][0] !== '') {

				$this->getRubric($this->settings['rubrics'], true);

				// If we have a list-starting template
				if ($toc['tpl_alias'] == 'default') {
					// If it has virtual called page_...
					if (substr($this->page_info['virtuals'][0], 0, 5) == 'page_') {
						// And then comes a number of a page...
						if (is_numeric(substr($this->page_info['virtuals'][0], 5))) {
							// And it is not bigger than total of pages and not less than 0.
							if ((intval(substr($this->page_info['virtuals'][0], 5))) <= ($this->totalPage) && (intval(substr($this->page_info['virtuals'][0], 5))) > 0) {
								$this->cms->vars_404['news'] = 'true';
							}
						}
					}
				}

				// If we have country-menu template...
				if ($toc['tpl_alias'] == 'start_from_menu' && isset($this->rubric)) {
					$y = $this->cms->smarty->_tpl_vars['lang_ru'];
					$this->cms->page_info["path"][] = array("title" => $this->rubric["title"], 'url' => $y . $this->rubric['alias']);
					$this->cms->page_info["path"][] = array("title" => $block['item']['title']);
				}

				/* else {
					// ROCK
					//$this->forse404 = true;


					$this->cms->vars_404['news'] = 'true';
				}*/

				$block['item'] = $this->getItem($this->page_info['virtuals']['0'], $block, false);

				if (!empty($block['item'])) {
					$this->cms->page_info["path"][] = array("title" => $block['item']['title']);
				}

			} elseif (count($this->page_info['virtuals']) > 1) {
				//$this->cms->vars_404['news'] = 'false';
			} else {
				$this->cms->vars_404['news'] = 'false';
			}

		}
	}

	function getRubric($identity, $by_id = false) {
		$sql = "SELECT * FROM " . $this->cms->tables["news_rubrics"] . " WHERE ";
		$sql .= (!$by_id) ? 'alias=' : 'id=';
		$sql .= "'" . $identity . "'";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount() > 0) {
			$this->cms->vars_404['news'] = 'true';
			$this->rubric = $result->FetchRow();
		}
	}

	function getItem($alias, $block, $check = true) {

		if (!isset($this->where[0]) && count($this->page_info['virtuals']) == 1) {
			return;
		}

		$ret = array();

		if ($check == true) {
			$this->cms->vars_404['news'] = 'false';
		}

		$sql = "SELECT * FROM " . $this->cms->tables["news"] . " WHERE alias='" . $alias . "' AND enabled=1 AND original_id=0";
		$result = $this->dbc->Execute($sql);

		if (!$result) {
			return $ret;
		} elseif ($result->RecordCount() == 0) {
			return $ret;
		} else {
			$this->state = 'item';
			$this->cms->vars_404['news'] = 'true';
			$row = $result->FetchRow();

			/* Lang copies */

			$sql = "SELECT * FROM " . $this->cms->tables["news"] . " WHERE original_id=" . $row["id"];
			$result = $this->dbc->Execute($sql);

			if (!$result) {

			} elseif ($result->RecordCount() == 0) {

			} else {
				while ($copy_list = $result->FetchRow()) {
					foreach ($copy_list as $key => $copy_row) {
						$row["lang_copies"][$copy_list["language"]] = $copy_list;
					}
				}
			}

			/* ! Lang copies */

			$row["url"] = $this->url . $alias; // "nid_" . $row['id'] . "/";
			$arrRubrics = explode("|", substr($row["rubrics"], 1, -1));
			$row["rubricsInfo"] = array();

			foreach ($arrRubrics as $rubricId) {
				if (isset($block["rubrics"][$rubricId]))
					$row["rubricsInfo"][] = $block["rubrics"][$rubricId];
			}

			$row["countRubricsInfo"] = count($row["rubricsInfo"]);

			$this->cms->page_info["meta_title"] = (!empty($row["meta_title"]) ? $row["meta_title"] : $row["title"]);
			$this->cms->page_info["structure"]["keywords"] = $row["meta_keywords"];
			$this->cms->page_info["structure"]["description"] = $row["meta_description"];


			if ($row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["title"] != '') {
				$row["title"] = $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["title"];
				$row["descr"] = $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["descr"];
				$row["content"] = $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["content"];

				$this->cms->page_info["meta_title"] = (!empty($row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["meta_title"]) ? $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["meta_title"] : $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["title"]);
				$this->cms->page_info["structure"]["keywords"] = $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["meta_keywords"];
				$this->cms->page_info["structure"]["description"] = $row["lang_copies"]["" . $this->cms->page_info["lang"] . ""]["meta_description"];
			}
			//die( $ret["lang_copies"]["en" ]["title"]);

			$ret = $row;
		}

		$process_content = $ret["content"];

		$RusA = "[абвгдеёжзийклмнопрстуфхцчшщъыьэюя]";
		$RusV = "[аеёиоуыэюя]";
		$RusN = "[бвгджзклмнпрстфхцчшщ]";
		$RusX = "[йъь]";
		$Hyphen ='&shy;';

		$re1 = "/(".$RusX.")(".$RusA.$RusA.")/iu";
		$re2 = "/(".$RusV.")(".$RusV.$RusA.")/iu";
		$re3 = "/(".$RusV.$RusN.")(".$RusN.$RusV.")/iu";
		$re4 = "/(".$RusN.$RusV.")(".$RusN.$RusV.")/iu";
		$re5 = "/(".$RusV.$RusN.")(".$RusN.$RusN.$RusV.")/iu";
		$re6 = "/(".$RusV.$RusN.$RusN.")(".$RusN.$RusN.$RusV.")/iu";

		//die($re1);
		//die("$1".$Hyphen."$2");


		$process_content =preg_replace($re1, "$1".$Hyphen."$2", $process_content);
		$process_content =preg_replace($re2, "$1".$Hyphen."$2", $process_content);
		$process_content =preg_replace($re3, "$1".$Hyphen."$2", $process_content);
		$process_content =preg_replace($re4, "$1".$Hyphen."$2", $process_content);
		$process_content =preg_replace($re5, "$1".$Hyphen."$2", $process_content);
		$process_content =preg_replace($re6, "$1".$Hyphen."$2", $process_content);


//		$.fn.hyphenate = function() {

//
//			this.each(function(){
//				var text=$(this).html();
//				text = text.replace(re1, "$1"+Hyphen+"$2");
//				text = text.replace(re2, "$1"+Hyphen+"$2");
//				text = text.replace(re3, "$1"+Hyphen+"$2");
//				text = text.replace(re4, "$1"+Hyphen+"$2");
//				text = text.replace(re5, "$1"+Hyphen+"$2");
//				text = text.replace(re6, "$1"+Hyphen+"$2");
//				$(this).html(text);
//
//				//console.log(text);
//			});
//		};

		$ret["content"] = $process_content;

		$sql = "UPDATE " . $this->cms->tables["news"] . " SET clicks='" . ($ret["clicks"] + 1) . "' WHERE id='" . $ret["id"] . "'";
		$this->dbc->Execute($sql);

		return $ret;
	}

	function setRubrics($rubrics) {
		$ret = array();
		if (trim($rubrics) != '') {
			$intRubric = array();
			$arrRubrics = explode("|", substr($rubrics, 1, -1));
			if (count($arrRubrics) > 0) {
				foreach ($arrRubrics as $rubric_id) {
					if ($rubric_id > 0) {
						$intRubric[] = $rubric_id;
					}
				}
			}
			if (count($intRubric) > 0) {
				$sql = "SELECT * FROM " . $this->cms->tables['news_rubrics'] . " WHERE rubric_id IN (" . implode(",", $intRubric) . ")";
				$result = $this->dbc->Execute($sql);
				if ($result && $result->RecordCount() > 0) {
					while ($item = $result->FetchRow()) {
						$ret[$item["rubric_id"]] = $item["title"];
					}
				}
			}
		}
		return $ret;
	}

	function getRelCatUrl($id) {
		$ret = '';
		$query = 'SELECT * FROM ' . $this->cms->tables['news_structure'] . ' WHERE id=' . $id;
		$result = $this->dbc->Execute($query);
		if ($result && $result->RecordCount() > 0) {
			$item = $result->FetchRow();
			$structure = $this->cms->get_structure_item($item['s_id']);
			$ret = $structure['url'];
		}
		return $ret;
	}

}

?>