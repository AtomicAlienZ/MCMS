<?php
class cloud_handler {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $mai = '';
	
	var $possibleGETVars = array("word");
	var $GETVar = array();

	var $where = '';
	var $countItemInPage = 20;
	var $countResultInPage = 20;
	var $directionsThisToc = array();

	var $lineFonts = array();
	var $minFont = 8;//px

	function exec($toc) {
		$this->initTables();
		$this->parseGetVar();
		
		$block=array();
		$block["var"] = $this->GETVar;

		if($toc["tpl_alias"]=="") $toc["tpl_alias"] = "default";

		switch($toc["tpl_alias"]) {
			case "result":
				$block["items"] = array();
				if(!empty($this->GETVar["word"])) {
					$sql = "SELECT * FROM ".$this->cms->tables['words']." WHERE word_id = ".$this->GETVar['word'];
					$res = $this->dbc->Execute($sql); //die($sql);
					if ($res->RecordCount()>0) $this->cms->vars_404['words'] = 'true';
					$block["items"] = $this->getItems();
				}
				$block["countItems"] = count($block["items"]);
				break;
			default:
				$block["items"] = $this->getWords();
				$block["countItems"] = count($block["items"]);
				break;
		}
		
		$block["pathToModuleTpl"] = $this->plugin['path'].'tpls/';

		$tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
		if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
			$this->cms->smarty->assign(array('output'=>$block));
			$this->cms->smarty->assign($this->page_info);
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
		}
		else {
			$output = false;
		}
//		if($toc["tpl_alias"]=="result") {
//			echo "<pre>"; print_r($block); echo "</pre>"; die();
//		}
		return $output;
	}

//	Парсим переменные из урла
	function parseGetVar() {
		$name = '';
		foreach($this->page_info["virtuals"] as $val) {
			if(strstr($val, "_")) {
				$varArr = explode("_", $val, 2);
				if(in_array($varArr[0], $this->possibleGETVars)) {
					$this->GETVar[$varArr[0]] = $varArr[1];
				}
			}
		}
	}
	function getWords() {
		$ret = array();
		$startUrl = $this->getUrl("cloud", "result");
		if(empty($startUrl)) {
			return $ret;
		}
		$sql = "SELECT word_id, word_".$this->page_info["lang"]." as word, count_view_".$this->page_info["lang"]." as count_view FROM ".$this->cms->tables["words"]." WHERE count_view_".$this->page_info["lang"].">0 ORDER BY count_view_".$this->page_info["lang"]." DESC LIMIT 0,".$this->countItemInPage;
//		echo $sql."<Br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			$counter = 0;
			$maxFont = floor($result->RecordCount() / 2) + $this->minFont;
			while($item = $result->FetchRow()) {
				if($counter % 2) $maxFont--;
				$url = $startUrl;
				$item["url"] = $startUrl."word_".$item["word_id"]."/";
				$item["fontsSize"] = $maxFont;
				$ret[] = $item;
				$counter++;
			}
			$temp = array();
			foreach($ret as $vals) {
				$temp[$vals["word"]] = $vals;
			}
			ksort($temp);
			$ret = array_values($temp);
		}
		return $ret;
	}

	function getItems() {
		$ret = array();
//		Возврат структурных страниц
		$sql = "SELECT s.s_id, s.title, c.content, s.relative_url FROM ".$this->cms->tables['structure']." AS s LEFT JOIN ".$this->cms->tables['toc']." AS toc ON ( s.s_id = toc.s_id and toc.enabled=1) LEFT JOIN ".$this->cms->tables['content_html']." AS c ON ( toc.content_id = c.content_id ) WHERE s.words LIKE '%|".(int)$this->GETVar["word"]."|%' AND s.enabled=1 AND language_id='".$this->page_info["language_id"]."' GROUP BY s.s_id ORDER BY s.title, s.ord ASC";
//		echo $sql."<Br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			while($item = $result->FetchRow()) {
				$item["relative_url"] = "/".$item["relative_url"];
				$ret[] = $item;
			}
		}

