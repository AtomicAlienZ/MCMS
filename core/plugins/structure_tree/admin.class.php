<?php

require_once(cms_LIB_PATH . 'cms_structure.class.php');

class plugin_admin_interface extends cms_structure
{
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms = '';
    var $templates = array();
    var $_cookie_sep = '-';

    var $top;
    var $language;

    var $rows;
    var $expanded;
    var $exp_condition;
    var $collapsed;
    var $coll_condition;
    var $volume_limit;
    var $elementsStructrure = array();

    var $structureFromSelect = array();

    var $leftKey = 1;
    var $rightKey;
    var $js;

//	var $top = 0;
//	var $language = 0;


    var $structure_types = array(
        0 => 'Страница',
        1 => 'Папка',
        2 => 'Перенаправление'
    );

    function init()
    {
        require_once(cms_LIB_PATH . 'form_builder.class.php');
        $this->cms->init_dbc();
        $this->dbc = &$this->cms->dbc;
        $this->templates = $this->_get_templates();
    }

    function main($command, $arguments)
    {
        $this->init();

        if (!isset($arguments['s_id']) || ($arguments['s_id'] == 0)) {
            $parent = $this->getFirstLanguage();
            $this->top = $parent["s_id"];
            $this->language = $parent["language_id"];
        } else {
            $this->getTopFromId($arguments['s_id']);
        }

        $this->cms->int_set_h1($this->plugin['title']);
        if (method_exists($this, $command)) {
            $result = $this->$command($arguments);
            return $result;
        }

        switch ($command) {
            default:
            case 'index':
                $result = $this->index($arguments);
                break;

            case 'direct':
                $result = $this->direct($arguments);
                break;

            case 'del':
                $result = $this->delete($arguments);
                break;

            case 'switch':
                $result = $this->switch_state($arguments);
                break;

            case 'add':
                $result = $this->add($arguments);
                break;

            case 'search':
                $result = $this->search($arguments);
                break;

            case 'exp':
                $result = $this->expand_node($arguments);
                break;

// old 
            case 'i_up':
                $result = $this->move_up($arguments);
                break;

            case 'i_down':
                $result = $this->move_down($arguments);
                break;
        }
        return $result;
    }

    function init_fob($form_name, $form_action)
    {
        $fob = &new form_builder($form_name, $form_action, 'post', '', '', '', 'multipart/form-data');
        return $fob;
    }

    function _get_templates()
    {
        $query = 'SELECT template_id, title FROM ' . $this->cms->tables['templates'] . ' ORDER BY title, tpl_alias';
        $result = $this->dbc->Execute($query);
        $templates = array();
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else {
            while ($row = $result->FetchRow()) {
                $templates[$row['template_id']] = $row['title'];
            }
        }
        return $templates;
    }

    function _get_template($id)
    {
        $query = 'SELECT * FROM ' . $this->cms->tables['templates'] . ' WHERE template_id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() == 0) {
            return false;
        }
        $template = $result->FetchRow();
        return $template;
    }

    function _add_iframe($src)
    {
//		$iframe = '<iframe frameborder="0" name="exec" id="exec" scrolling="No" src="'.$src.'" class="str-check"></iframe>';
        $iframe = '<iframe frameborder="0" name="exec" id="exec" scrolling="No" src="" class="str-check" style="visibility:hidden; display:none; width=0px; height=0px;" APPLICATION="yes" frameborder="0"></iframe>';
        return $iframe;
    }

    function index($arguments)
    {
        if (!isset($arguments['s_id']) || ($arguments['s_id'] == 0)) $parent = $this->top;
        else $parent = $arguments['s_id'];

        $this->default_expanded_nodes($parent, 1);

        $parent = $this->check_id($parent);
        $output = '';
        $output .= $this->_print_tabs('tree', '', $parent);
        $output .= $this->_print_structure_header('tree');
        $this->getAllElementsStructure();
        $output .= $this->_print_structure($parent, $this->config['view_options']['depth_limit']);
        $output .= $this->_print_structure_footer();
        return $output;
    }

    function getAllElementsStructure()
    {
        if (!$this->top) {
            $parent = $this->getFirstLanguage();
            $this->top = $parent["s_id"];
            $this->language = $parent["language_id"];
        }
        $sql = "SELECT * FROM " . $this->cms->tables['structure'] . " WHERE language_id='" . $this->language . "' ORDER BY title ASC";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {
                $item["childs"] = array();
                $item["url"] = ROOT_URL . $item["relative_url"];
                if ($item["parent"]) $this->elementsStructrure[$item["parent"]]["childs"][] = $item["s_id"];
                if (isset($this->elementsStructrure[$item["s_id"]]) && count($this->elementsStructrure[$item["s_id"]]["childs"])) {
                    $temp = $this->elementsStructrure[$item["s_id"]]["childs"];
                    $this->elementsStructrure[$item["s_id"]] = $item;
                    foreach ($temp as $value) $this->elementsStructrure[$item["s_id"]]["childs"][] = $value;
                } else {
                    $this->elementsStructrure[$item["s_id"]] = $item;
                }
            }
        }
//		echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";
    }

    function getFirstLanguage()
    {
        $sql = "SELECT s_id, language_id FROM " . $this->cms->tables['structure'] . " WHERE parent=0 ORDER BY ord ASC LIMIT 0,1";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) {
            return false;
        } else {
            $item = $result->FetchRow();
            return array("s_id" => $item["s_id"], "language_id" => $item["language_id"]);
        }
    }

    function getTopFromId($id)
    {
        $sql = "SELECT parent FROM " . $this->cms->tables["structure"] . " WHERE s_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        if ($row["parent"]) return $this->getTopFromId($row["parent"]);
        else {
            $this->top = $id;
            $sql = "SELECT language_id FROM " . $this->cms->tables["structure"] . " WHERE s_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            $row = $result->FetchRow();
            $this->language = $row["language_id"];
            return $id;
        }
    }

    function _print_tabs($active_tab = 'tree', $search_query = '', $parent = 0)
    {
//		echo "<pre>"; print_r(get_object_vars($this)); echo "</pre>";
        $output = '';
        $tabs = array(
            'tree' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent)) . '">Карта сайта</a>',
            'add_page' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $parent, 'type' => 'page')) . '">[+] добавить новую страницу</a>',
