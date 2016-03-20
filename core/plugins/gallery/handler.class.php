<?php
class gallery_handler
{
    var $email = array('');

    var $plugin = array();
    var $config = array();
    var $dbc;
    var $cms = '';
    var $lang = 'ru';

    var $possibleGETVars = array("page");
    var $GETVar = array();

    var $where = array();
    var $countItemInPage = 10;
    var $page = 1;
    var $totalItem = 0;

    var $tpl = '';

    var $now_id = 0;
    var $section_id = 0;

    var $startUrl = '';

    function exec($toc)
    {

        $this->initTables();
        $this->parseGetVar();

        $block = array();

        $this->setCountItemInPage();

        $this->deleteOldInfo();
        require_once(cms_LIB_PATH . 'protection_image.php');

        $this->setSection($toc["toc_id"], $toc["s_id"]);
        if ($this->section_id == 0) {
            return false;
        }

        $this->now_id = $this->section_id;

        $this->startUrl = $this->getUrl('gallery');
        if ($this->startUrl == '') {
            return false;
        }

        $this->lang = $this->page_info["lang"];

        if ($this->page_info["virtuals"][0] != "") {
            $this->get_id_element_by_virtuals();
        }

        $this->setWhere();

        $block["item"] = $this->getItem($this->now_id);
        if (!empty($block["item"]["gallery_id"])) {
            $block["back_url"] = $this->page_info["structure"]["url"] . substr(str_replace($block["item"]["alias"] . "/", "", $block["item"]["relative_url"]), 1);

            $this->page_info["title"] = $block["item"]["title"];
            $this->setPath($block["item"]["gallery_id"]);

            $this->setMeta($block["item"]);

            $this->setPrevNext($block);
        } else {
            $block["back_url"] = $this->page_info["structure"]["url"];
        }
        $block['protect_img'] = ROOT_URL . 'protect_image.php';
        $error = 0;
        if (!empty($this->cms->page_info['user_data']['uid'])) {
            $uid = $this->cms->page_info['user_data']['uid'];
            $block["user_data"] = $this->getUserInfo($this->page_info["user_data"]["uid"]);
            if (isset($_POST["code"])) {
                $protection_image = new protection_image;
                if ($protection_image->check_code($_POST['code'])) {
                    // ���������� ����������� � ������������ ���������������. 01-02-2010 Pavlo Lisovyi
                    $sql = "INSERT INTO " . $this->cms->tables['gallery_comments'] . "  (user_id, time, comment, gallery_id, parent_id) VALUES ('" . $uid . "', NOW(), '" . addslashes(trim($_POST['comment'])) . "', " . $block['item']['gallery_id'] . ", '" . $_POST['parent-comment'] . "')";
                    $this->dbc->Execute($sql);
                    $this->sendMail();
                    // var_dump($this->page_info["structure"]);
                    //$item_path = str_replace("ru/", "", end($this->page_info["structure"]["url"]));
                    //header('Location: '.$item_path["url"]);
                } else $error = 1;
            }
            if (isset($_GET["del"]) && $this->cms->page_info['user_data']['access_level'] >= 50) {
                $sql = "DELETE FROM " . $this->cms->tables['gallery_comments'] . " WHERE comment_id = '" . addslashes(trim($_GET["del"])) . "'";
                $this->dbc->Execute($sql);
                $item_path = str_replace("ru/", "", end($this->page_info["path"]));
                header('Location: ' . $item_path["url"]);

            }
        } else {
            $uid = 0;
        }
        $block['error'] = $error;
        $sql = "SELECT c.*, u.name FROM " . $this->cms->tables['gallery_comments'] . " as c, " . $this->cms->tables['users'] . " as u WHERE u.uid = c.user_id AND c.gallery_id = " . $block['item']['gallery_id'];
        $result = $this->dbc->Execute($sql);
        $block['commentsCount'] = 0;
        while ($row = $result->FetchRow()) {
            $block['comments'][] = $row;
            $block['commentsCount']++;
        }

        $this->totalItem = $this->getTotalItem();
        $this->setPage();
        $this->setNavi($block);

        $block["sections"] = $this->getItems($block, 0);
        $block["countSections"] = count($block["sections"]);
        if ($block["countSections"] == 0) {
            $block["items"] = $this->getItems($block, 1);
            $block["countItems"] = count($block["items"]);
        }

        $this->tpl = $toc["tpl_alias"];

        if (empty($toc['tpl_alias'])) {
            $toc['tpl_alias'] = 'default';
        }


        $block["pathTemplate"] = $tpl_file = $this->plugin['path'] . 'tpls/';

        $tpl_file = $this->plugin['path'] . 'tpls/' . $toc['tpl_alias'] . '.tpl';
        if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
            $this->cms->smarty->assign(array('output' => $block));
            $output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);
        } else {
            $output = false;
        }

