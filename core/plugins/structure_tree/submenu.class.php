<?php

class sa_submenu {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms_ai   = '';
    var $_cookie_sep = '-';
    var $top;
    var $language;
    var $output = '';

    var $structure_types = array (
        0 => 'Страница',
        1 => 'Папка',
        2 => 'Редирект'
    );


    var $expanded;
    var $collapsed;

    function init() {
        if (!empty($_REQUEST['arg'])) $arguments = $_REQUEST['arg'];
        if (!isset($arguments['s_id']) || ($arguments['s_id'] == 0)) {
            $this->getTop();
        }
        else {
            $this->getTopFromId($arguments['s_id']);
        }
    }
    function main() {
        $this->dbc = &$this->cms->dbc;
        $this->init();
//        $output = $this->_print_structure(0, 10);
        $this->output = $this->showLanguage();
        $this->showStructure();
        return $this->output;
    }
    function getTop() {
        $sql = "SELECT s_id, language_id FROM ".$this->cms->tables['structure']." WHERE parent=0 ORDER BY ord ASC LIMIT 0,1";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount()==0) {
            return false;
        }
        else {
            $item = $result->FetchRow();
            $this->top = $item["s_id"];
            $this->language = $item["language_id"];
        }
    }
    function showLanguage() {
        if (!empty($_REQUEST['arg'])) $arguments = $_REQUEST['arg'];
        if (!isset($arguments['s_id'])) $s_id = $this->top;
        else $s_id = $arguments['s_id'];

        $output = '';
//        $expanded      = $this->_get_expanded_nodes();
//        $collapsed     = $this->_get_collapsed_nodes();
//        $volume_limit = 20;

        $structure_cache = array();
        $lang_item = array();


        $sql = "SELECT s.*, l.short_title FROM ".$this->cms->tables["structure"]." s INNER JOIN ".$this->cms->tables["languages"]." l ON l.language_id=s.language_id WHERE level=1 AND parent=0 ORDER BY l.ord ASC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
            return false;
        }
        elseif ($result->RecordCount() == 0) {
            return false;
        }
        elseif ($result->RecordCount() > 0) {
            while($structure = $result->FetchRow()) {
                $structure_title = $structure['alias'];
                $structure_alt = $structure['title'];
                $edit_url = $this->cms->format_url('sa', 'index', array('s_id'=>$structure['s_id']));
                $topFromNow = $this->getTopFromId($s_id);
                $lang_item[] = array('title'=>$structure_title, 'alt'=>$structure_alt, 'edit_url'=>$edit_url, 'current' => ($topFromNow==$structure['s_id'])?1:0);
            }
            $output .= '<div class="lang"><div class="cont">';
            foreach ($lang_item as $key=>$val) {
                if ($val['current'] == 1)
                    $output .= '<a href="'.$val['edit_url'].'" title="'.$val['alt'].'" class="a">'.$val['title'].'</a>';
                else
                    $output .= '<a href="'.$val['edit_url'].'" title="'.$val['alt'].'">'.$val['title'].'</a>';
            }
            $output .= '<a href="'.$this->cms->format_url('languages', 'formItem', array()).'" class="add" title="Добавить языковую версию">[+]</a></div></div>';
        }
        return $output;
    }
    function getTopFromId($id) {
        $sql = "SELECT parent FROM ".$this->cms->tables["structure"]." WHERE s_id='".$id."'";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        if($row["parent"]) return $this->getTopFromId($row["parent"]);
        else {
            $this->top = $id;
            $sql = "SELECT language_id FROM ".$this->cms->tables["structure"]." WHERE s_id='".$id."'";
            $result = $this->dbc->Execute($sql);
            $row = $result->FetchRow();
            $this->language = $row["language_id"];
            return $id;
        }
    }
    function showStructure() {
        if (!empty($_REQUEST['arg'])) $arguments = $_REQUEST['arg'];
        if (!isset($arguments['s_id'])) $s_id = $this->top;
        else $s_id = $arguments['s_id'];

        $this->expanded = $this->_get_expanded_nodes();
        $this->collapsed = $this->_get_collapsed_nodes();

//echo "<prE>expandedSub<br>"; print_r($this->expanded); echo "</pre>";
//echo "<prE>collapsedSub<br>"; print_r($this->collapsed); echo "</pre>";

        $this->output .= '<div class="str-mini str-mini-bg">';
//        $items = $this->getStructure($this->top);
        $items = $this->getStructure(0);
        $this->output .= '</div>';
    }
    function getStructure($id) {
        if (!empty($_REQUEST['arg'])) $arguments = $_REQUEST['arg'];
//        if (!$id) $id = $this->top;
        $items = array();
//        echo $this->top." ".$this->language;
        $sql = "SELECT * FROM ".$this->cms->tables["structure"]." WHERE language_id='".$this->language."' AND parent='".$id."' ORDER BY title ASC";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        while($structure = $result->FetchRow()) {
            if(!empty($structure['title_menu'])) {
                $titleStructure = str_replace('"', '&quot;', strip_tags($structure['title_menu']));
            }
            else {
                $titleStructure = str_replace('"', '&quot;', strip_tags($structure['title']));
            }

            if (strlen($titleStructure) > 28) {
                $structure_title = substr($titleStructure, 0, 25).'...';
                $structure_alt = $titleStructure.".\nАлиас: ".$structure['alias'];
            }
            else {
                $structure_title = $titleStructure;
                $structure_alt = 'Алиас: '.$structure['alias'];
            }

            $prefix = '<img src="/admin/img/px.gif">';
            $suffix = '';
            $class = '';
            switch ($structure['type']) {
                case 0:  // page 
                    break;

                case 1:  // folder 
                    $class = ' f';
                    break;

                default:
                case 2:  // redirect 
                    $suffix = '&nbsp;<img src="/admin/img/redir_sm.gif">';
                    break;
            }
            $class .= (isset($arguments['s_id']) && $arguments['s_id'] == $structure['s_id']) ? ' a' : '';
            $edit_url = $this->cms->format_url('sim', 'man', array('s_id'=>$structure['s_id']));
            $modify_link = $prefix.'<a href="'.$edit_url.'" title="'.$structure_alt.'">'.$structure_title.$suffix.'</a>';
            $this->output .= '<div class="l'.$structure['level'].$class.'">'.$modify_link.'</div>';
            if(in_array($structure['s_id'], $this->expanded) || $this->top==$structure['s_id']) $this->getStructure($structure['s_id']);
        }
    }