//			'add_lang'  => '<a href="'.$this->cms->format_url($this->plugin['name'], 'add', array('s_id'=>$parent, 'type'=>'lang')).'">[+] добавить языковую версию</a>',
        );
        if ($active_tab == 'search') {
            $tabs[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'search', array('s_id' => 0)) . '">Поиск</a>';
        }
        $output .= $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }

    function _print_structure($parent = 0, $depth_limit = 2)
    {
        $this->cms->int_set_hierarchy($this->top);
        $show_condition = '';
        $output = '';
        $this->expanded = $this->_get_expanded_nodes();
        $this->collapsed = $this->_get_collapsed_nodes();
        $this->volume_limit = $this->config['view_options']['volume_limit'];

//echo "<prE>expanded<br>"; print_r($this->expanded); echo "</pre>";
//echo "<prE>collapsed<br>"; print_r($this->collapsed); echo "</pre>";

        $structure_cache = array();

        if (is_array($this->expanded) && count($this->expanded) > 0) {
            $this->exp_condition = 'or parent in (' . implode(',', $this->expanded) . ')';
        } else {
            $this->exp_condition = '';
        }
        if (isset($collapsed) && is_array($collapsed) && count($collapsed) > 0) {
            $this->coll_condition = 'and parent not in (' . implode(',', $collapsed) . ')';
        } else {
            $this->coll_condition = '';
        }
//		echo "<prE>"; print_r($expanded); echo "</prE>";
        if (is_array($this->expanded) && count($this->expanded) > 0) {
//			$this->coll_condition .= '  and parent not in ('.implode(',', $this->expanded).') )';
        }

//		echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";
        $this->getStructureOneLevel($this->top);
        $this->js = '<script type="text/javascript" language="JavaScript1.2">var keys=new Array(); ' . $this->js . '</script>';
//		echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";

//		echo "<prE>"; print_r($this->rows); echo "</prE>";
        if (count($this->rows)) {
            $table = array(
                'js' => $this->js,
                'title' => 'Заголовок',
                'template' => 'Шаблон',
                'actions' => 'Действия',
                'rows' => $this->rows,
            );
            $output .= $this->cms->parse_template($table, 'structure_table');
            $output .= '';
        }
        return $output;
    }

    function getStructureOneLevel($childs = false)
    {
        if (!is_array($childs)) {
            $this->getAttributeElement($this->elementsStructrure[$this->top], 121);
            if (count($this->elementsStructrure[$this->top]["childs"]) > 0)
                $this->getStructureOneLevel($this->elementsStructrure[$this->top]["childs"]);
            $this->elementsStructrure[$this->top]["right"] = $this->leftKey++;
            $this->js .= 'keys[' . $this->top . ']["right"]=' . $this->elementsStructrure[$this->top]["right"] . '; ';
        } else {
//			echo "<pre>"; print_r($childs); echo "</pre>";
            $countChild = count($childs);
            $counter = 0;
            foreach ($childs as $s_id) {
                $act = 121;
                if ($countChild > 1) {
                    if ($counter > 0) $act += 2;
                    if ($counter < $countChild - 1) $act += 4;
                }
//				echo $this->elementsStructrure[$s_id]["title"]." : ".$countChild." ".$counter." ".$act."<hr>";
//				echo $s_id."<hr>";
                $info = $this->elementsStructrure[$s_id];
                $this->getAttributeElement($this->elementsStructrure[$s_id], $act);
                if (in_array($s_id, $this->expanded))
                    $this->getStructureOneLevel($this->elementsStructrure[$s_id]["childs"]);
                $this->elementsStructrure[$s_id]["right"] = $this->leftKey++;
                $this->js .= 'keys[' . $s_id . ']["right"]=' . $this->elementsStructrure[$s_id]["right"] . '; ';
                $counter++;
            }
        }
    }

    function getAttributeElement(&$info, $act = 127)
    {
        if (!isset($info["s_id"])) return;
//		echo "<pre>"; print_r($info); echo "</pre>";
        $node_switch = '';
        $info["left"] = $this->leftKey++;
        if (isset($info['s_id'])) {
            $this->js .= 'keys[' . $info['s_id'] . ']=new Array(); ';
            $this->js .= 'keys[' . $info['s_id'] . ']["left"]=' . $info['left'] . '; ';
        }
        if (isset($info["childs"]) && count($info["childs"]) == 0 || isset($info["parent"]) && $info["parent"] == 0) {
            $node_switch .= '';
        } elseif (isset($info['s_id'])) {
            if (!in_array($info['s_id'], $this->expanded)) {
                $node_switch .= '<a class="cms-list-control cms-list-control__expand" href="' . $this->cms->format_url($this->plugin['name'], 'exp', array('s_id' => $info['s_id'])) . '" onclick="expand_node(' . $info['s_id'] . ');"><img src="/admin/img/plus.gif" width="9" height="9" id="mk' . $info['left'] . '"></a>';
            } else {
                $node_switch .= '<a class="cms-list-control cms-list-control__collapse" href="' . $this->cms->format_url($this->plugin['name'], 'col', array('s_id' => $info['s_id'])) . '" onclick="collapse_node(' . $info['s_id'] . ');"><img src="/admin/img/minus.gif" width="9" height="9" id="mk' . $info['left'] . '"></a>';
            }
        }
        if (!empty($info['title_menu'])) {
            $titleStructure = str_replace('"', '&quot;', strip_tags($info['title_menu']));
        } else {
            $titleStructure = str_replace('"', '&quot;', strip_tags($info['title']));
        }
        if (isset($titleStructure)) {
            if (strlen($titleStructure) > 103) {
                $structure_title = substr($titleStructure, 0, 100) . '...';
                $structure_alt = $info['title'] . ".\nАлиас:&nbsp;" . $info['alias'];
            } else {
                $structure_title = $titleStructure;
                $structure_alt = 'Алиас:&nbsp;' . $info['alias'];
            }
        }
        if (isset($info['title'])) {
            switch ($info['type']) {
                case 0:
                    $structure_type = 'page';
                    break;
                case 1:
                    $structure_type = 'folder';
                    break;
                default:
                case 2:
                    $structure_type = 'redir';
                    break;
            }
        }
        $structure_item = '<!--<span class="a-s_id" style="display: inline-block; min-width: 80px;">s_id =' . $info['s_id'] . '</span>--><a href="' . $this->cms->format_url('sim', 'man', array('s_id' => $info['s_id'])) . '" title="' . $structure_alt . '" class="' . $structure_type . ' ">' . $structure_title . '</a>';
        $actions = array(
            'add_sub' => '',        //	1
            'up' => '',        //	2
            'dn' => '',        //	4
            'properties' => '',        //	8
            'preview' => '',        //	16
            'switch' => '',        //	32
            'delete' => ''        //	64
        );

        if ($act & 1) $actions['add_sub'] = '&nbsp;<a href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $info['s_id'])) . '" title="Добавить вложенную стриницу" class="add-sub">добавить вложенную</a>';
        if ($act & 2) $actions['up'] = (isset($info['first_child']) && $info['first_child']) ? '' : '<a href="' . $this->cms->format_url($this->plugin['name'], 'move', array('s_id' => $info['s_id'], "order" => $info['ord'] - 1)) . '"><img src="/admin/img/prior_up.gif" width="8" height="11"></a>';
        if ($act & 4) $actions['dn'] = (isset($info['last_child']) && $info['last_child']) ? '' : '<a href="' . $this->cms->format_url($this->plugin['name'], 'move', array('s_id' => $info['s_id'], "order" => $info['ord'] + 1)) . '"><img src="/admin/img/prior_dn.gif" width="8" height="11"></a>';

        $actions['words'] = '<a href="' . $this->cms->format_url('sim', 'words', array('s_id' => $info['s_id'])) . '">теги</a>';

        //if ($act & 8) $actions['properties'] = '<a href="' . $this->cms->format_url('sim', 'prop', array('s_id' => $info['s_id'])) . '"><img src="/admin/img/edit.png" alt="Свойства" title="Свойства"></a>';

        //if ($act & 16) $actions['preview'] = '<a href="' . $info['url'] . '" target="_blank" title="Открыть страницу в новом окне"><img src="/admin/img/view_active.png" alt="Просмотр" title="Просмотр"></a>';

        if (!$info['static']) {
            if ($act & 64) $actions['delete'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'del', array('s_id' => $info['s_id'])) . '" onclick="return confirm(\'Удалить страницу?\')">удалить</a>';

            if ($info['enabled']) {
                if ($act & 32) $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $info['s_id'])) . '" >активна</a>';
            } else {
                //if ($act & 16) $actions['preview'] = '<span class="faded"><img src="/admin/img/view_inactive.png" alt="Просмотр" title="Просмотр"></span>';
                if ($act & 32) $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $info['s_id'])) . '" >выключена</a>';
            }
        }

        if ($info['enabled']) {
            $actions['onoroff'] = 'enabled';
        } else {
            $actions['onoroff'] = 'disabled';
        }

        $row = array_merge($info, $actions);
        $row['structure_level'] = $info['level'];
        $row['node_switch'] = $node_switch;

        $row['structure_item'] = $structure_item;
        $rows = $this->cms->parse_template($row, 'structure_row');

        $this->rows .= $rows;
        $info["rows"] = $rows;
    }


    function _print_structure1($parent = 0, $depth_limit = 2)
    {
        $show_condition = '';
        if ($parent > 0) {
            $parent_page = $this->cms->get_structure_item($parent);
//			echo "<pre>"; print_r($parent_page); echo "</prE>";
//			$show_condition = 'AND left_key >='.$parent_page['left_key'].' AND right_key <= '.$parent_page['right_key'];
            $show_condition = " AND language_id='" . $this->language . "' AND parent='" . $parent . "'";
        }

        $output = '';
        $expanded = $this->_get_expanded_nodes();
        $collapsed = $this->_get_collapsed_nodes();
        $volume_limit = $this->config['view_options']['volume_limit'];
//		$query = 'SELECT MAX(right_key) as max_right FROM '.$this->cms->tables['structure'].'';
//		$result = $this->dbc->Execute($query);
//		if (!$result) {
//			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
//			return false;
//		}
//		$max_row = $result->FetchRow();
        $structure_cache = array(0 => array('right_key' => $max_row['max_right'] + 1, 'left_key' => 0));

        if (is_array($expanded) && count($expanded) > 0) {
            $exp_condition = 'or parent in (' . implode(',', $expanded) . ')';
        } else {
            $exp_condition = '';
        }
        if (is_array($collapsed) && count($collapsed) > 0) {
            $coll_condition = 'and parent not in (' . implode(',', $collapsed) . ')';
        } else {
            $coll_condition = '';
        }
//		echo "<prE>"; print_r($expanded); echo "</prE>";
        if (is_array($expanded) && count($expanded) > 0) {
//			$coll_condition .= '  and parent not in ('.implode(',', $expanded).') )';
        }

        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE (level<' . ($depth_limit + 1) . ' ' . $exp_condition . ' ) ' . $coll_condition . $show_condition . ' ORDER BY title ASC';
        echo $query . "<br>";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() == 0) {
            return false;
        } elseif ($result->RecordCount() > 0) {
            /*
                        $output .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" class="structure">';
                        $output .= '<tr class="header">';
                        $output .= '<th width="60%"><b>'.$this->cms->translate('Название', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="20">&nbsp;</th>';
            //			$output .= '<th width="120px"><b>'.$this->cms->translate('Алиас', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="100"><b>'.$this->cms->translate('Шаблон', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="70"><b>'.$this->cms->translate('Действия', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="20">&nbsp;</th>';
                        $output .= '<th width="20">&nbsp;</th>';
                        $output .= '<th width="10" align="right"><input type="checkbox" onclick="javascript:setCheckboxes(\'structure\', \'s_id[]\', this.checked);"  title="'.$this->cms->translate('выделить все/очистить выделение', $this->plugin['name']).'"></th>';
                        $output .= '</tr>';
            */
            $rows = '';
//			$depth_markers = array();
            while ($structure = $result->FetchRow()) {
//				echo "<pre>"; print_r($structure); echo "</prE>";
                if ($structure['type'] > 5) {
                    continue;
                }
//				if (empty($structure_cache[$structure['parent']])) {
//					continue;
//				}
//				else {
//					$parent_item = $structure_cache[$structure['parent']];
//				}

//				if (($structure['right_key']-$structure['left_key'] > $volume_limit) and !in_array($structure['s_id'], $expanded)) {
//					continue;
//					$collapsed[] = $structure['s_id'];
//				}
//				else {
//					$structure_cache[$structure['s_id']] = $structure;
//				}
                if (empty($structure['relative_url']) && ($structure['enabled'] == 1)) {
                    $this->update_relative_url($structure['s_id']);
                }
                $structure = $this->cms->format_structure_item($structure);
//				if ($structure['left_key']-$parent_item['left_key']==1) {
//					$structure['first_child'] = true;
//				}
//				else {
//					$structure['first_child'] = false;
//				}
//				if ($parent_item['right_key']-$structure['right_key']==1) {
//					$structure['last_child'] = true;
//				}
//				else {
//					$structure['last_child'] = false;
//				}
//echo "<pre>"; print_r($structure); echo "</prE>";
                $node_switch = '';
                if ($structure['right_key'] - $structure['left_key'] == 1) {
//					no subpages
                    $node_switch .= '';
                } else {
                    if (in_array($structure['s_id'], $collapsed) || ($structure['level'] >= $depth_limit && !in_array($structure['s_id'], $expanded))) {
//					    expand_node
                        $node_switch .= '<a href="' . $this->cms->format_url($this->plugin['name'], 'exp', array('s_id' => $structure['s_id'])) . '" onclick="expand_node(' . $structure['s_id'] . ');"><img src="/admin/img/plus.gif" width="9" height="9" id="mk' . $structure['left_key'] . '"></a>';
                    } else {
//						switch_node
                        $node_switch .= '<a href="' . $this->cms->format_url($this->plugin['name'], 'exp', array('s_id' => $structure['s_id'])) . '" onclick="switch_node(' . ($structure['left_key'] + 1) . ', ' . ($structure['right_key'] - 1) . ', ' . $structure['s_id'] . '); return false;"><img src="/admin/img/minus.gif" width="9" height="9" id="mk' . $structure['left_key'] . '"></a>';
                    }
                }
                if (strlen($structure['title']) > 103) {
                    $structure_title = substr($structure['title'], 0, 100) . '...';
                    $structure_alt = $structure['title'] . ".\nАлиас:&nbsp;" . $structure['alias'];
                } else {
                    $structure_title = $structure['title'];
                    $structure_alt = 'Алиас:&nbsp;' . $structure['alias'];
                }
                switch ($structure['type']) {
                    case 0:
                        $structure_type = 'page';
                        break;

                    case 1:
                        $structure_type = 'folder';
                        break;

                    default:
                    case 2:
                        $structure_type = 'redir';
                        break;
                }
                $structure_item = '<a href="' . $this->cms->format_url('sim', 'man', array('s_id' => $structure['s_id'])) . '" title="' . $structure_alt . '" class="' . $structure_type . '">' . $structure_title . '</a>';
                $actions = array(
                    'add_sub' => '',
                    'up' => '',
                    'dn' => '',
                    'properties' => '',
                    'preview' => '',
                    'switch' => '',
                    'delete' => ''
                );

                $actions['add_sub'] = '&nbsp;<a href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $structure['s_id'])) . '" title="Добавить вложенную стриницу" class="add-sub">| +</a>';
                $actions['up'] = $structure['first_child'] ? '' : '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_up', array('s_id' => $structure['s_id'])) . '"><img src="/admin/img/prior_up.gif" width="8" height="11"></a>';
                $actions['dn'] = $structure['last_child'] ? '' : '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_down', array('s_id' => $structure['s_id'])) . '"><img src="/admin/img/prior_dn.gif" width="8" height="11"></a>';
                $actions['properties'] = '<a href="' . $this->cms->format_url('sim', 'prop', array('s_id' => $structure['s_id'])) . '">Свойства</a>';
                //Тут вставлена иконка просмотра. Гендо. 09.02.10
                $actions['preview'] = '<a href="' . $structure['url'] . '" target="_blank" title="Открыть страницу в новом окне">
				<img src="/admin/img/view_active.png" alt="Просмотр" title="Просмотр">
				</a>';
                if (!$structure['static']) {
                    //Тут вставлена иконка Удаления. Гендо. 09.02.10
                    $actions['delete'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'del', array('s_id' => $structure['s_id'])) . '" onclick="return confirm(\'Удалить страницу?\')">
					<img src="/admin/img/delete.png" alt="Удалить" title="Удалить">
					</a>';
                    if ($structure['enabled']) {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '" class="on">вкл</a>';
                    } else {
                        $actions['preview'] = '<span class="faded">Просмотр</span>';
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '" class="off">выкл</a>';
                    }
                }