//		echo"<pre>"; print_r($block); echo"</pre>"; 
        return $output;
    }

//	��������� ������� ��� ����������� ������
    function setWhere()
    {
        $this->where[] = "enabled=1";
        $this->where[] = "parent_id='" . $this->now_id . "'";
    }

    // ������� ��������� �������� ����� ����� �� ���������� �������
    function apply_current_language($data)
    {

        if (is_array($data)) {

            // �� ������� �������� �� ���� �����
            $record = array();
            foreach ($data as $field_id => $field) {

                // ������� �������� ������ ����� ����
                $field_lang = substr($field_id, -3);
                if (substr($field_lang, 0, 1) == "_") {
                    $field_lang = substr($field_lang, 1);
                } else {
                    $field_lang = "";
                }

                // ���� ��� ���� �������� �����, �� ��������� ��� � ������
                if ($field_lang == $this->lang) {
                    // ������������ ����� ������������� ����
                    $new_field_id = substr($field_id, 0, -3);
                    // ��������� �������� ���� � ������
                    $record[$new_field_id] = $field;
                }
                // ���� ���� �� ������� �� �����, �� ������ �������� ���
                $record[$field_id] = $field;
            }
            $data = $record;
        }

        return $data;
    }

//	������� ��������� �������� �������
    function getItems($block, $type = 0)
    {
        $ret = array();

        $limitSql = " LIMIT " . ($this->page - 1) * $this->countItemInPage . ", " . $this->countItemInPage;

        $sql = "SELECT * FROM " . $this->cms->tables["gallery"] . " WHERE type='" . $type . "'";
        if (count($this->where) > 0) {
            $sql .= " AND " . implode(" AND ", $this->where);
        }
        $sql .= " ORDER BY ord DESC, created ASC, BINARY(title_" . $this->lang . ")" . $limitSql;
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) return $ret;
        else {
            while ($item = $result->FetchRow()) {
                $item = $this->apply_current_language($item);
                $item["url"] = $this->startUrl . substr($item["relative_url"], 1);
                $ret[] = $item;
            }
        }

        return $ret;
    }

//	��������� ������ �� ���������� � ��������� ��������
    function setPrevNext(&$block)
    {
        $next_url = '';
        $next_link = '';
        $prev_url = '';
        $findNow = false;
//		$sql = "SELECT * FROM ".$this->cms->tables["gallery"]." WHERE type=1 AND enabled=1 AND parent_id='".$block["item"]["parent_id"]."'";
        $sql = "SELECT * FROM " . $this->cms->tables["gallery"] . " WHERE type=" . $block["item"]["type"] . " AND enabled=1 AND parent_id='" . $block["item"]["parent_id"] . "'";
        $sql .= " ORDER BY ord DESC, created ASC, BINARY(title_" . $this->lang . ")";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) return false;
        else {
            while ($item = $result->FetchRow()) {
                if ($findNow === true) {
                    $block["next_url"] = substr($item["relative_url"], 1);
                    $findNow = false;
                    break;
                }
                if ($item["gallery_id"] == $block["item"]["gallery_id"]) {
                    if (isset($prev_url)) {
                        $block["prev_url"] = $prev_url;
                    }
                    $findNow = true;
                }
                $prev_url = substr($item["relative_url"], 1);
            }
        }
    }

//	������� ������ ��������
    function getItem($id)
    {
        $ret = array();
        $sql = "SELECT * FROM " . $this->cms->tables["gallery"] . " WHERE gallery_id='" . (int)$id . "' AND enabled=1";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) return $ret;
        else {
            $item = $result->FetchRow();
            $item["url"] = $this->startUrl . substr($item["relative_url"], 1);
            $item = $this->apply_current_language($item);
            $ret = $item;
            $sql = "UPDATE " . $this->cms->tables["gallery"] . " SET visited = (visited + 1) WHERE gallery_id='" . (int)$id . "' AND enabled=1";
            $result = $this->dbc->Execute($sql);
        }

        return $ret;
    }

    function setMeta($item)
    {
        if ($item["parent_id"] > 0) {
            $this->cms->page_info["meta_title"] = $item["meta_title"];
            $this->page_info["structure"]["keywords"] = $item["meta_keywords"];
            $this->page_info["structure"]["description"] = $item["meta_description"];
        }
    }

    function setPath($id)
    {
        $item = $this->getItem($id);
        if (!empty($item["parent_id"])) {
            $this->setPath($item["parent_id"]);
            $this->page_info["path"][] = array("s_id" => "l" . $id, "title" => $item["title"], "meta_title" => $item["meta_title"], "url" => $item["url"]);
        }
    }