//		Возврат новостей
		$sql = "SELECT id, rubrics FROM pm_news_structure WHERE s_id IN (SELECT s_id FROM ".$this->cms->tables["structure"]." WHERE language_id='".$this->page_info["language_id"]."' AND enabled=1) AND id=block_id";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			while ($row = $result->FetchRow()) {
				$arrRubrikTemp = explode("|", substr($row["rubrics"], 1, -1));
				if(count($arrRubrikTemp)>0) {
					foreach($arrRubrikTemp as $rubrikId) {
						$rubrikIds[$rubrikId] = "rubrics LIKE '%|".$rubrikId."|%'";
					}
				}
			}
		}
		if(count($rubrikIds)>0) {
			$sql = "SELECT * FROM pm_news WHERE words LIKE '%|".(int)$this->GETVar["word"]."|%' AND enabled='1' AND (".implode(" OR ", $rubrikIds).") GROUP BY id ORDER BY dates DESC, title ASC";
//			echo $sql."<br>";
			$result = $this->dbc->Execute($sql);
			if ($result && $result->RecordCount()>0) {
				while($row = $result->FetchRow()) {
					$structure = $this->getUrlForNewsForOtherRubrik($row["rubrics"]);
					if(!empty($structure["s_id"])) {
						$row['relative_url'] = $structure['url'].'nid_'.$row['id'].'/';
						$row["title"] = $row["title"];
						$ret[] = $row;
					}
				}
			}
		}

//		Возврат Каталога
		$urlsTop = array();
		$sql = "SELECT * FROM pm_catalog WHERE words LIKE '%|".(int)$this->GETVar["word"]."|%' AND enabled='1' GROUP BY id ORDER BY ord DESC, title_".$this->page_info["lang"]." ASC";
//		echo $sql."<br>"; //die();
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				$arrRURL = explode("/", $row["full_relative_url"]);
				$idTop = $this->getIdCatalogByRelativeUrl("/".$arrRURL[1]."/");
				$row["idTop"] = $idTop;
				if(empty($urlsTop[$idTop])) {
					$urlsTop[$idTop] = $this->getUrlByIdTopCatalog($idTop);
				}
//				echo "'".$urlsTop[$idTop]."'<br>";
				$row["relative_url"] = $urlsTop[$idTop].substr($row["relative_url"], 1);
				$row = $this->apply_current_language($row);
				$row["content"] = $row["description"];
				$ret[] = $row;
			}
		}

//		die();

//		Возврат Библиотеки
		$startUrlLibrary = $this->getUrl("library");
		$urlsTop = array();
		$sql = "SELECT * FROM pm_library WHERE words LIKE '%|".(int)$this->GETVar["word"]."|%' AND enabled='1' GROUP BY library_id ORDER BY ord DESC, title_".$this->page_info["lang"]." ASC";