//				$template = $this->_get_template($structure['template_id']);

                $row = array_merge($structure, $actions);
                $row['structure_level'] = $structure['level'];
                $row['node_switch'] = $node_switch;
                $row['structure_item'] = $structure_item;

                $rows .= $this->cms->parse_template($row, 'structure_row');

//				$rows .= $this->_print_structure($structure['s_id']);
            }
            $table = array(
                'title' => 'Заголовок',
                'template' => 'Шаблон',
                'actions' => 'Действия',
                'rows' => $rows,
            );
            $output .= $this->cms->parse_template($table, 'structure_table');
            $output .= '';
        }
        return $output;
    }

    function _print_structure_header($active_tab = 'tree', $search_query = '')
    {
        $iframe_src = $this->cms->format_url($this->plugin['name'], 'direct', array(), 'exec.php');
        $output = $this->cms->parse_template(array('iframe_src' => $iframe_src), 'structure_header');
        return $output;
    }

    function _print_structure_footer()
    {
        $variables['iframe'] = $this->_add_iframe($this->cms->format_url($this->plugin['name'], 'direct', array(), 'exec.php'));
        $output = $this->cms->parse_template($variables, 'structure_footer');
        return $output;
    }

    function getAbsTopFromId($s_id)
    {
        $sql = "SELECT language_id FROM " . $this->cms->tables["structure"] . " WHERE s_id='" . $s_id . "'";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        $sql = "SELECT s_id, language_id FROM " . $this->cms->tables["structure"] . " WHERE language_id='" . $row["language_id"] . "' AND level=1";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        return $row;
    }

    function direct()
    {
        $output = '';
        $this->cms->int_set_h1($this->plugin['title']);

//		echo "<pre>"; print_r($_POST); echo "</pre>";
//		die();
        if (isset($_POST["s_id"][0]) && !empty($_POST["s_id"][0])) {
            $parent = $this->getAbsTopFromId($_POST["s_id"][0]);
            $this->top = $parent["s_id"];
            $this->language = $parent["language_id"];
        }

        if (!empty($_POST['s_action'])) {
            $command = $_POST['s_action'];
        } else {
            $command = '';
        }
        switch ($command) {
            /*			default:
                        case 'index':
                            $output  = $this->_gen_rel_url();
                            $output  = $this->_check_integrity();
                        break;*/

            case 'enable':
                if (!empty($_POST['s_id'])) {
                    $this->_switch($_POST['s_id'], 1);
                }
                $output .= $this->_reload();
                break;

            case 'disable':
                if (!empty($_POST['s_id'])) {
                    $this->_switch($_POST['s_id'], 0);
                }
                $output .= $this->_reload();
                break;

            case 'delete':
                if (!empty($_POST['s_id'])) {
                    $this->_delete($_POST['s_id']);
                    $output = $this->_check_integrity();
                    $output .= $this->_reload();
                }
                break;

        }
        return $output;
    }

    function _gen_rel_url()
    {
        $query = 'SELECT s_id FROM ' . $this->cms->tables['structure'] . ' WHERE relative_url="" and enabled=1 ORDER BY title ASC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        }
        while ($row = $result->FetchRow()) {
            $this->update_relative_url($row['s_id']);
        }
        return true;
    }