//************************************************************************************************************
    function _get_expanded_nodes(){
        $nodes = array();
        if (!empty($_COOKIE['pm_expanded'])) {
            $nodes = explode($this->_cookie_sep, $_COOKIE['pm_expanded']);
        }
        $nodes = array_unique($nodes);
        $nodes = array_filter($nodes, '_check_sid');
        if (empty($nodes[0])) {
            unset($nodes[0]);
        }
        return $nodes;
    }

    function _get_collapsed_nodes(){
        $nodes = array();
        if (!empty($_COOKIE['pm_collapsed'])) {
            $nodes = explode($this->_cookie_sep, $_COOKIE['pm_collapsed']);
        }
        $nodes = array_unique($nodes);
        $nodes = array_filter($nodes, '_check_sid');
        if (empty($nodes[0])) {
            unset($nodes[0]);
        }
        return $nodes;
    }
    function _print_structure($parent = 0, $depth_limit = 2) {

        /*        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
        //        echo $query."<br>";
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
                elseif ($result->RecordCount() == 1) {
                    $item = $result->FetchRow();
                    $level = $item['level'];
                    $left_key = $item['left_key'];
                    $right_key = $item['right_key'];
                    if ($level > 0) {
                        $query = 'SELECT left_key, right_key, level FROM '.$this->cms->tables['structure'].' WHERE level=1 AND left_key < '.$left_key.' AND right_key > '.$right_key;
        //        echo $query."<br>";
                        $result = $this->dbc->Execute($query);
                        if (!$result) {
                            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                            return false;
                        }
                        elseif ($result->RecordCount() == 1) {
                            $item = $result->FetchRow();
                            $level = $item['level'];
                            $left_key = $item['left_key'];
                            $right_key = $item['right_key'];
                        }
                    }
                }*/

        /*        $query = 'SELECT MAX(right_key) as max_right FROM '.$this->cms->tables['structure'].'';
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
                $max_row = $result->FetchRow();
                $structure_cache = array(0=>array('right_key'=>$max_row['max_right']+1));
        */
        /*        $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE level=1 AND left_key > 0 ORDER BY left_key';
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
                elseif ($result->RecordCount() == 0) {
                    return false;
                }
                elseif ($result->RecordCount() > 0) {
                    while($structure = $result->FetchRow()) {
                        if ($structure['type'] > 5 || empty($structure_cache[$structure['parent']])) {
                            continue;
                        }
                        if (($structure['right_key']-$structure['left_key']>$volume_limit) and !in_array($structure['s_id'], $expanded)) {
                            $collapsed[] = $structure['s_id'];
                        }
                        else {
                            $structure_cache[$structure['s_id']] = $structure;
                        }
                        $structure_title = substr($structure['alias'], 0, 2);
                        $structure_alt = $structure['title'];
                        $edit_url = $this->cms->format_url('sa', 'index', array('s_id'=>$structure['s_id']));
                        $current = $this->is_child($s_id, $structure['s_id']) ? 1 : 0;
                        $lang_item[] = array('title'=>$structure_title, 'alt'=>$structure_alt, 'edit_url'=>$edit_url, 'current' => $current);
                    }
                    $output .= '<div class="lang"><div class="cont">';
                    foreach ($lang_item as $key=>$val) {
                        if ($val['current'] == 1)
                            $output .= '<a href="'.$val['edit_url'].'" title="'.$val['alt'].'" class="a">'.$val['title'].'</a>';
                        else
                            $output .= '<a href="'.$val['edit_url'].'" title="'.$val['alt'].'">'.$val['title'].'</a>';
                    }
                    $output .= '</div></div>';
                }

        /**/
        /*
                if (is_array($collapsed) && count($collapsed)>0) {
                    $coll_condition = 'AND parent NOT IN ('.implode(',', $collapsed).')';
                }
                else {
                    $coll_condition = '';
                }

                if ($s_id > 0) {
                    $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE level<'.($depth_limit+1).' '.$coll_condition.' AND left_key > '.$left_key.' AND right_key < '.$right_key.' ORDER BY left_key';
                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                        return $output;
                    }
                    elseif ($result->RecordCount() == 0) {
                        return $output;
                    }
                    elseif ($result->RecordCount() > 0) {
                        $output .= '<div class="str-mini str-mini-bg">';
                        while($structure = $result->FetchRow()) {
                            if ($structure['type'] > 5 || empty($structure_cache[$structure['parent']])) {
                                continue;
                            }

                            if (($structure['right_key']-$structure['left_key']>$volume_limit) and !in_array($structure['s_id'], $expanded)) {
                                $collapsed[] = $structure['s_id'];
                            }
                            else {
                                $structure_cache[$structure['s_id']] = $structure;
                            }

                            if (strlen($structure['title']) > 28) {
                                $structure_title = substr($structure['title'], 0, 25).'...';
                                $structure_alt = $structure['title'].".\nАлиас:&nbsp;".$structure['alias'];
                            }
                            else {
                                $structure_title = $structure['title'];
                                $structure_alt = 'Алиас:&nbsp;'.$structure['alias'];
                            }

                            $prefix = '<img src="/admin/img/px.gif">';
                            $suffix = '';
                            $class = '';
                            switch ($structure['type']) {
                                case 0:  // page
                                    break;

                                case 1:  // folder
                                    $class = ' f';
                                    break;

                                default:
                                case 2:  // redirect
                                    $suffix = '&nbsp;<img src="/admin/img/redir_sm.gif">';
                                    break;
                            }
                            $class .= $s_id == $structure['s_id'] ? ' a' : '';
                            $edit_url = $this->cms->format_url('sim', 'man', array('s_id'=>$structure['s_id']));
                            $modify_link = $prefix.'<a href="'.$edit_url.'" title="'.$structure_alt.'">'.$structure_title.$suffix.'</a>';
                            $output .= '<div class="l'.$structure['level'].$class.'">'.$modify_link.'</div>';
                        }
                        $output .= '</div>';
                    }

                }
                */
        return $output;
    }
    /*
        function is_child($s_id, $parent) {
            if ($s_id > 0) {
                $item = $this->cms->get_structure_item($s_id);
                $query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$parent.' AND left_key<='.$item['left_key'].' AND right_key>='.$item['right_key'];
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
                elseif ($result->RecordCount() != 1) {
                    return false;
                }
                else {
                    return true;
                }
            }
            return false;
        }
    /**/
}

?>