//		echo $sql."<br>"; die();
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			while($row = $result->FetchRow()) {
				$row["relative_url"] = $startUrlLibrary.substr($row["relative_url"], 1);
				$row = $this->apply_current_language($row);
				$ret[] = $row;
			}
		}

		
		return $ret;
	}
	function getIdCatalogByRelativeUrl($rurl) {
		$ret = 0;
		$sql = "SELECT id FROM pm_catalog WHERE full_relative_url='".addslashes($rurl)."'";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			$row = $result->FetchRow();
			$ret = $row["id"];
		}
		return $ret;
	}
	function getUrlByIdTopCatalog($id) {
		$ret = '';
		$sql = "SELECT s.relative_url FROM ".$this->cms->tables["structure"]." s LEFT JOIN ".$this->cms->tables["toc"]." t ON t.s_id=s.s_id LEFT JOIN pm_catalog_structure cs ON (cs.toc_id=t.toc_id AND main_flag=1) WHERE cs.record_id='".$id."' AND t.handler='catalog' AND t.enabled='1' AND s.enabled=1 AND s.language_id='".$this->page_info["language_id"]."' LIMIT 0,1";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result && $result->RecordCount()>0) {
			$row = $result->FetchRow();
			$ret = "/".$row["relative_url"];
		}
		return $ret;
	}
	// Функция наложение текущего языка сайта на результаты запроса
	function apply_current_language($data) {

		if (is_array($data)) {

			// По очереди проходим по всем полям
			$record = array();
			foreach($data as $field_id => $field) {

				// Находим языковую версию языка поля
				$field_lang = substr($field_id, -3);
				if (substr($field_lang,0,1) == "_") {
					$field_lang = substr($field_lang, 1);
				} else {
					$field_lang = "";
				}

				// Проверяем что поле поддерживает один из определенных на сайте языков
				if (in_array($field_lang, array("ru", "ua"))) {

					// Если это поля текущего языка, то добавляем его в массив
					if ($field_lang == $this->page_info["lang"]) {

						// Рассчитываем новый идентификатор поля
						$new_field_id = substr($field_id, 0, -3);

						// Добавляем значение поля в массив
						$record[$new_field_id] = $field;
					}

				} else {

					// Если поле не зависит от языка, то просто копируем его
					$record[$field_id] = $field;
				}
			}
			$data = $record;
		}

		return $data;
	}
	function getUrlForNewsForOtherRubrik($rubriks) {
		$arrRubriks = explode("|", substr($rubriks, 1, -1));
		if(count($arrRubriks)>0) {
			foreach($arrRubriks as $rubrikId) {
				$sql = "SELECT s.s_id FROM pm_news_structure ns LEFT JOIN ".$this->cms->tables["structure"]." s ON (s.s_id=ns.s_id AND s.enabled=1) LEFT JOIN ".$this->cms->tables["toc"]." toc ON (toc.s_id=s.s_id AND toc.enabled=1 AND ns.toc_id=toc.toc_id) WHERE ns.rubrics LIKE '%|".(int)$rubrikId."|%' AND s.s_id<>'".$this->page_info["structure"]["s_id"]."' AND s.level>1 AND ns.id=ns.block_id AND ns.toc_id=toc.toc_id";
//				echo $sql."<br>";die();
				$resultS = $this->dbc->Execute($sql);
				if ($resultS->RecordCount()!=0) {
					$item = $resultS->FetchRow();
					$structure = $this->cms->get_structure_item($item['s_id']);
					return $structure;
				}
			}
		}
		return '';
	}

	function prepareVar(&$send) {
		foreach($send as $key => $val) {
			if(is_array($val)) {
				$val = $this->prepareVar($val);
			}
			else {
				$val = strip_tags($val);
				$val = htmlspecialchars($val);
				$val = nl2br($val);
				$send[$key] = $val;
			}
		}
	}
	function getUrl($handler, $tpl_alias='') {
		$sql = "SELECT s.relative_url FROM ".$this->cms->tables["structure"]." s LEFT JOIN ".$this->cms->tables["toc"]." t ON t.s_id=s.s_id WHERE t.handler='".$handler."' AND t.enabled='1' AND s.enabled=1".(($tpl_alias!='')?" AND tpl_alias='".$tpl_alias."'":" AND (t.tpl_alias='' OR t.tpl_alias='default')")." AND s.language_id='".$this->page_info["language_id"]."' LIMIT 0,1";
//		echo $sql."<br>";
		$result = $this->dbc->Execute($sql);
		if ($result->RecordCount()>0) {
			$item = $result->FetchRow();
			return str_replace("/ru/", "/", ROOT_URL.$item["relative_url"]);
		}
		return '';
	}
	function initTables() {
		$pathToTable = cms_PLUGINS_PATH.'cloud_manage/tables.php';
		if(file_exists($pathToTable)) {
			$requiredFiles = get_required_files();
			if(!in_array($pathToTable, $requiredFiles)) {
				include($pathToTable);
				$this->cms->tables = array_merge(@$this->tables, $this->cms->tables);
			}
		}
	}

}

?>