//************************************************************************************


    function new_pages($arguments)
    {
        $output = '';
        $output .= $this->_print_tabs('new_pages');
        $output .= $this->_print_structure_header('new_pages');
        $output .= $this->_print_new_structure();
        $output .= $this->_print_structure_footer();
        return $output;
    }

    function updated_pages($arguments)
    {
        $output = '';
        $output .= $this->_print_tabs('updated_pages');
        $output .= $this->_print_structure_header('updated_pages');
        $output .= $this->_print_updated_structure();
        $output .= $this->_print_structure_footer();
        return $output;
    }

    function search($arguments)
    {
        if (!empty($arguments['query'])) {
            $query = trim($arguments['query']);
        } else {
            $query = '';
        }
        $output = '';
        $output .= $this->_print_tabs('search', $query);
        $output .= $this->_print_structure_header('search', $query);
        $output .= $this->_print_search_results($arguments);
        $output .= $this->_print_structure_footer();
        return $output;
    }

    function structure_options_list()
    {
        $options = '<option value="">-выберите страницу-</option>';
        $query = 'SELECT distinct(s_id), url, relative_url, title FROM ' . $this->cms->tables['structure'] . ' WHERE 1 ORDER BY title ASC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        }
        while ($structure = $result->FetchRow()) {
            if ($structure['type'] > 5) {
                continue;
            }
            $structure = $this->cms->format_structure_item($structure);
            $options .= '<option value="' . $structure['url'] . '">' . $structure['relative_url'] . '</option>';
        }
        //return "<select id=\"f_local_href\" style=\"width:100%;\">".$options."</select>";
        echo "document.write('<select id=\"f_local_href\" style=\"width:100%;\">" . $options . "</select>');";
        die();
    }


    function _reload()
    {
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array("s_id" => $this->top));
        $output = '<b>reload</b>';
        $output .= '<script type=text/javascript>';
        $output .= 'top.location.href = "' . $this->cms->admin_url . $target_url . '";';
        $output .= '</script>';
        return $output;
    }

    function rand_pass()
    {
        # Pronounceable pieces of words
        $array = array(
            "ap", "dus", "tin", "rog", "sti", "rev", "pik", "sty", "lev", "qot", "rel", "vid",
            "kro", "xo", "pro", "wia", "axi", "jer", "foh", "mu", "ya", "zol", "gu", "pli", "cra",
            "den", "bi", "sat", "ry", "qui", "wip", "fla", "gro", "tav", "peh", "gil", "lot",
            "kal", "zan", "noc", "bat", "tev", "lun", "pal", "hom", "cun", "wos", "vox"
        );
        $num_letters = 12;
        $uppercased = 3;
        mt_srand((double)microtime() * 1000000);
        $pass = '';
        for ($i = 0; $i < $num_letters; $i++)
            $pass .= $array[mt_rand(0, (count($array) - 1))];
        for ($i = 1; $i < strlen($pass); $i++) {
            if (substr($pass, $i, 1) == substr($pass, $i - 1, 1))
                $pass = substr($pass, 0, $i) . substr($pass, $i + 1);
        }
        for ($i = 0; $i < strlen($pass); $i++) {
            if (mt_rand(0, $uppercased) == 0)
                $pass = substr($pass, 0, $i) . strtoupper(substr($pass, $i, 1)) . substr($pass, $i + 1);
        }
        $pass = substr($pass, 0, $num_letters);
        return $pass;
    }


    function _fill_structure($fill_volume = 100)
    {
        return false;
        for ($i = 1; $i <= $fill_volume; $i++) {
            $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE level>0 and level<5 ORDER BY rand() LIMIT 1';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return false;
            } else if ($result->RecordCount() > 0) {
                $parent = $result->FetchRow();
                $item = array(
                    'alias' => time() . '_' . $i,
                    'title' => $this->rand_pass(),
                    'template_id' => 2,
                    'type' => 0,
                    'redirect_url' => ''
                );
                $s_id = $this->insert_node($parent, $item);
            }

        }
    }


    function _check_integrity()
    {
        $output = '';
        $query = 'SELECT COUNT(s_id) as total FROM ' . $this->cms->tables['structure'] . '';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        }
        $row = $result->FetchRow();
        $rows_total = $row['total'];
        if ($rows_total == 0) {
            $output = '<div style="padding: 10px;">';
            $output .= $this->cms->int_add_h2('Проверка целосности структуры');
            $output .= '<table cellpadding="0" cellspacing="0" border="0" class="integrity">';
            $output .= '<tr>';
            $output .= '<td>Все проверки успешно пройдены</td>';
            $output .= '</tr>';
            $output .= '</table>';
            $output .= '</div>';
            return $output;
        }
        /*** 1 ***/
        $query = 'SELECT s_id FROM ' . $this->cms->tables['structure'] . ' WHERE left_key>=right_key';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() > 0) {
            $output .= '<tr>';
            $output .= '<td width="300">1. Левый ключ ВСЕГДА меньше правого</td>';
            $output .= '<td width="300">FAILED</td>';
            $output .= '</tr>';
        }
        /*** 2 ***/
        $rows_total = 0;
        $query = 'SELECT COUNT(s_id) as total, MIN(left_key) as min_left, MAX(right_key) as max_right FROM ' . $this->cms->tables['structure'] . '';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() > 0) {
            $row = $result->FetchRow();
            $rows_total = $row['total'];
            if (($row['min_left'] == 1) && ($row['max_right'] == 2 * $row['total'])) {
                //$output .= '<td width="300">OK</td>';
            } else {
                $output .= '<tr>';
                $output .= '<td width="400">2. Наименьший левый ключ ВСЕГДА равен 1<br>Наибольший правый ключ ВСЕГДА равен двойному числу узлов</td>';
                $output .= '<td width="300">FAILED</td>';
                $output .= '</tr>';
            }
        }
        /*** 3 ***/
        $query = 'SELECT COUNT(s_id) as total FROM ' . $this->cms->tables['structure'] . ' WHERE ((right_key - left_key) % 2)=0';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            die($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() > 0) {
            $row = $result->FetchRow();
            if ($row['total'] > 0) {
                $output .= '<tr>';
                $output .= '<td width="400">3. Разница между правым и левым ключом ВСЕГДА нечетное число</td>';
                $output .= '<td width="300">FAILED</td>';
                $output .= '</tr>';
            }
        }
        /*** 4 ***/
        $query = 'SELECT COUNT(s_id) as total FROM ' . $this->cms->tables['structure'] . ' WHERE ((left_key-level+2) % 2)=1';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            die($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() > 0) {
            $row = $result->FetchRow();
            if ($row['total'] > 0) {
                $output .= '<tr>';
                $output .= '<td width="400">4. Если уровень узла нечетное число то тогда левый ключ ВСЕГДА нечетное число, то же самое и для четных чисел</td>';
                $output .= '<td width="300">FAILED</td>';
                $output .= '</tr>';
            }
        }
        /*** 5 ***/
        $query = 'SELECT COUNT(DISTINCT(left_key)) as total FROM ' . $this->cms->tables['structure'] . '';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            die($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        }
        $row = $result->FetchRow();
        $left_total = $row['total'];
        $query = 'SELECT COUNT(DISTINCT(right_key)) as total FROM ' . $this->cms->tables['structure'] . '';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            die($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        }
        $row = $result->FetchRow();
        $right_total = $row['total'];
        if (($right_total + $left_total) <> ($rows_total * 2)) {
            $output .= '<tr>';
            $output .= '<td width="400">5. Ключи ВСЕГДА уникальны</td>';
            $output .= '<td width="300">FAILED</td>';
            $output .= '</tr>';
        }
        if ($output > '') {
        } else {
            $output .= '<tr>';
            $output .= '<td>Все проверки успешно пройдены</td>';
            $output .= '</tr>';
        }
        $result = '<div style="padding: 10px;">';
        $result .= $this->cms->int_add_h2('Проверка целосности структуры');
        $result .= '<table cellpadding="0" cellspacing="0" border="0">';
        $result .= $output;
        $result .= '</table>';
        $result .= '</div>';
        return $result;
    }

    function getItemSelectFromOrd($parent, $id = 0, $ord = 0)
    {
        $sql = "SELECT s_id, title, ord FROM " . $this->cms->tables["structure"] . " WHERE parent='" . $parent . "'" . ($id ? " AND s_id<>'" . $id . "'" : "") . "ORDER BY title ASC";
        $results = $this->dbc->Execute($sql);
        $items = array();
        $items["first"] = 'первым';
        $items["last"] = 'последним';
        $selectedNow = 0;
        if ($results) {
            while ($row = $results->FetchRow()) {
                if ($row["s_id"] != $id) $items[$row["s_id"]] = $row["title"];
                if ($row["ord"] < $ord) $selectedNow = $row["s_id"];
            }
        }
        return array("items" => $items, "selectedNow" => $selectedNow);
    }

    function add($arguments)
    {
        $variables = array();
        $output = '';
        $s_id = (int)$arguments['s_id'];
        $type = 0;
        if (isset($arguments['type'])) {
            $type = $arguments['type'];
        }
        if ($type != 'lang') $type = 'page';
        $variables['tabs'] = $type == 'page' ? $this->_print_tabs('add_page', '', $s_id) : $this->_print_tabs('add_lang', '', $s_id);
        $default_alias = time();
        if (!empty($arguments['next_step'])) {
            $next_step = $arguments['next_step'];
        } else {
            $next_step = 'tree';
        }
        if ($type == 'page') {
            $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id;
            $this->cms->int_set_hierarchy($s_id);
        } else {
            $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE level=1 ORDER BY title ASC';
        }
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() == 0) {  //|| $type == 'lang'
            $parent = array(
                's_id' => 0,
                'left_key' => 0,
                'right_key' => 0,
            );
        } else {
            $parent = $result->FetchRow();
        }
