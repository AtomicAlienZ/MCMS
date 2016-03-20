<?php

class plugin_admin_interface extends cms_plugin_admin
{
    var $plugin = array();
    var $config = array();
    var $items_per_page = 100;
    var $dbc;
    var $fob;
    var $cms = '';
    var $i_template = 'default';
    var $i_handler = 'html_editor';

    var $search_handler = '"news"';

    var $default_height = 150;
    var $default_width = 150;

    var $sortOrder = array(
        'dates' => 'дате',
        'title' => 'названию',
        'id' => 'времени добавления',
    );

    function index($arguments)
    {
        $output = '';
        $s_id = (int)$arguments['s_id'];
        $toc_id = (int)$arguments['toc_id'];
        $where_condition = array();
        $where_condition[] = 's_id=' . $s_id;
        $where_condition[] = 'toc_id=' . $toc_id;

        $sql = "SELECT * FROM " . $this->cms->tables["news_structure"] . " WHERE " . join(" AND ", $where_condition);
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } elseif ($result->RecordCount() == 0) {
            $item = $this->insert($arguments);
        } else $item = $result->FetchRow();

        $rubricsItem = array();
        if ($item["rubrics"] != '') {
            $tmp = explode("|", substr($item["rubrics"], 1, -1));
            if (count($tmp) > 0) {
                $rubricsItem = array_flip($tmp);
            }
        }

        $edit_url = $this->cms->format_url($this->plugin["name"], "index", $arguments);
        $path = array('<a href="' . $edit_url . '">' . $this->plugin['title'] . '</a>');
        $this->cms->int_set_hierarchy($s_id, $path);

        $id = $item['id'];

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'index');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[s_id]', $s_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[toc_id]', $toc_id);
        $fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'lim', $item['lim'], 'Ограничение на количество новостей', '', 'fob-text', '', 'text', '', '');
        $fob->add_select(false, 'sorttype', $this->sortOrder, 'Тип сортировки', '', $item['sorttype'], '');
        $fob->add_checkbox(false, 'show_navi', 1, 'Отображать навигацию', '', '', $item['show_navi'], 'fob-check', '', '');
        $fob->add_select(true, 'block_id', $this->getBlocks($item['id']), 'Обзор для блока', '', $item['block_id'], '');
        $rubrics = $this->getRubrics($item['id']);
        if (count($rubrics) > 0) {
            $text = '<b>Рубрики:</b>';
            $lTitle = '';
            $fob->add_html('img', $text, 'html_code');
            foreach ($rubrics as $key => $val) {
                $fob->add_checkbox(false, 'rubric[' . $key . ']', $key, '<span style="padding-left:20px;">' . $this->cms->translate($val['title']) . '</span>', '', '', isset($rubricsItem[$key]), 'fob-check', '');
            }
        }

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $item = array(
                'title' => "'" . addslashes(trim($values['title'])) . "'",
                'lim' => (int)$values['lim'],
                'sorttype' => "'" . addslashes(trim($values['sorttype'])) . "'",
                'show_navi' => (int)0 + !empty($values['show_navi']),
                'block_id' => (int)$values['block_id'],
            );

            $idS = array();
            foreach ($values['rubric'] as $key => $val) {
                if ($val) $idS[] = $key;
            }
            if (count($idS) > 0) {
                $item["rubrics"] = "'|" . implode("|", $idS) . "|'";
            } else {
                $item["rubrics"] = "''";
            }

            $update_sql = array();
            foreach ($item as $key => $val) {
                $update_sql[] = $key . '=' . $val;
            }
            $query = 'UPDATE ' . $this->cms->tables['news_structure'] . ' SET ' . join(', ', $update_sql) . ' WHERE ' . join(' AND ', $where_condition);
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return false;
            }

            $target_url = $this->cms->format_url('sim', 'man', array('s_id' => $s_id));
            header('Location: ' . $target_url);
            return $output;
        }
        return $output;
    }

    function getBlocks($selfId)
    {
        $rubr = array();
        $query = 'SELECT  news.id, str.title, str.relative_url, news.title AS blockTitle
					FROM ' . $this->cms->tables['toc'] . ' AS toc,
						' . $this->cms->tables['structure'] . ' AS str,
						' . $this->cms->tables['news_structure'] . ' AS news
					WHERE toc.handler=' . $this->search_handler . '
						/*AND toc.enabled = 1*/
						AND str.enabled = 1
						AND str.s_id=toc.s_id
						AND news.s_id=toc.s_id
						AND news.toc_id=toc.toc_id
						AND news.id=news.block_id
					ORDER BY str.s_id';
//		die($query);
        $result = $this->dbc->Execute($query);
        $rubr[$selfId] = "сам на себя";
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            return false;
        } elseif ($result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {
                if ($item['id'] == $selfId)
                    $rubr[$item['id']] = "сам на себя";
                else
                    $rubr[$item['id']] = $item['title'] . ' - "' . $item['blockTitle'] . '"';
            }
        }
        return $rubr;
    }

    function getRubrics($id = 0)
    {
        $ret = array();
        $query = "SELECT * FROM " . $this->cms->tables['news_rubrics'] . " ORDER BY BINARY(title) ASC";
//		echo $query."<br>";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return $ret;
        } elseif ($result->RecordCount() == 0) {
            return $ret;
        }
        while ($item = $result->FetchRow()) {
            $ret[$item['rubric_id']] = $item;
        }
        return $ret;
    }


    function insert($arguments)
    {
        $output = '';
        $s_id = (int)$arguments['s_id'];
        $toc_id = (int)$arguments['toc_id'];
        $where_condition = array();
        $items = array(
            's_id' => $s_id,
            'toc_id' => $toc_id,
            'lim' => 10,
            'sorttype' => '"dates"',
        );

        $query = 'INSERT INTO ' . $this->cms->tables['news_structure'] . '  (' . join(',', array_keys($items)) . ') VALUES (' . join(',', $items) . ')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        }
        $items['id'] = $this->dbc->insert_id();
        return $items;
    }

    function init()
    {
        require_once(cms_LIB_PATH . 'form_builder.class.php');
        require_once(cms_LIB_PATH . 'images.lib.php');
        $this->dbc = &$this->cms->dbc;
        $this->initTables();
    }

    function initTables()
    {
        $pathToTable = cms_PLUGINS_PATH . 'news_manage/tables.php';
        if (file_exists($pathToTable)) {
            require_once($pathToTable);
            $this->cms->tables = array_merge($this->tables, $this->cms->tables);
            $this->cms->install->dbc = &$this->dbc;
            $this->cms->install->tables = $this->tables;
            $this->cms->install->tablesColumn = $this->tableColumn;
            $this->cms->install->runInstall();
        }
    }

    function main($command, $arguments)
    {
        $this->init();
        $this->cms->int_set_h1($this->plugin['title']);
        if (method_exists($this, $command)) {
            $result = $this->$command($arguments);
            $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
            return $result;
        }
        switch ($command) {
            default:
                $result = $this->index($arguments);
                break;
        }
        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');
        return $result;
    }

    function print_tabs($active_tab, $arguments = array())
    {
        $tabs = array(
            'index' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'index', $arguments) . '">' . $this->plugin['title'] . '</a>',
        );
        $active_tab = !array_key_exists($active_tab, $tabs) ? 'index' : $active_tab;
        setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }


}