//	���������� ����� ���-�� ���������
    function getTotalItem()
    {
        $ret = 0;
        //$this->setWhere();
        $sqlWhere = '';
        if (count($this->where) > 0) {
            $sqlWhere .= " WHERE " . join(" AND ", $this->where) . "";
        }

        $sql = "SELECT COUNT(*) as counts FROM " . $this->cms->tables["gallery"] . "" . $sqlWhere . "";

        //die($sql);
        $result = $this->dbc->Execute($sql);
        if ($result) {
            $item = $result->FetchRow();
            $ret = $item["counts"];
        }
        return $ret;
    }

//	������������� ������� ��������
    function setPage()
    {
        if (!empty($this->GETVar["page"])) {
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

//	������� ������ ������������ ���������
    function setNavi(&$block)
    {
        $arrGet = array();
//		$startPath = $this->page_info["structure"]["url"];
        if (!empty($block["item"]["gallery_id"])) {
            $startPath = $block["item"]["url"];
        } else {
            $startPath = $this->page_info["structure"]["url"];
        }

        if (count($this->GETVar) > 0) {
            foreach ($this->GETVar as $key => $value) {
                if ($key != "page") {
                    $startPath .= $key . "_" . $value . "/";
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

        // 404 check
        if ($this->page > $this->totalPage) {
            $this->cms->vars_404['gallery_album'] = 'false';
        }

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

//	������� ��������� URL �� ��������� ������
    function getUrl($handler, $tpl = '', $is_set_alias = '')
    {
        $ret = '';
        $sql = "SELECT * FROM " . $this->cms->tables["gallery_structure"] . " WHERE record_id='" . $this->section_id . "' AND open_this=1";
        $resultSearch = $this->dbc->Execute($sql);
        if ($resultSearch && $resultSearch->RecordCount() > 0) {
            while ($itemSearch = $resultSearch->FetchRow()) {
                $sql = "SELECT s.relative_url FROM " . $this->cms->tables["structure"] . " s LEFT JOIN " . $this->cms->tables["toc"] . " t ON t.s_id = s.s_id WHERE t.handler = '" . $handler . "' AND  t.enabled = '1' AND s.enabled = 1 AND s.language_id='" . $this->page_info["language_id"] . "'" . (($tpl != '') ? " AND t.tpl_alias='" . $tpl . "'" : " AND (t.tpl_alias='default' OR t.tpl_alias='')") . "" . (($is_set_alias != '') ? " AND s.relative_url LIKE '%" . $is_set_alias . "%'" : "") . " AND t.toc_id='" . $itemSearch['toc_id'] . "' AND t.s_id='" . $itemSearch['s_id'] . "'";
                $result = $this->dbc->Execute($sql);
                if ($result && $result->RecordCount() > 0) {
                    while ($item = $result->FetchRow()) {
                        $ret = ROOT_URL . $item['relative_url'];
                    }
                }
            }
        }
        return $ret;
    }

//	������������� ���������� ��������� ��������� �� ��������
    function setCountItemInPage()
    {
        $sql = "SELECT * FROM " . $this->cms->tables["gallery_options"] . " WHERE name='countItemInPage'";
        $result = $this->dbc->Execute($sql);

        if ($result && $result->RecordCount() > 0) {
            $row = $result->FetchRow();
            $this->countItemInPage = $row["value"];
        }
    }

    // Returns element depending on it's virtuals and checks for 404
    function get_id_element_by_virtuals()
    {
        $ret = 0;

        $arr_path = array();

        foreach ($this->page_info["virtuals"] as $virt) {
            $relative_url = '/';

            if (isset($arr_path[count($arr_path) - 1])) {
                $relative_url = $arr_path[count($arr_path) - 1];
            }

            $relative_url .= $virt . "/";
            $arr_path[] = $relative_url;
        }

//        Array $arr_path
//        (
//            [0] => /photo_tibet/
//            [1] => /photo_tibet/Tibet_Lamayuru/
//            [2] => /photo_tibet/Tibet_Lamayuru/page_2/
//        )


        $count_item = count($arr_path) - 1;
        // print_r = 2

        for ($i = $count_item; $i >= 0; $i--) {

            $sql = "SELECT gallery_id, relative_url FROM " . $this->cms->tables["gallery"] . " WHERE relative_url='" . addslashes($arr_path[$i]) . "'";
			//echo $sql."<br>";
            //die();
            $result = $this->dbc->Execute($sql);

            if ($result && $result->RecordCount() > 0 && $ret == '') {
                $record = $result->FetchRow();
                $ret = $record["gallery_id"];

                $rel_origin = $record["relative_url"];

                if ($relative_url == $rel_origin) {
                    $this->cms->vars_404['phototour'] = 'true';
                }
            }

        }

        if ($ret > 0) {
            $this->now_id = $ret;
        }

        // OK test for 404

        $virtuals = $this->page_info["virtuals"];

        // I take the last virtual, definitely it is something like "page_2"
        $last_virtual = $virtuals[count($virtuals)-1];

        // I explode it by "_" and work separately with its parts
        $test_subject = explode("_", $last_virtual);

        // It is not 404 if the first one is "page" and the second is numeric
        if ( ($test_subject[0] == "page")&&(is_numeric($test_subject[1])) ) {
            $this->cms->vars_404['gallery_album'] = 'true';
        }

        $this->totalPage = $this->totalItem / $this->countItemInPage;
        //print_r($this->getTotalItem());
        //die();



//      $this->cms->vars_404['phototour'] = 'true';
//		echo "<pre>"; print_r($arr_path); echo "</pre>";
//		echo $ret."<br>";
//		die();
    }

//	���������� ����� ������ ������ ��������� �� ������ ��������
    function setSection($toc_id, $s_id)
    {
        $sql = "SELECT record_id FROM " . $this->cms->tables["gallery_structure"] . " WHERE toc_id='" . (int)$toc_id . "' AND s_id='" . (int)$s_id . "'";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            $record = $result->FetchRow();
            $this->section_id = $record["record_id"];
        }
    }

    function deleteOldInfo()
    {
        if (date("H", time()) > 20) {
            $sql = "DELETE FROM " . $this->cms->tables["gallery_structure"] . " WHERE toc_id NOT IN (SELECT toc_id FROM " . $this->cms->tables["toc"] . " WHERE handler='gallery')";
            $this->dbc->Execute($sql);
        }
    }

    function initTables()
    {
        $pathToTable = cms_PLUGINS_PATH . 'gallery_manage/tables.php';
        if (file_exists($pathToTable)) {
            include($pathToTable);
            if (isset($this->tables)) {
                $this->cms->tables = array_merge($this->tables, $this->cms->tables);
            }
        }
    }

    function prepareVar(&$send)
    {
        foreach ($send as $key => $val) {
            if (is_array($val)) {
                $val = $this->prepareVar($val);
            } else {
                $val = strip_tags($val);
                $val = htmlspecialchars($val);
                $send[$key] = $val;
            }
        }
    }

//	������ ���������� �� ����
    function parseGetVar()
    {
        $name = '';
        foreach ($this->page_info["virtuals"] as $val) {
            if (strstr($val, "_")) {
                $varArr = explode("_", $val, 2);
                if (in_array($varArr[0], $this->possibleGETVars)) {
                    $this->GETVar[$varArr[0]] = $varArr[1];
                }
            }
        }
    }

    function getUserInfo($uid)
    {
        $ret = array();
        if ($uid < 1) return $ret;
        $sql = "SELECT * FROM " . $this->cms->tables["users"] . " WHERE uid='" . (int)$uid . "' AND valid_account=1";
        //echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) return $ret;
        else {
            $ret = $result->FetchRow(); //date("d.m.Y H:i", strtotime($item['dates']))
        }
        return $ret;
    }

    function sendMail()
    {
        $data["site"] = str_replace(array("http://", "www."), array("", ""), substr(ROOT_URL, 0, -1));
        //	$data["dates"] = date('d.m.Y H:h:s');
        require_once(cms_LIB_PATH . 'mail_event.class.php');
        require_once(cms_LIB_PATH . 'email_message.class.php');
        require_once(cms_LIB_PATH . 'strcom_parser.class.php');
        require_once(cms_LIB_PATH . "validate.php");
        $mail_event = & new mail_event($this->plugin['path'] . 'tpls/mail/', '.tpl');
        $data['comment'] = trim($_POST['comment']);
        $item_path = str_replace("ru/", "", end($this->page_info["path"]));
        $item_path = preg_replace("/nid\/(\d+\/$)/", "nid_$1", $item_path["url"]);
        $data['url'] = $item_path;
        $data['name'] = $this->page_info["user_data"]["name"];
        foreach ($this->email as $mail) {
            $data['email'] = $mail;
            $result = $mail_event->event('send', $data);
        }
    }


}

?>