//		echo "<pre>"; print_r($parent); echo "</pre>";

        $templates = $this->_get_templates();
        $fob = $this->init_fob('fob-add', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[s_id]', $s_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[language]', $this->language);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[type]', $type);
        $fob->add_hidden('s_id', $s_id);
        $fob->add_text(true, 'title', $default_alias, 'Название', '', '', '', 'text', '', '');
        $fob->add_text(true, 'alias', $default_alias, 'Алиас', '', '', '', 'text', '', '');
        $fob->add_select(false, 'template_id', $templates, 'Шаблон', '', '', '');

        $this->getStructureFromSelect();
        $onchange = 'onchange="changeParent(this, ' . $s_id . ', \'fob-add[ord]\')"';
        $js = '
		<script type="text/javascript" language="JavaScript1.2">
			function changeParent(objParent, s_id, name) {
				var objOrd = document.getElementsByName(name);
//				alert(objOrd[name].name);
				if(objParent.value==s_id) objOrd[name].disabled = false;
				else objOrd[name].disabled = true;
			}
		</script>';
//		echo "<pre>"; print_r($this->structureFromSelect); echo "</pre>";
        $fob->add_select(false, 'parent', $this->structureFromSelect, 'В разделе', '', $s_id, '', '', '', '', array(), '', $onchange, $js);

        $listOrd = $this->getItemSelectFromOrd($s_id);
//		echo "<pre>"; print_r($listOrd); echo "</pre>";
        $fob->add_select(false, 'ord', $listOrd["items"], 'Порядок', '', 'last');

        $fob->add_select(false, 'type', $this->structure_types, 'Тип', '', 0, '');
        $fob->add_text(false, 'redirect_url', '', 'Адрес редиректа', '', '', '', 'text', '', '');
//		$fob->add_checkbox(false, 'enabled', 1, 'Включен', 'Временное включение(выключение) раздела', '', (bool) $structure['enabled'], 'fob-check', '');
//		$fob->add_checkbox(false, 'auth', 0, 'Закрытая зона', '', '', (bool) $structure['auth'], 'fob-check', '');
//		$fob->add_text(false, 'level',  0,  'Приоритет',  '', 'fob-text', '', 'text', '', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '', '', 'button');
        $fob->add_radio(false, 'next_step', 'tree||item||add', 'После создания', 'вернуться к структуре сайта||перейти на вновь созданную страницу||вставить еще одну страницу', '', $next_step, '', '');
//		$fob->add_radio(false, 'next_step', 'tree||add', 'После создания', 'вернуться к структуре сайта||вставить еще одну страницу', '', $next_step, '', '');

        $variables['output'] = $fob->display('return');
        $output .= $this->cms->parse_template($variables, 'workarea');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            if (!$this->is_valid_alias(trim($values['alias']), $parent['s_id'])) {
                $this->cms->int_set_message('top', 'Недопустимый алиас. Алиас может содержать только латинские символы. Алиасы не должны совпадать для страниц одного раздела.', $this->plugin['title'], 'error');
                return $output;
            }
            if ($type == 'page') {
//				die("insert_node");
                $item_s_id = $this->insert_node($parent, $values);
            } else {
//				die("insert_root_node");
                $item_s_id = $this->insert_root_node($parent, $values);
            }
//			echo $item_s_id."<br>";
            switch ($values['next_step']) {
                default:
                case 'tree':
                    $type == 'lang' ? $parent = $this->get_level1_node($item_s_id) : $parent = $this->top;//$this->top
                    $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent));
                    break;

                case 'item':
                    $target_url = $this->cms->format_url('sim', 'man', array('s_id' => $item_s_id));
                    break;

                case 'add':
                    $target_url = $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $s_id, 'type' => $type, 'next_step' => 'add'));
                    break;
            }
            header('Location: ' . $target_url);
            return $this->index(array());
//			return $this->modify(array('s_id'=>$s_id));

        }
        return $output;
    }

    function getStructureFromSelect($childs = '', $shift = '')
    {
        if (!is_array($this->elementsStructrure) || count($this->elementsStructrure) == 0) {
            $this->getAllElementsStructure();
//			echo "<pre>"; print_r($this->elementsStructrure); echo "</pre>";
        }

        if (!is_array($childs)) {
            $this->structureFromSelect[$this->top] = $shift . $this->elementsStructrure[$this->top]["title"];
            if (count($this->elementsStructrure[$this->top]["childs"]) > 0)
                $this->getStructureFromSelect($this->elementsStructrure[$this->top]["childs"], $shift . "&nbsp;&nbsp;&nbsp;");
        } else {
            foreach ($childs as $s_id) {
                $this->structureFromSelect[$s_id] = $shift . $this->elementsStructrure[$s_id]["title"];
                $this->getStructureFromSelect($this->elementsStructrure[$s_id]["childs"], $shift . "&nbsp;&nbsp;&nbsp;");
            }
        }

    }

    function delete($arguments)
    {
        $s_id = (int)$arguments['s_id'];
        $parent = $this->get_level1_node($s_id);

        $this->delete_node($s_id);

        if ($s_id == $parent)
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        else
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $this->top));
        header('Location: ' . $target_url);
    }

    function default_expanded_nodes($parent, $level = 1)
    {
        if (empty($_COOKIE['pm_expanded'])) {
            $query = 'SELECT s_id FROM ' . $this->cms->tables['structure'] . ' WHERE enabled=1 AND level=' . $level;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return false;
            } else if ($result->RecordCount() == 0) {
                return false;
            } else {
                $nodes = $this->_get_expanded_nodes();
                while ($item = $result->FetchRow())
                    $nodes[] = $item['s_id'];

                $this->_set_expanded_nodes($nodes);
                //$this->expand_node(array('s_id' => $item['s_id']));
                $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent));
                header('Location: ' . $target_url);
                return true;
            }
        }
        return true;
    }

    function expand_node($arguments)
    {
        $s_id = (int)$arguments['s_id'];
        $expanded = $this->_get_expanded_nodes();
        $this->_set_expanded_nodes($expanded, $s_id);

        $collapsed = $this->_get_collapsed_nodes();
        $c_key = array_search($s_id, $collapsed);
        if ($c_key != false) {
            unset($collapsed[$c_key]);
            $this->_set_collapsed_nodes($collapsed);
        }

        $parent = $this->get_level1_node($s_id);

//        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=> $parent));
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $s_id));
        header('Location: ' . $target_url);
    }

    function _get_expanded_nodes()
    {
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

    function _set_expanded_nodes($nodes, $s_id = 0)
    {
        if (!empty($s_id)) {
            $nodes[] = $s_id;
            $nodes = array_unique($nodes);
        }
        setcookie('pm_expanded', $this->_cookie_sep . implode($this->_cookie_sep, $nodes) . $this->_cookie_sep, time() + 3600 * 24 * 14, '');
    }

    function _get_collapsed_nodes()
    {
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

    function _set_collapsed_nodes($nodes, $s_id = 0)
    {
        if (!empty($s_id)) {
            $nodes[] = $s_id;
            $nodes = array_unique($nodes);
        }
        setcookie('pm_collapsed', $this->_cookie_sep . implode($this->_cookie_sep, $nodes) . $this->_cookie_sep, time() + 3600 * 24 * 14, '');
    }

    function move($arguments)
    {
        $s_id = (int)$arguments['s_id'];
        $order = (int)$arguments['order'];
        $output = '';
        $query = 'SELECT ord, parent FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() <> 1) {
            return false;
        }
        $now = $result->FetchRow();

        $sql = "SELECT s_id FROM " . $this->cms->tables['structure'] . " WHERE ord='" . $order . "' AND parent='" . $now["parent"] . "'";
        $results = $this->dbc->Execute($sql);
        $oponent = $results->FetchRow();

        $sql = "UPDATE " . $this->cms->tables['structure'] . " SET ord='" . $order . "' WHERE s_id='" . $s_id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $sql, E_USER_ERROR);
            return false;
        }

        $sql = "UPDATE " . $this->cms->tables['structure'] . " SET ord='" . $now["ord"] . "' WHERE s_id='" . $oponent["s_id"] . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $this->top));
//		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: ' . $target_url);

    }

    function move_up($arguments)
    {
        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE level=' . $item['level'] . ' and left_key<' . $item['left_key'] . ' ORDER BY left_key DESC LIMIT 1';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() <> 1) {
            return false;
        }
        $neighbour = $result->FetchRow();
        $skew_neigh = $item['right_key'] - $item['left_key'] + 1;
        $skew_item = $neighbour['right_key'] - $neighbour['left_key'] + 1;

        $queries = array();
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="' . $item['s_id'] . '" WHERE left_key>=' . $item['left_key'] . ' AND right_key<=' . $item['right_key'] . '';
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="' . $neighbour['s_id'] . '" WHERE left_key>=' . $neighbour['left_key'] . ' AND right_key<=' . $neighbour['right_key'] . '';

        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="", left_key=left_key-' . $skew_item . ', right_key=right_key-' . $skew_item . ' WHERE relative_url="' . $item['s_id'] . '"';
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="", left_key=left_key+' . $skew_neigh . ', right_key=right_key+' . $skew_neigh . ' WHERE relative_url="' . $neighbour['s_id'] . '"';
        while (list(, $query) = each($queries)) {
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return $output;
            }
        }

        $parent = $this->get_level1_node($s_id);

        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent));
//		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: ' . $target_url);
    }

    function move_down($arguments)
    {

        $s_id = (int)$arguments['s_id'];
        $output = '';

        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() <> 1) {
            return false;
        }
        $neighbour = $result->FetchRow();

        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE level=' . $neighbour['level'] . ' and left_key>' . $neighbour['left_key'] . ' ORDER BY left_key ASC LIMIT 1';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() <> 1) {
            return false;
        }
        $item = $result->FetchRow();

        $skew_neigh = $item['right_key'] - $item['left_key'] + 1;
        $skew_item = $neighbour['right_key'] - $neighbour['left_key'] + 1;

        $queries = array();
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="' . $item['s_id'] . '" WHERE left_key>=' . $item['left_key'] . ' AND right_key<=' . $item['right_key'] . '';
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="' . $neighbour['s_id'] . '" WHERE left_key>=' . $neighbour['left_key'] . ' AND right_key<=' . $neighbour['right_key'] . '';

        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="", left_key=left_key-' . $skew_item . ', right_key=right_key-' . $skew_item . ' WHERE relative_url="' . $item['s_id'] . '"';
        $queries[] = 'UPDATE  ' . $this->cms->tables['structure'] . ' SET relative_url="", left_key=left_key+' . $skew_neigh . ', right_key=right_key+' . $skew_neigh . ' WHERE relative_url="' . $neighbour['s_id'] . '"';
        while (list(, $query) = each($queries)) {
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return $output;
            }
        }

        $parent = $this->get_level1_node($s_id);
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent));
        //$target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: ' . $target_url);
    }

    function _delete($s_ids)
    {
        if (!is_array($s_ids) || count($s_ids) == 0) {
            return false;
        }
        while (list(, $s_id) = each($s_ids)) {
            $this->delete_node($s_id);
        }
        return true;
    }

    function _switch($s_id, $enabled = 0)
    {
        if (!is_array($s_id) || count($s_id) == 0) {
            return false;
        }
        return $this->switch_nodes($s_id, $enabled);
    }

    function switch_state($arguments)
    {
        $s_id = (int)$arguments['s_id'];
        $query = 'SELECT type, enabled, parent FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() <> 1) {
            return false;
        }
        $item = $result->FetchRow();
        $enabled = abs($item['enabled'] - 1);

        $this->switch_node($s_id, $enabled);

        $parent = $this->get_level1_node($s_id);

//		$target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id'=> $parent));
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $s_id));
        header('Location: ' . $target_url);
        return $this->index(array());
    }
//*************************************************************
//Старая
    function switch_state1($arguments)
    {
        $s_id = (int)$arguments['s_id'];
        $query = 'SELECT type, enabled, right_key, left_key FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() <> 1) {
            return false;
        }
        $item = $result->FetchRow();
        $enabled = abs($item['enabled'] - 1);
        if (($item['right_key'] - $item['left_key'] == 1) && ($item['type'] == 1)) {
            $enabled = 0;
        }
        if ($enabled != $item['enabled']) {
            $this->switch_node($s_id, $enabled);
        }

        $parent = $this->get_level1_node($s_id);

        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array('s_id' => $parent));
        header('Location: ' . $target_url);
        return $this->index(array());
    }

//*************************************************************

    function get_level1_node($s_id)
    {
        if ($s_id > 0)
            $item = $this->cms->get_structure_item($s_id);
        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE level=1 AND left_key<=' . $item['left_key'] . ' AND right_key>=' . $item['right_key'];
        $result = $this->dbc->Execute($query);
//		die($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return 0;
        } elseif ($result->RecordCount() <> 1) {
            return 0;
        } else {
            $parent = $result->FetchRow();
//			print_r($parent);
            return $parent['s_id'];
        }
        return 0;
    }

    function check_id($id)
    {
        $query = 'SELECT s_id FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() <> 1) {
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
            header('Location: ' . $target_url);
            return false;
        } else return $id;
    }

    function _print_search_form($query = '')
    {
        if (!empty($query)) {
            $cell = 'th';
        } else {
            $cell = 'th';
        }
        $actions = '<form method="post" action="' . $this->cms->admin_url . '" name="fob-search" style="padding:0;margin:0;" >';
        $actions .= '<input type="hidden" name="' . $this->cms->request_vars['plugin'] . '" value="' . $this->plugin['name'] . '">';
        $actions .= '<input type="hidden" name="' . $this->cms->request_vars['command'] . '" value="search">';
        $actions .= '<nobr>&nbsp;Поиск:&nbsp;';
        $actions .= '<input type="text" value="' . htmlspecialchars($query) . '" name="' . $this->cms->request_vars['arguments'] . '[query]' . '" style="width:130px">';
        $actions .= '</nobr>';
        $actions .= '</form>';
        return $actions;
    }

    function _print_search_results($arguments)
    {
        if (!empty($arguments['query'])) {
            $query = trim($arguments['query']);
        } else {
            $query = '';
        }
        require_once($this->cms->lib_path . 'search_query_parser.class.php');
        if (empty($query) || strlen($query) < 4) {
            $output = $this->cms->int_add_message('Запрос должен содержать не менее 4 символов.', 'Поиск', 'information');
            return $output;
        }
        $output = '';
        $sqp = &new search_query_parser();
        $query = (get_magic_quotes_gpc() == 1 ? StripSlashes($query) : $query);
        $fields = array('alias', 'title');
        $result = $sqp->parse_query($query);
        $queries = $sqp->construct_sql_query($result, $fields);
        $sep = '';
        $db_query = '';
        while (list(, $sub) = each($queries)) {
            $db_query .= $sep . '(' . $sub . ')';
            $sep = ' OR ';
        }
        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE ' . $db_query . ' ORDER BY title ASC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            $output = $this->cms->int_add_message('По данному запросу ничего не найдено', 'Поиск', 'information');
            return $output;
        } else if ($result->RecordCount() > 0) {
            /*
                        $output .= '<table aligh=left width="100%" border=0 cellspacing=0 cellpadding=0 class="structure">';
                        $output .= '<tr class="header">';
                        //$output .= '<th width="20px">&nbsp;</th>';
                        $output .= '<th width="20px">&nbsp;</th>';
                        $output .= '<th width="20px">&nbsp;</th>';
                        $output .= '<th width="60%"><b>'.$this->cms->translate('Название', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="120px"><b>'.$this->cms->translate('Алиас', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="100px"><b>'.$this->cms->translate('Шаблон', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="70px"><b>'.$this->cms->translate('Действия', $this->plugin['name']).'</b></th>';
                        $output .= '<th width="10px" align="right"><input type="checkbox" onclick="javascript:setCheckboxes(\'structure\', \'s_id[]\', this.checked);"  title="'.$this->cms->translate('выделить все/очистить выделение', $this->plugin['name']).'"></th>';
                        $output .= '</tr>';
                        $output .= "\n";
            */
            $rows = '';
            while ($structure = $result->FetchRow()) {
                $structure = $this->cms->format_structure_item($structure);
                if (strlen($structure['title']) > 25) {
                    $structure_title = substr($structure['title'], 0, 15) . '...';
                    $structure_alt = $structure['title'];
                } else {
                    $structure_title = $structure['title'];
                    $structure_alt = '';
                }
                $actions = array(
                    'preview' => '',
                    'up' => '',
                    'down' => '',
                    'switch' => '',
                    'edit' => '',
                    'properties' => '',
                    'delete' => ''
                );

                $preview_url = $structure['url'];

                $add_sub_link = ' <a title="добавить вложенную стриницу" href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $structure['s_id'])) . '">+</a>';
                $edit_url = $this->cms->format_url('sim', 'man', array('s_id' => $structure['s_id']));
                $prop_url = $this->cms->format_url('sim', 'prop', array('s_id' => $structure['s_id']));
                $delete_url = $this->cms->format_url($this->plugin['name'], 'del', array('s_id' => $structure['s_id']));
                if ($structure['static']) {
                    $actions['preview'] = '<a href="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                } else {
                    $actions['delete'] = '<a onclick="return confirm(\'Удалить?\')" href="' . $delete_url . '"><img src="' . $this->cms->admin_images_url . 'i_del.gif" alt="удалить"></a>';
                    if ($structure['enabled']) {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_en.gif" alt="выключить"></a>';
                        $actions['preview'] = '<a href="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                    } else {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_dis.gif" alt="включить"></a>';
                    }
                }

                $actions['up'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'l_up', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_up.gif"></a>';
                $actions['down'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'l_down', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_down.gif"></a>';
                $actions['properties'] = '<a href="' . $prop_url . '"><img src="' . $this->cms->admin_images_url . 'i_prop.gif" alt="свойства"></a>';
                $actions['manage'] = '<a href="' . $edit_url . '"><img src="' . $this->cms->admin_images_url . 'i_edit.gif" alt="управление"></a>';

                $modify_link = '<a href="' . $edit_url . '" title="' . $structure_alt . '">' . $structure_title . '</a>';
                $actions_group = array($actions['properties'], $actions['delete']);
                switch ($structure['type']) {
                    case 0:  /* page */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_page.gif">&nbsp;';
                        break;

                    case 1:  /* folder */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_folder.gif">&nbsp;';
                        $modify_link = '<b>' . $modify_link . '</b>';
                        break;

                    default:
                    case 2: /* redirect */
                        $structure_icon = '&nbsp;';
                        break;
                }

                $row = array_merge($structure, $actions);
                $row['actions_group'] = implode(' ', $actions_group);
                $row['structure_icon'] = $structure_icon;
                $row['modify_link'] = $modify_link;
                $row['add_sub_link'] = $add_sub_link;
                $rows .= $this->cms->parse_template($row, 'search_row');
                /*
                                $output .= '<tr class="lev'.$structure['level'].'" id="r'.$structure['left_key'].'">';
                                //$output .= '<td nowrap>'.$actions['up'].$actions['down'].'</td>';
                                $output .= '<td align="center">'.$actions['preview'].'</td>';
                                $output .= '<td align="center">'.$actions['switch'].'</td>';
                                $output .= '<th>'.$depth_marker.$structure_icon.$modify_link.$add_sub_link.'</th>';
                                $output .= '<td>'.$structure['alias'].'</td>';
                                if (!empty($this->templates[$structure['template_id']])) {
                                    $output .= '<td>'.$this->templates[$structure['template_id']].'</td>';
                                } else {
                                    $output .= '<td>--&nbsp;</td>';
                                }
                                $output .= '<td nowrap>'.implode(' | ', $actions_group).'</td>';
                                        $output .= '<td><input type="checkbox" name="s_id[]" value='.$structure['s_id'].'></td>';
                                $output .= '</tr>';
                                $output .= "\n";
                */
            }
            $table = array(
                'title' => 'Заголовок',
                'template' => 'Шаблон',
                'actions' => 'Действия',
                'rows' => $rows,
            );
            $output .= $this->cms->parse_template($table, 'search_table');
            $output .= '';
//            $output .= '</table>';
        }
        return $output;
    }

    function _print_new_structure($parent = 0, $depth_limit = 2)
    {
        $output = '';
        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' ORDER BY title ASC LIMIT 0, 25';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            return false;
        } else if ($result->RecordCount() > 0) {
            $output .= '<table aligh=left width="100%" border=0 cellspacing=0 cellpadding=0 class="structure">';
            $output .= '<tr class="header">';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="60%"><b>Название</b></th>';
            $output .= '<th width="120px"><b>Алиас</b></th>';
            $output .= '<th width="100px"><b>Шаблон</b></th>';
            $output .= '<th width="70px"><b>Действия</b></th>';
            $output .= '<th width="10px" align="right"><input type="checkbox" onclick="javascript:setCheckboxes(\'structure\', \'s_id[]\', this.checked);"  title="выделить все/очистить выделение"></th>';
            $output .= '</tr>';
            $output .= "\n";
            $depth_markers = array();
            while ($structure = $result->FetchRow()) {
                if ($structure['type'] > 5) {
                    continue;
                }
                $structure = $this->cms->format_structure_item($structure);
                $depth_marker = '&nbsp;';
                if (strlen($structure['title']) > 25) {
                    $structure_title = substr($structure['title'], 0, 15) . '...';
                    $structure_alt = $structure['title'];
                } else {
                    $structure_title = $structure['title'];
                    $structure_alt = '';
                }
                $actions = array(
                    'preview' => '',
                    'up' => '',
                    'down' => '',
                    'switch' => '',
                    'edit' => '',
                    'properties' => '',
                    'delete' => ''
                );

                //$preview_url = $this->cms->site_url.$structure['relative_url'].'/';
                $preview_url = $structure['url'];

                $add_sub_link = ' <a title="добавить вложенную стриницу" href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $structure['s_id'])) . '">+</a>';
                $edit_url = $this->cms->format_url('sim', 'man', array('s_id' => $structure['s_id']));
                $prop_url = $this->cms->format_url('sim', 'prop', array('s_id' => $structure['s_id']));
                $delete_url = $this->cms->format_url($this->plugin['name'], 'del', array('s_id' => $structure['s_id']));
                if ($structure['static']) {
                    $actions['preview'] = '<a onclick="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                } else {
                    $actions['delete'] = '<a onclick="return confirm(\'Удалить?\')" href="' . $delete_url . '"><img src="' . $this->cms->admin_images_url . 'i_del.gif" alt="удалить"></a>';
                    if ($structure['enabled']) {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_en.gif" alt="выключить"></a>';
                        $actions['preview'] = '<a href="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                    } else {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_dis.gif" alt="включить"></a>';
                    }
                }
                if ($structure['first_child']) {
                    $actions['up'] = '&nbsp;';
                } else {
                    $actions['up'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_up', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_up.gif"></a>';
                }
                if ($structure['last_child']) {
                    $actions['down'] = '&nbsp;';
                } else {
                    $actions['down'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_down', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_down.gif"></a>';
                }
                $actions['properties'] = '<a href="' . $prop_url . '"><img src="' . $this->cms->admin_images_url . 'i_prop.gif" alt="свойства"></a>';
                $actions['manage'] = '<a href="' . $edit_url . '"><img src="' . $this->cms->admin_images_url . 'i_edit.gif" alt="управление"></a>';
                $modify_link = '<a href="' . $edit_url . '" title="' . $structure_alt . '">' . $structure_title . '</a>';
                $actions_group = array($actions['manage'], $actions['properties'], $actions['delete']);
                switch ($structure['type']) {
                    case 0:  /* page */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_page.gif">&nbsp;';
                        break;

                    case 1:  /* folder */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_folder.gif">&nbsp;';
                        $modify_link = '<b>' . $modify_link . '</b>';
                        break;

                    default:
                    case 2: /* redirect */
                        $structure_icon = '&nbsp;';
                        break;
                }
                $output .= '<tr class="lev' . $structure['level'] . '" id="r' . $structure['left_key'] . '">';
                $output .= '<td nowrap>' . $actions['up'] . $actions['down'] . '</td>';
                $output .= '<td>' . $actions['preview'] . '</td>';
                $output .= '<td>' . $actions['switch'] . '</td>';
                $output .= '<th>' . $depth_marker . $structure_icon . $modify_link . $add_sub_link . '</th>';
                $output .= '<th>' . $structure['alias'] . '</th>';
                if (!empty($this->templates[$structure['template_id']])) {
                    $output .= '<th>' . $this->templates[$structure['template_id']] . '</th>';
                } else {
                    $output .= '<th>--&nbsp;</th>';
                }
                $output .= '<td nowrap>' . implode(' | ', $actions_group) . '</td>';
                $output .= '<td><input type="checkbox" name="s_id[]" value=' . $structure['s_id'] . '></td>';
                $output .= '</tr>';
                $output .= "\n";
            }
            $output .= '</table>';
        }
        return $output;
    }

    function _print_updated_structure($parent = 0, $depth_limit = 2)
    {
        $output = '';
        $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' ORDER BY title ASC LIMIT 0, 25';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            return false;
        } else if ($result->RecordCount() > 0) {
            $output .= '<table aligh=left width="100%" border=0 cellspacing=0 cellpadding=0 class="structure">';
            $output .= '<tr class="header">';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="20px">&nbsp;</th>';
            $output .= '<th width="60%"><b>Название</b></th>';
            $output .= '<th width="120px"><b>Алиас</b></th>';
            $output .= '<th width="100px"><b>Шаблон</b></th>';
            $output .= '<th width="70px"><b>Действия</b></th>';
            $output .= '<th width="10px" align="right"><input type="checkbox" onclick="javascript:setCheckboxes(\'structure\', \'s_id[]\', this.checked);"  title="выделить все/очистить выделение"></th>';
            $output .= '</tr>';
            $output .= "\n";
            $depth_markers = array();
            while ($structure = $result->FetchRow()) {
                if ($structure['type'] > 5) {
                    continue;
                }
                $structure = $this->cms->format_structure_item($structure);
                $depth_marker = '&nbsp;';
                if (strlen($structure['title']) > 25) {
                    $structure_title = substr($structure['title'], 0, 15) . '...';
                    $structure_alt = $structure['title'];
                } else {
                    $structure_title = $structure['title'];
                    $structure_alt = '';
                }
                $actions = array(
                    'preview' => '',
                    'up' => '',
                    'down' => '',
                    'switch' => '',
                    'edit' => '',
                    'properties' => '',
                    'delete' => ''
                );

                //$preview_url = $this->cms->site_url.$structure['relative_url'].'/';
                $preview_url = $structure['url'];

                $add_sub_link = ' <a href="' . $this->cms->format_url($this->plugin['name'], 'add', array('s_id' => $structure['s_id'])) . '" title="Добавить вложенную стриницу">+</a>';
                $edit_url = $this->cms->format_url('sim', 'man', array('s_id' => $structure['s_id']));
                $prop_url = $this->cms->format_url('sim', 'prop', array('s_id' => $structure['s_id']));
                $delete_url = $this->cms->format_url($this->plugin['name'], 'del', array('s_id' => $structure['s_id']));
                if ($structure['static']) {
                    $actions['preview'] = '<a onclick="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                } else {
                    $actions['delete'] = '<a href="' . $delete_url . '" onclick="return confirm(\'Удалить?\')"><img src="' . $this->cms->admin_images_url . 'i_del.gif" alt="удалить"></a>';
                    if ($structure['enabled']) {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_en.gif" alt="выключить"></a>';
                        $actions['preview'] = '<a href="' . $preview_url . '"><img src="' . $this->cms->admin_images_url . 'i_prev.gif"></a>';
                    } else {
                        $actions['switch'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_dis.gif" alt="включить"></a>';
                    }
                }
                if ($structure['first_child']) {
                    $actions['up'] = '&nbsp;';
                } else {
                    $actions['up'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_up', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_up.gif"></a>';
                }
                if ($structure['last_child']) {
                    $actions['down'] = '&nbsp;';
                } else {
                    $actions['down'] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'i_down', array('s_id' => $structure['s_id'])) . '"><img src="' . $this->cms->admin_images_url . 'i_down.gif"></a>';
                }
                $actions['properties'] = '<a href="' . $prop_url . '"><img src="' . $this->cms->admin_images_url . 'i_prop.gif" alt="свойства"></a>';
                $actions['manage'] = '<a href="' . $edit_url . '"><img src="' . $this->cms->admin_images_url . 'i_edit.gif" alt="управление"></a>';
                $modify_link = '<a href="' . $edit_url . '" title="' . $structure_alt . '">' . $structure_title . '</a>';
                $actions_group = array($actions['manage'], $actions['properties'], $actions['delete']);
                switch ($structure['type']) {
                    case 0:  /* page */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_page.gif">&nbsp;';
                        break;

                    case 1:  /* folder */
                        $structure_icon = '<img src="' . $this->cms->admin_images_url . 'it_folder.gif">&nbsp;';
                        $modify_link = '<b>' . $modify_link . '</b>';
                        break;

                    default:
                    case 2: /* redirect */
                        $structure_icon = '&nbsp;';
                        break;
                }
                $output .= '<tr class="lev' . $structure['level'] . '" id="r' . $structure['left_key'] . '">';
                $output .= '<td nowrap>' . $actions['up'] . $actions['down'] . '</td>';
                $output .= '<td>' . $actions['preview'] . '</td>';
                $output .= '<td>' . $actions['switch'] . '</td>';
                $output .= '<th>' . $depth_marker . $structure_icon . $modify_link . $add_sub_link . '</th>';
                $output .= '<th>' . $structure['alias'] . '</th>';
                if (!empty($this->templates[$structure['template_id']])) {
                    $output .= '<th>' . $this->templates[$structure['template_id']] . '</th>';
                } else {
                    $output .= '<th>--&nbsp;</th>';
                }
                $output .= '<td nowrap>' . implode(' | ', $actions_group) . '</td>';
                $output .= '<td><input type="checkbox" name="s_id[]" value=' . $structure['s_id'] . '></td>';
                $output .= '</tr>';
                $output .= "\n";
            }
            $output .= '</table>';
        }
        return $output;
    }


}

?>