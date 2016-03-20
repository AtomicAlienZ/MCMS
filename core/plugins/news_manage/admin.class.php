<?php

class plugin_admin_interface extends cms_plugin_admin {

    var $plugin             = array();
    var $config             = array();
    var $dbc;
    var $fob;
    var $cms_ai             = '';
    var $defaultWidth       = 400;
    var $defaultHeight      = 400;
    var $defaultWidthSm     = 200;
    var $defaultHeightSm    = 200;
    var $defaultWidthSm1    = 80;
    var $defaultHeightSm1   = 80;
    var $defaultWidthSm2    = 80;
    var $defaultHeightSm2   = 80;
    var $allowed_file_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/svg');

    // Список поддерживаемых языков
    var $langs = array();

    // Текущий язык сайта
    var $lang = "ru";

    function index($arguments) {
        $output = '';

        $this->cms->intSetHierarchy();

        $add_link = '<div style="padding: 10px 0px;"><input type="button" value="Добавить рубрику" style="color:#000000; text-decoration:none;" onClick="javascript:window.location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'addRubric', $arguments) . "'" . '"></div>';
        $output .= $add_link;

        $sql = "SELECT * FROM " . $this->cms->tables["news_rubrics"] . " ORDER BY BINARY(title) ASC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            return $output;
        }

        $output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h">Название</td>
					<td class="act-h last" colspan="2">Действия</td>
				</tr>';
        while ($item = $result->FetchRow()) {
            $arguments = array('id' => $item['rubric_id']);
            $actions = array(
                'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'editRubric', $arguments) . '">редактировать</a>',
                'delete' => '<a onclick="return confirm(\'' . $this->translate('Удалить') . '?\')" href="' . $this->cms->format_url($this->plugin['name'], 'deleteRubric', $arguments) . '">удалить</a>'
            );
            $output .= '
				<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title"><a href="' . $this->cms->format_url($this->plugin['name'], 'showNews', array('rubric_id' => $item['rubric_id'])) . '">' . $item['title'] . '</a></td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
				</tr>
			';
        }
        $output .= '</table></div></div>';

        $output .= $add_link;

        return $output;
    }

    function addRubric($arguments) {
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'addRubric', $arguments) . '">Добавление рубрики</a>';
        $this->cms->intSetHierarchy();

        $output = '';
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'addRubric');
        $fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(true, 'alias', '', 'alias', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'title_menu', '', 'title_menu', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_title', '', 'meta-title', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_description', '', 'meta-description', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', '', 'meta-keywords', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'description', '', 'description', '', 'fob-text', '', 'text', '', '');


//		$fob->add_checkbox(false, 'subscription', 1, 'Рассылать новости из этой рубрики', '', '', 0, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');
        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $item = array(
                'title'            => "'" . addslashes(trim($values['title'])) . "'",
                'alias'            => "'" . addslashes(trim($values['alias'])) . "'",
                'meta_title'       => "'" . addslashes(trim($values['meta_title'])) . "'",
                'meta_description' => "'" . addslashes(trim($values['meta_description'])) . "'",
                'meta_keywords'    => "'" . addslashes(trim($values['meta_keywords'])) . "'",
                'title_menu'       => "'" . addslashes(trim($values['title_menu'])) . "'",
                'description'      => "'" . addslashes(trim($values['description'])) . "'",

                //				'subscription'	   => "'".addslashes(trim($values['subscription']))."'",
            );

            $query = 'INSERT INTO ' . $this->cms->tables['news_rubrics'] . ' (' . join(',', array_keys($item)) . ') VALUES(' . join(',', $item) . ')';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            }
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function editRubric($arguments) {
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'editRubric', $arguments) . '">Добавление рубрики</a>';
        $this->cms->intSetHierarchy();
        $output = '';
        $id = (int)$arguments['id'];
        $sql = "SELECT * FROM " . $this->cms->tables["news_rubrics"] . " WHERE rubric_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
            header('Location: ' . $target_url);
            die();
        }
        $item = $result->FetchRow();
        //die($item['subscription']);
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'editRubric');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(true, 'alias', $item['alias'], 'alias', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'title_menu', $item['title_menu'], 'Название для меню', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_title', $item['meta_title'], 'meta-title', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_description', $item['meta_description'], 'meta-description', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', $item['meta_keywords'], 'meta-keywords', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'description', $item['description'], 'description', '', 'fob-text', '', 'text', '', '');

//		$fob->add_checkbox(false, 'subscription', 1, 'Рассылать новости из этой рубрики', '', '', (int)$item['subscription'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');
        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $item = array(
                'title'            => "'" . addslashes(trim($values['title'])) . "'",
                'alias'            => "'" . addslashes(trim($values['alias'])) . "'",
                'meta_title'       => "'" . addslashes(trim($values['meta_title'])) . "'",
                'meta_description' => "'" . addslashes(trim($values['meta_description'])) . "'",
                'meta_keywords'    => "'" . addslashes(trim($values['meta_keywords'])) . "'",
                'title_menu'       => "'" . addslashes(trim($values['title_menu'])) . "'",
                'description'      => "'" . addslashes(trim($values['description'])) . "'",
                //				'subscription'	   => "'".addslashes(trim($values['subscription']))."'",
            );

            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($item)) {
                $update_sql .= $update_sep . $field . '=' . $value . ' ';
                $update_sep = ', ';
            }
            $sql = "UPDATE " . $this->cms->tables["news_rubrics"] . " SET " . $update_sql . " WHERE rubric_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            }
            $target_url = $this->cms->format_url($this->plugin['name'], 'index', $arguments);
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function deleteRubric($arguments) {
        $id = (int)$arguments['id'];
        $sql = "DELETE FROM " . $this->cms->tables["news_rubrics"] . " WHERE rubric_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: ' . $target_url);
        die();
    }

    function getInfoRubric($id) {
        $ret = array();
        $sql = "SELECT * FROM " . $this->cms->tables["news_rubrics"] . " WHERE rubric_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        }
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() > 0) {
            $ret = $result->FetchRow();
        }

        return $ret;
    }

    function getRubrics($id = 0) {
        $ret = array();
        $query = "SELECT * FROM " . $this->cms->tables['news_rubrics'] . " ORDER BY BINARY(title) ASC";

        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return $ret;
        } elseif ($result->RecordCount() == 0) {
            return $ret;
        }
        while ($item = $result->FetchRow()) {
            $ret[ $item['rubric_id'] ] = $item;
        }

        return $ret;
    }

    function showNews($arguments) {
        $rubric_id = 0;

        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];

        if ($rubric_id) {
            $infoRubric = $this->getInfoRubric($rubric_id);
            $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'showNews', $arguments) . '">' . $infoRubric["title"] . '</a>';
        }
        $this->cms->intSetHierarchy();

        $output = '';

        $add_link = '<p><input type="button" class="button" value="Добавить новость" onClick="javascript:window.location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'add', $arguments) . "'" . '"></p>';
        $output .= $add_link;

        $where = '';
        if ($rubric_id) {
            $where = " WHERE rubrics LIKE '%|" . $rubric_id . "|%'";
        }
        $sql = "SELECT * FROM " . $this->cms->tables["news"] . $where . " ORDER BY dates DESC, BINARY(title) ASC, id ASC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            return $output;
        }
        $output = '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h">Название</td>
					<td class="act-h">&nbsp;</td>
					<td class="act-h">&nbsp;</td>
					<td class="act-h last" colspan="2">Действия</td>
				</tr>
		';
        while ($item = $result->FetchRow()) {
            $arg = array('id' => $item['id']);
            if ($rubric_id)
                $arg["rubric_id"] = $rubric_id;
            if ($item['enabled']) {
                $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch_state', $arg) . '" class="on">вкл</a>';
            } else {
                $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switch_state', $arg) . '" class="off">выкл</a>';
            }
            $actions = array(
                'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'edit', $arg) . '">редактировать</a>',
                'delete' => '<a onclick="return confirm(\'Удалить?\')" href="' . $this->cms->format_url($this->plugin['name'], 'delete', $arg) . '">удалить</a>'
            );
            if (strlen($item['title']) > 103) {
                $iTitle = substr($item['title'], 0, 100) . '...';
            } else {
                $iTitle = $item['title'];
            }
            $output .= '
				<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title"><a href="' . $this->cms->format_url($this->plugin['name'], 'edit', $arg) . '">' . $iTitle . '</a></td>
					<td class="act">' . Date('d.m.Y', strtotime($item['dates'])) . '</td>
					<td class="act">' . $switch . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
				</tr>
			';
        }
        $output .= '</table></div></div>';
        $add_link = '<p><input type="button" class="button" value="Добавить новость" onClick="javascript:window.location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'add', $arguments) . "'" . '"></p>';
        $output .= $add_link;

        return $output;
    }

    function add($arguments) {
        $rubric_id = 0;
        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];

        if ($rubric_id) {
            $infoRubric = $this->getInfoRubric($rubric_id);
            $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'showNews', $arguments) . '">' . $infoRubric["title"] . '</a>';
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'add', $arguments) . '">Добавление новости</a>';
        $this->cms->intSetHierarchy();

        $output = '';
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'add');

        if ($rubric_id) {
            $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[rubric_id]', $rubric_id);
        }

        $fob->add_text(true, 'title', '', 'Заголовок', '', 'fob-text', '', 'text', '', '');

        $fob->add_html('meta', $this->cms->int_add_h2('Мета данные:'));
        $fob->add_text(true, 'alias', '', 'alias', 'alias', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_title', '', 'Title', 'meta', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', '', 'Ключевые слова', 'meta', 'fob-text', '', 'text', '', '');
        $fob->add_textarea(false, 'meta_description', '', 'Описание', 'meta', 30, 3, 'fob-textarea');
        $fob->add_text(false, 'tags', '', 'Теги', 'через запятую', '', '', 'text', '', '');

        $fob->add_html('meta', $this->cms->int_add_h2('Данные о объекте:'));
        $fob->add_wysiwyg(false, 'descr', '', 'Анонс', '', 10, 20, 'fob-textarea');
        $fob->add_wysiwyg(false, 'content', '', 'Содержимое', '', 30, 20, 'fob-textarea');
        $fob->add_date('dates', Date('d.m.Y', time()), 'Дата');
        $fob->add_file('img', 'Основное изображение');

        $fob->add_html('meta', $this->cms->int_add_h2('Информация о авторе и источнике:'));
        $fob->add_text(false, 'author', '', 'Автор', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'sourceName', '', 'Источник', 'название', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'sourceUrl', '', 'Источник', 'ссылка', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', true, 'fob-check', '');

        $fob->add_html('meta', $this->cms->int_add_h2('Параметры автоматизации:'));
        $fob->add_checkbox(false, 'auto_start', '1', 'Отображать в промежутке', '', '', false, 'fob-check', '');
        $fob->add_date('dates_start', Date('d.m.Y', time()), 'Дата с');
        $fob->add_date('dates_end', Date('d.m.Y', time() + 60 * 60 * 24 * 31), 'Дата по');

        $rubrics = $this->getRubrics();

        if (count($rubrics) > 0) {
            $fob->add_html('meta', $this->cms->int_add_h2('Рубрики:'));
            foreach ($rubrics as $key => $val) {
                $fob->add_checkbox(false, 'rubric[' . $key . ']', $key, '<span style="padding-left:20px;"><nobr>' . $val['title'] . '</nobr></span>', '', '', $rubric_id == $key, 'fob-check', '');
            }
        }

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');

        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $files = $fob->get_submited_files();

            if (!empty($files['img']['name'])) {
                $imgToUse = $this->uploadImg($files['img']);

                $values['img_orig'] = $imgToUse['img_orig'];
                $values['img'] = $imgToUse['img'];
                $values['img_sm'] = $imgToUse['img_sm'];
                $values['img_sm1'] = $imgToUse['img_sm1'];
                $values['img_sm2'] = $imgToUse['img_sm2'];
            } else {
                $values['img_orig'] = '';
                $values['img'] = '';
                $values['img_sm'] = '';
                $values['img_sm1'] = '';
                $values['img_sm2'] = '';
            }
            $dateArr = explode('.', $values['dates']);
            $item = array(
                'alias'            => "'" . addslashes(trim($values['alias'])) . "'",
                'title'            => "'" . addslashes(trim($values['title'])) . "'",
                'meta_title'       => "'" . addslashes(trim($values['meta_title'])) . "'",
                'meta_keywords'    => "'" . addslashes(trim($values['meta_keywords'])) . "'",
                'meta_description' => "'" . addslashes(trim($values['meta_description'])) . "'",
                'tags'             => "'" . addslashes(trim($values['tags'])) . "'",
                'descr'            => "'" . addslashes(trim($values['descr'])) . "'",
                'content'          => "'" . addslashes(trim($values['content'])) . "'",
                'dates'            => "'" . $dateArr[2] . "-" . $dateArr[1] . "-" . $dateArr[0] . "'",
                'author'           => "'" . addslashes(trim($values['author'])) . "'",
                'sourceName'       => "'" . addslashes(trim($values['sourceName'])) . "'",
                'sourceUrl'        => "'" . addslashes(trim($values['sourceUrl'])) . "'",
                'img_orig'         => "'" . addslashes(trim($values['img_orig'])) . "'",
                'img'              => "'" . addslashes(trim($values['img'])) . "'",
                'img_sm'           => "'" . addslashes(trim($values['img_sm'])) . "'",
                'enabled'          => (int)$values['enabled'],
            );
            $this->updateItemTags($values['tags']);

            $dateStartArr = explode('.', $values['dates_start']);
            $dateEndArr = explode('.', $values['dates_end']);
            $item["auto_start"] = "'" . (int)$values['auto_start'] . "'";
            $item["dates_start"] = "'" . $dateStartArr[2] . "-" . $dateStartArr[1] . "-" . $dateStartArr[0] . "'";
            $item["dates_end"] = "'" . $dateEndArr[2] . "-" . $dateEndArr[1] . "-" . $dateEndArr[0] . "'";

            $idS = array();
            foreach ($values['rubric'] as $key => $val) {
                if ($val)
                    $idS[] = $key;
            }
            if (count($idS) > 0) {
                $item["rubrics"] = "'|" . implode("|", $idS) . "|'";
            } else {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Необходимо указать рубрику', 'Error', 'error');

                return $output;
            }

            $sql = "INSERT INTO " . $this->cms->tables["news"] . " (" . join(",", array_keys($item)) . ") VALUES(" . join(",", $item) . ")";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
            $newId = $this->dbc->insert_id();

            if ($values["submit"] == "Сохранить и остаться") {
                $target_url = $this->cms->format_url($this->plugin['name'], 'edit', array("id" => $newId, "rubric_id" => $arguments["rubric_id"]));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'showNews', $arguments);
            }
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function edit($arguments) {

        /*

        1. Взяли языки
        2. Проверили, есть ли копии с такими языками в базе
        3. Записали эти копии в переменную
        4. Вывели поля
        5.

        */


        $output = '';
        $id = (int)$arguments['id'];
        $arg = $arguments;
        $this->init_languages();
        unset($arg["id"]);
        $rubric_id = 0;

        $ru_not_set = 0;

        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];

        if ($rubric_id) {
            $infoRubric = $this->getInfoRubric($rubric_id);
            $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'showNews', $arg) . '">' . $infoRubric["title"] . '</a>';
        }

        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'edit', $arguments) . '">Редактирование новости</a>';
        $this->cms->intSetHierarchy();

        $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;

        $result = $this->dbc->Execute($query);

        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Required news not found<br>' . $query, $this->plugin['name'], 'error');

            return false;
        }

        $item = $result->FetchRow();

        $rubricsItem = array();
        if ($item["rubrics"] != '') {
            $tmp = explode("|", substr($item["rubrics"], 1, -1));
            if (count($tmp) > 0) {
                $rubricsItem = array_flip($tmp);
            }
        }

        /* Getting language copies */
        $item__copies = array();

        foreach ($this->langs as $language) {
            $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE original_id=' . $id . ' AND language="' . $language . '"';
            $result = $this->dbc->Execute($query);

            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            } elseif ($result->RecordCount() > 0) {
                while ($record = $result->FetchRow()) {
                    $item__copies[ $language["alias"] ] = $record;
                }
            } else {
                if ($language == "ru") {
                    $ru_not_set = 1;
                }

                $sql = "INSERT INTO " . $this->cms->tables["news"] . "(original_id, language) VALUES (" . $item["id"] . ", '" . $language . "')";
                $result = $this->dbc->Execute($sql);

                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }

                $item__copies[ $language["alias"] ]["language"] = $language;
            }
        }

        /* ! Getting language copies */

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        if ($rubric_id) {
            $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[rubric_id]', $rubric_id);
        }

        $fob->add_html('meta', $this->cms->int_add_h2('Общее для всех языков:'));
        $fob->add_text(true, 'alias', $item['alias'], 'alias', 'alias', 'fob-text', '', 'text', '', '');
        $fob->add_file('img', 'Изображение');

        $fob->add_text(false, 'author', $item['author'], 'Автор', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'sourceName', $item['sourceName'], 'Источник', 'название', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'sourceUrl', $item['sourceUrl'], 'Источник', 'ссылка', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', $item['enabled'], 'fob-check', '');
        $fob->add_date('dates', Date('d.m.Y', strtotime($item['dates'])), 'Дата');

        $rubrics = $this->getRubrics();
        if (count($rubrics) > 0) {
            $fob->add_html('meta', $this->cms->int_add_h2('Рубрики:'));
            foreach ($rubrics as $key => $val) {
                $fob->add_checkbox(false, 'rubric[' . $key . ']', $key, '<span style="padding-left:20px;"><nobr>' . $val['title'] . '</nobr></span>', '', '', isset($rubricsItem[ $key ]), 'fob-check', '');
            }
        }

        foreach ($item__copies as $copy) {

            if (($ru_not_set == 1) and ($copy["language"] == "ru")) {
                //die();
                $fob->add_html('meta', $this->cms->int_add_h2($copy["language"]));
                $fob->add_text(false, 'title' . $copy["language"], $item['title'], 'Название ', '', 'fob-text', '', 'text', '', '');
                $fob->add_text(false, 'meta_title' . $copy["language"], $item['meta_title'], 'Title', 'meta', 'fob-text', '', 'text', '', '');
                $fob->add_text(false, 'meta_keywords' . $copy["language"], $item['meta_keywords'], 'Ключевые слова', 'meta', 'fob-text', '', 'text', '', '');
                $fob->add_textarea(false, 'meta_description' . $copy["language"], $item['meta_description'], 'Описание', 'meta', 30, 3, 'fob-textarea');
                $fob->add_text(false, 'tags' . $copy["language"], $item['tags'], 'Теги', 'через запятую', 'fob-text', '', 'text', '', '');
                $fob->add_wysiwyg(false, 'descr' . $copy["language"], $item['descr'], 'Краткая информация ' . $copy["language"], '', 10, 20, 'fob-textarea');
                $fob->add_wysiwyg(false, 'content' . $copy["language"], $item['content'], 'Содержимое', '', 30, 20, 'fob-textarea');
            } else {
                $fob->add_html('meta', $this->cms->int_add_h2($copy["language"]));
                $fob->add_text(false, 'title' . $copy["language"], $copy['title'], 'Название ', '', 'fob-text', '', 'text', '', '');
                $fob->add_text(false, 'meta_title' . $copy["language"], $copy['meta_title'], 'Title', 'meta', 'fob-text', '', 'text', '', '');
                $fob->add_text(false, 'meta_keywords' . $copy["language"], $copy['meta_keywords'], 'Ключевые слова', 'meta', 'fob-text', '', 'text', '', '');
                $fob->add_textarea(false, 'meta_description' . $copy["language"], $copy['meta_description'], 'Описание', 'meta', 30, 3, 'fob-textarea');
                $fob->add_text(false, 'tags' . $copy["language"], $copy['tags'], 'Теги', 'через запятую', 'fob-text', '', 'text', '', '');
                $fob->add_wysiwyg(false, 'descr' . $copy["language"], $copy['descr'], 'Краткая информация ', '', 10, 20, 'fob-textarea');
                $fob->add_wysiwyg(false, 'content' . $copy["language"], $copy['content'], 'Содержимое', '', 30, 20, 'fob-textarea');
            }

        }

        if ($ru_not_set == 1) {

            $update_copies[ $item__copies["ru"]["id"] ] = array(
                'title'            => "'" . addslashes(trim($item['title'])) . "'",
                'meta_title'       => "'" . addslashes(trim($item['meta_title'])) . "'",
                'meta_keywords'    => "'" . addslashes(trim($item['meta_keywords'])) . "'",
                'meta_description' => "'" . addslashes(trim($item['meta_description'])) . "'",
                'tags'             => "'" . addslashes(trim($item['tags'])) . "'",
                'descr'            => "'" . addslashes(trim($item['descr'])) . "'",
                'content'          => "'" . addslashes(trim($item['content'])) . "'",
            );

            $update_sql = '';
            $update_sep = '';

            while (list($field, $value) = each($update_copies[ $item__copies["ru"]["id"] ])) {
                $update_sql .= $update_sep . $field . '=' . $value . ' ';
                $update_sep = ', ';
            }

            $query = 'UPDATE ' . $this->cms->tables['news'] . ' SET ' . $update_sql . ' WHERE original_id=' . $item["id"] . ' AND language="ru"';
            $result = $this->dbc->Execute($query);

            if (!$result) {
                $this->mosaic->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            }

        }


        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $files = $fob->get_submited_files();

            if (!empty($files['img']['name'])) {
                $this->dieImg($item);
                $imgToUse = $this->uploadImg($files['img']);
                $values['img_orig'] = $imgToUse['img_orig'];
                $values['img'] = $imgToUse['img'];
                $values['img_sm'] = $imgToUse['img_sm'];
            } else {
                $values['img_orig'] = $item['img_orig'];
                $values['img'] = $item['img'];
                $values['img_sm'] = $item['img_sm'];
            }
            $dateArr = explode('.', $values['dates']);

            $update = array(
                'dates'      => "'" . $dateArr[2] . "-" . $dateArr[1] . "-" . $dateArr[0] . "'",
                'img_orig'   => "'" . addslashes(trim($values['img_orig'])) . "'",
                'img'        => "'" . addslashes(trim($values['img'])) . "'",
                'img_sm'     => "'" . addslashes(trim($values['img_sm'])) . "'",
                'sourceName' => "'" . addslashes(trim($values['sourceName'])) . "'",
                'sourceUrl'  => "'" . addslashes(trim($values['sourceUrl'])) . "'",
                'author'     => "'" . addslashes(trim($values['author'])) . "'",
                'enabled'    => (int)$values['enabled']
            );

            foreach ($item__copies as $copy) {
                $update__copies[ $copy["id"] ] = array(
                    'title'            => "'" . addslashes(trim($values[ 'title' . $copy["language"] ])) . "'",
                    'meta_title'       => "'" . addslashes(trim($values[ 'meta_title' . $copy["language"] ])) . "'",
                    'meta_keywords'    => "'" . addslashes(trim($values[ 'meta_keywords' . $copy["language"] ])) . "'",
                    'meta_description' => "'" . addslashes(trim($values[ 'meta_description' . $copy["language"] ])) . "'",
                    'tags'             => "'" . addslashes(trim($values[ 'tags' . $copy["language"] ])) . "'",
                    'descr'            => "'" . addslashes(trim($values[ 'descr' . $copy["language"] ])) . "'",
                    'content'          => "'" . addslashes(trim($values[ 'content' . $copy["language"] ])) . "'"
                );
            }

            //var_dump($update__copies);

            $this->updateItemTags($values['tags']);

            $dateStartArr = explode('.', $values['dates_start']);
            $dateEndArr = explode('.', $values['dates_end']);
            $update["auto_start"] = "'" . (int)$values['auto_start'] . "'";
            $update["dates_start"] = "'" . $dateStartArr[2] . "-" . $dateStartArr[1] . "-" . $dateStartArr[0] . "'";
            $update["dates_end"] = "'" . $dateEndArr[2] . "-" . $dateEndArr[1] . "-" . $dateEndArr[0] . "'";

            $idS = array();

            foreach ($values['rubric'] as $key => $val) {
                if ($val)
                    $idS[] = $key;
            }
            if (count($idS) > 0) {
                $update["rubrics"] = "'|" . implode("|", $idS) . "|'";
            } else {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Необходимо указать рубрику', 'Error', 'error');

                return $output;
            }

            $update_sql = '';
            $update_sep = '';

            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep . $field . '=' . $value . ' ';
                $update_sep = ', ';
            }

            $query = 'UPDATE ' . $this->cms->tables['news'] . ' SET ' . $update_sql . ' WHERE id=' . $id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            }

            /* writing copies */

            foreach ($update__copies as $key => $update__copy) {
                $update_sql = '';
                $update_sep = '';

                while (list($field, $value) = each($update__copy)) {
                    $update_sql .= $update_sep . $field . '=' . $value . ' ';
                    $update_sep = ', ';
                }

                $query = 'UPDATE ' . $this->cms->tables['news'] . ' SET ' . $update_sql . ' WHERE id=' . $key;
//                var_dump($query);

                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                    return false;
                }
            }

            //die();

            /* ! writing copies */

            unset($arguments["id"]);

            if ($values["submit"] == "Сохранить и остаться") {
                $target_url = $this->cms->format_url($this->plugin['name'], 'edit', array("id" => $id, "rubric_id" => $arguments["rubric_id"]));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'showNews', $arguments);
            }

            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function switch_state($arguments) {
        $item_id = (int)$arguments['id'];
        $rubric_id = 0;
        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];

        $query = 'UPDATE ' . $this->cms->tables['news'] . ' SET enabled=abs(enabled-1) WHERE id=' . $item_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $arg = array();
        if ($rubric_id)
            $arg["rubric_id"] = $rubric_id;
        $target_url = $this->cms->format_url($this->plugin['name'], 'showNews', $arg);
        header('Location: ' . $target_url);

        return '';
    }

    function delete($arguments) {
        $rubric_id = 0;
        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];
        $id = (int)$arguments['id'];
        $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            return false;
        }
        $item = $result->FetchRow();
        $this->dieImg($item);
        $query = 'DELETE FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $arg = array();
        if ($rubric_id)
            $arg["rubric_id"] = $rubric_id;
        $target_url = $this->cms->format_url($this->plugin['name'], 'showNews', $arg);
        header('Location: ' . $target_url);
    }

    function gallery($arguments) {
        $id = (int)$arguments['id'];
        $rubric_id = 0;
        if (isset($arguments['rubric_id']))
            $rubric_id = (int)$arguments['rubric_id'];

        $output = '';

        $output .= $this->cms->int_add_h1('<a name="gallery"></a>Фотогалерея элемента:');

        $sql = "SELECT * FROM " . $this->cms->tables["news_gallery"] . " WHERE news_id='" . $id . "' ORDER BY ord ASC, BINARY(title) ASC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            $output .= 'Фотографии к данному элементу не загружены';
        } else {
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
					<tr>
						<td class="title-h">Название</td>
						<td class="act-h">&nbsp;</td>
						<td class="act-h">&nbsp;</td>
						<td class="act-h last" colspan="2">Действия</td>
					</tr>
			';
            while ($item = $result->FetchRow()) {
                $arg = array('id' => $id, 'rubric_id' => $rubric_id, 'gid' => $item['id']);
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'editGallery', $arg) . '">редактировать</a>',
                    'delete' => '<a onclick="return confirm(\'' . $this->translate('Удалить') . '?\')" href="' . $this->cms->format_url($this->plugin['name'], 'deleteGallery', $arg) . '">удалить</a>'
                );
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switchStateGallery', $arg) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'switchStateGallery', $arg) . '" class="off">выкл</a>';
                }
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
						<td class="title"><a href="' . $this->cms->format_url($this->plugin['name'], 'editGallery', $arg) . '">' . $item['title'] . '</a></td>
						<td class="act">' . $switch . '</td>
						<td class="act">' . $actions['edit'] . '</td>
						<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
            }
            $output .= '
				</table></div></div>
			';
        }

        $fob = $this->init_fob('fob_gal', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[rubric_id]', $rubric_id);

        for ($i = 1; $i < 5; $i++) {
            $fob->add_html('meta', $this->cms->int_add_h2('Фото ' . $i . ':'));
            $fob->add_file('img_' . $i, 'Изображение');
            $fob->add_text(false, 'title_' . $i, '', 'Название', '', 'fob-text', '', 'text', '', '');
            $fob->add_text(false, 'meta_title_' . $i, '', 'Title', 'meta', 'fob-text', '', 'text', '', '');
            $fob->add_text(false, 'meta_keywords_' . $i, '', 'Ключевые слова', 'meta', 'fob-text', '', 'text', '', '');
            $fob->add_textarea(false, 'meta_description_' . $i, '', 'Описание', 'meta', 30, 3, 'fob-textarea');
            $fob->add_textarea(false, 'descr_' . $i, '', 'Краткое описание', '', 30, 3, 'fob-textarea');
            $fob->add_checkbox(false, 'enabled_' . $i, '1', 'Включена', '', '', true, 'fob-check', '');
            $fob->add_text(false, 'ord_' . $i, '0', 'Порядок', 'meta', 'fob-text', '', 'text', '', '');
        }
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
        $output .= $fob->display('return');
        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $files = $fob->get_submited_files();

            for ($i = 1; $i < 5; $i++) {
                if (!empty($files[ 'img_' . $i ]['name'])) {
                    $item = array(
                        'news_id'          => "'" . $id . "'",
                        'title'            => "'" . addslashes(trim($values[ 'title_' . $i ])) . "'",
                        'meta_title'       => "'" . addslashes(trim($values[ 'meta_title_' . $i ])) . "'",
                        'meta_keywords'    => "'" . addslashes(trim($values[ 'meta_keywords_' . $i ])) . "'",
                        'meta_description' => "'" . addslashes(trim($values[ 'meta_description_' . $i ])) . "'",
                        'descr'            => "'" . addslashes(trim($values[ 'descr_' . $i ])) . "'",
                        'created'          => "NOW()",
                        'enabled'          => (int)$values[ 'enabled_' . $i ],
                        'ord'              => (int)$values[ 'ord_' . $i ],
                    );
                    $sql = "INSERT INTO " . $this->cms->tables["news_gallery"] . " (" . join(",", array_keys($item)) . ") VALUES(" . join(",", $item) . ")";
//					echo $sql."<br>";
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
                    $newId = $this->dbc->insert_id();

                    $imgToUse = $this->uploadImg($files[ 'img_' . $i ], "gallery/", "art_" . $id . "_" . $newId);

                    $values['img_orig'] = $imgToUse['img_orig'];
                    $values['img'] = $imgToUse['img'];
                    $values['img_sm'] = $imgToUse['img_sm'];
                    $values['img_sm1'] = $imgToUse['img_sm1'];
                    $values['img_sm2'] = $imgToUse['img_sm2'];
                    $item = array(
                        'img_orig' => "'" . addslashes(trim($values['img_orig'])) . "'",
                        'img'      => "'" . addslashes(trim($values['img'])) . "'",
                        'img_sm'   => "'" . addslashes(trim($values['img_sm'])) . "'",
                        'img_sm1'  => "'" . addslashes(trim($values['img_sm1'])) . "'",
                        'img_sm2'  => "'" . addslashes(trim($values['img_sm2'])) . "'",
                    );
                    $update_sql = '';
                    $update_sep = '';
                    while (list($field, $value) = each($item)) {
                        $update_sql .= $update_sep . $field . '=' . $value . ' ';
                        $update_sep = ', ';
                    }
                    $sql = 'UPDATE ' . $this->cms->tables['news_gallery'] . ' SET ' . $update_sql . ' WHERE id=' . $newId;
//					echo $sql."<br>";
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
//					die();
                }
            }
            if ($values["submit"] == "Сохранить и остаться") {
                $target_url = $this->cms->format_url($this->plugin['name'], 'edit', array("id" => $id, "rubric_id" => $arguments["rubric_id"]));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'showNews', $arguments);
            }
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function switchStateGallery($arguments) {
        $id = (int)$arguments['id'];
        $rubric_id = (int)$arguments['rubric_id'];
        $gid = (int)$arguments['gid'];

        $query = 'UPDATE ' . $this->cms->tables['news_gallery'] . ' SET enabled=abs(enabled-1) WHERE id=' . $gid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $arg = array();
        $arg["rubric_id"] = $rubric_id;
        $arg["id"] = $id;
        $target_url = $this->cms->format_url($this->plugin['name'], 'edit', $arg) . "#gallery";
        header('Location: ' . $target_url);

        return '';
    }

    function editGallery($arguments) {
        $output = '';
        $id = (int)$arguments['id'];
        $gid = (int)$arguments['gid'];
        $rubric_id = (int)$arguments['rubric_id'];
        $arg = $arguments;
        unset($arg["gid"]);

        if ($rubric_id) {
            $infoRubric = $this->getInfoRubric($rubric_id);
            $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'showNews', array("rubric_id" => $rubric_id)) . '">' . $infoRubric["title"] . '</a>';
        }

        $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Required pic not found<br>' . $query, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'edit', $arg) . '">' . $item["title"] . '</a>';

        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'editGallery', $arguments) . '">Редактирование фото</a>';
        $this->cms->intSetHierarchy();

        $query = 'SELECT * FROM ' . $this->cms->tables['news_gallery'] . ' WHERE id=' . $gid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Required pic not found<br>' . $query, $this->plugin['name'], 'error');

            return false;
        }

        $item = $result->FetchRow();

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'editGallery');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[rubric_id]', $rubric_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[gid]', $gid);

        $fob->add_text(false, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_title', $item['meta_title'], 'Title', 'meta', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', $item['meta_keywords'], 'Ключевые слова', 'meta', 'fob-text', '', 'text', '', '');
        $fob->add_textarea(false, 'meta_description', $item['meta_description'], 'Описание', 'meta', 30, 3, 'fob-textarea');
        $fob->add_textarea(false, 'descr', $item['descr'], 'Краткое описание', '', 30, 3, 'fob-textarea');

        $fob->add_file('img', 'Изображение');
        if (!empty($item['img_sm1'])) {
            $img = '<img src="' . $item['img_sm1'] . '" border="0">';
            $fob->add_html('img', $img, 'html_code');
        }
        $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', $item['enabled'], 'fob-check', '');
        $fob->add_text(false, 'ord', $item['ord'], 'Порядок', '', 'fob-text', '', 'text', '', '');

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            $files = $fob->get_submited_files();

            if (!empty($files['img']['name'])) {
                $this->dieImg($item);
                $imgToUse = $this->uploadImg($files['img']);
                $values['img_orig'] = $imgToUse['img_orig'];
                $values['img'] = $imgToUse['img'];
                $values['img_sm'] = $imgToUse['img_sm'];
                $values['img_sm1'] = $imgToUse['img_sm1'];
                $values['img_sm2'] = $imgToUse['img_sm2'];
            } else {
                $values['img_orig'] = $item['img_orig'];
                $values['img'] = $item['img'];
                $values['img_sm'] = $item['img_sm'];
                $values['img_sm1'] = $item['img_sm1'];
                $values['img_sm2'] = $item['img_sm2'];
            }
            $dateArr = explode('.', $values['dates']);
            $update = array(
                'title'            => "'" . addslashes(trim($values['title'])) . "'",
                'meta_title'       => "'" . addslashes(trim($values['meta_title'])) . "'",
                'meta_keywords'    => "'" . addslashes(trim($values['meta_keywords'])) . "'",
                'meta_description' => "'" . addslashes(trim($values['meta_description'])) . "'",
                'descr'            => "'" . addslashes(trim($values['descr'])) . "'",
                'img_orig'         => "'" . addslashes(trim($values['img_orig'])) . "'",
                'img'              => "'" . addslashes(trim($values['img'])) . "'",
                'img_sm'           => "'" . addslashes(trim($values['img_sm'])) . "'",
                'img_sm1'          => "'" . addslashes(trim($values['img_sm1'])) . "'",
                'img_sm2'          => "'" . addslashes(trim($values['img_sm2'])) . "'",
                'enabled'          => (int)$values['enabled'],
                'ord'              => (int)$values['ord'],
                'modified'         => "NOW()",
            );

            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep . $field . '=' . $value . ' ';
                $update_sep = ', ';
            }
            $query = 'UPDATE ' . $this->cms->tables['news_gallery'] . ' SET ' . $update_sql . ' WHERE id=' . $id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

                return false;
            }
            unset($arguments["id"]);
            if ($values["submit"] == "Сохранить и остаться") {
                $target_url = $this->cms->format_url($this->plugin['name'], 'editGallery', array("id" => $id, "gid" => $gid, "rubric_id" => $rubric_id));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'edit', $arg) . "#gallery";
            }
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function deleteGallery($arguments) {
        $rubric_id = (int)$arguments['rubric_id'];
        $id = (int)$arguments['id'];
        $gid = (int)$arguments['gid'];
        $query = 'SELECT * FROM ' . $this->cms->tables['news_gallery'] . ' WHERE id=' . $gid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() == 0) {
            return false;
        }
        $item = $result->FetchRow();
        $this->dieImg($item);
        $query = 'DELETE FROM ' . $this->cms->tables['news_gallery'] . ' WHERE id=' . $gid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $arg = array("rubric_id" => $rubric_id, "id" => $id);
        $target_url = $this->cms->format_url($this->plugin['name'], 'edit', $arg) . "#gallery";
        header('Location: ' . $target_url);
    }

    function uploadImg($img, $addFolder = '', $i = 0) {
        $items = array(
            'img_orig' => ""
            /*            'img' => "",
                        'img_sm' => "",
                        'img_sm1' => "",
                        'img_sm2' => "",*/
        );
        $file = $img;
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info["extension"]);
        $fileName = time() . '_' . $i;
        $img_url = 'content/news/' . $addFolder . $fileName . '.' . $extension;
        $target = ROOT_HTML_PATH . $img_url;

        //die ($img_url);

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $this->cms_ai->message_box->add('top', 'Ошибка при закачивании ' . $file['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');

            return $output;
        }

        $img_url_orig = 'content/news/' . $addFolder . $fileName . '_orig.' . $extension;
        $target_orig = ROOT_HTML_PATH . $img_url_orig;

        if (!copy($target, $target_orig)) {
            $this->cms_ai->message_box->add('top', 'Ошибка при копировании ' . $target . ' -> ' . $target_orig, $this->plugin['name'], 'error');

            return $output;
        }

        @chmod($target_orig, 0777);
        $items['img_orig'] = '/' . $img_url_orig;


        $this->cms->files->resizeImageMode($target, $this->defaultWidth, $this->defaultHeight, 0);
        @chmod($target, 0777);
        $items['img'] = '/' . $img_url;

        /*$img_url = 'content/news/' . $addFolder . $fileName . '_sm.' . $extension;
        $target2 = ROOT_HTML_PATH . $img_url;
        if (!copy($target, $target2)) {
            $this->cms_ai->message_box->add('top', 'Ошибка при копировании ' . $target . ' -> ' . $target2, $this->plugin['name'], 'error');
            return $output;
        }
        $this->cms->files->resizeImageMode($target2, $this->defaultWidthSm, $this->defaultHeightSm, 2);
        @chmod($target2, 0777);
        $items['img_sm'] = '/' . $img_url;

        $img_url = 'content/news/' . $addFolder . $fileName . '_sm1.' . $extension;
        $target3 = ROOT_HTML_PATH . $img_url;
        if (!copy($target, $target3)) {
            $this->cms_ai->message_box->add('top', 'Ошибка при копировании ' . $target . ' -> ' . $target3, $this->plugin['name'], 'error');
            return $output;
        }
        $this->cms->files->resizeImageMode($target3, $this->defaultWidthSm1, $this->defaultHeightSm1, 2);
        @chmod($target3, 0777);
        $items['img_sm1'] = '/' . $img_url;

        $img_ur2 = 'content/news/' . $addFolder . $fileName . '_sm2.' . $extension;
        $target4 = ROOT_HTML_PATH . $img_url;
        if (!copy($target, $target4)) {
            $this->cms_ai->message_box->add('top', 'Ошибка при копировании ' . $target . ' -> ' . $target4, $this->plugin['name'], 'error');
            return $output;
        }
        $this->cms->files->resizeImageMode($target4, $this->defaultWidthSm2, $this->defaultHeightSm2, 2);
        @chmod($target4, 0777);
        $items['img_sm2'] = '/' . $img_url;*/

        return $items;
    }

    function dieImg($item) {
        $filename = ROOT_HTML_PATH . substr($item['img_orig'], 1, strlen($item['img_orig']));
        if (!empty($item['img_orig']) && file_exists($filename)) {
            if (!is_writeable($filename)) {
                $this->cms->int_add_message('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');

                return $output;
            }
            $result = unlink($filename);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }
        $filename = ROOT_HTML_PATH . substr($item['img'], 1, strlen($item['img']));
        if (!empty($item['img']) && file_exists($filename)) {
            if (!is_writeable($filename)) {
                $this->cms->int_add_message('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');

                return $output;
            }
            $result = unlink($filename);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }
        $filename = ROOT_HTML_PATH . substr($item['img_sm'], 1, strlen($item['img_sm']));
        if (!empty($item['img_sm']) && file_exists($filename)) {
            if (!is_writeable($filename)) {
                $this->cms->int_add_message('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');

                return $output;
            }
            $result = unlink($filename);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }
        $filename = ROOT_HTML_PATH . substr($item['img_sm1'], 1, strlen($item['img_sm1']));
        if (!empty($item['img_sm1']) && file_exists($filename)) {
            if (!is_writeable($filename)) {
                $this->cms->int_add_message('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');

                return $output;
            }
            $result = unlink($filename);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }
        $filename = ROOT_HTML_PATH . substr($item['img_sm2'], 1, strlen($item['img_sm2']));
        if (!empty($item['img_sm2']) && file_exists($filename)) {
            if (!is_writeable($filename)) {
                $this->cms->int_add_message('top', 'Недостаточно прав для удаления файла.', $this->plugin['name'], 'error');

                return $output;
            }
            $result = unlink($filename);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }
    }

    function deleteImg($arguments) {
        $id = (int)$arguments['id'];
        $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        } elseif ($result->RecordCount() == 0) {
            return false;
        }
        $item = $result->FetchRow();
        $this->dieImg($item);
        $query = 'UPDATE ' . $this->cms->tables['news'] . ' SET img_orig="", img="", img_sm="", img_sm1="", img_sm2="" WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'edit', $arguments);
        header('Location: ' . $target_url);
    }

    /* 	function tags($arguments) {
      $rubric_id = 0;

      $this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'tags', $arguments).'">Теги</a>';
      $this->cms->intSetHierarchy();

      $output = '';

      $add_link = '<p><input type="button" class="button" value="Добавить тег" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'addTag', $arguments)."'".'"></p>';
      $output .= $add_link;

      $sql = "SELECT * FROM ".$this->cms->tables["news_tags"]." ORDER BY BINARY(title) ASC, tag_id ASC";
      $result = $this->dbc->Execute($sql);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      elseif ($result->RecordCount()==0) {
      return $output;
      }
      $output = '
      <div class="admin-table2"><div class="width-fix"><table>
      <tr>
      <td class="title-h">Название</td>
      <td class="act-h">&nbsp;</td>
      <td class="act-h last" colspan="2">Действия</td>
      </tr>
      ';
      while($item = $result->FetchRow()) {
      $arg = array('tag_id'=>$item['tag_id']);
      if ($item['enabled']) {
      $switch = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switchStateTag', $arg).'" class="on">вкл</a>';
      }
      else {
      $switch = '<a href="'.$this->cms->format_url($this->plugin['name'], 'switchStateTag', $arg).'" class="off">выкл</a>';
      }
      $actions = array(
      'edit' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'editTag', $arg).'">редактировать</a>',
      'delete' => '<a onclick="return confirm(\'Удалить?\')" href="'.$this->cms->format_url($this->plugin['name'], 'deleteTag', $arg).'">удалить</a>'
      );
      if (strlen($item['title']) > 103) {
      $iTitle = substr($item['title'], 0, 100).'...';
      }
      else {
      $iTitle = $item['title'];
      }
      $output .= '
      <tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
      <td class="title"><a href="'.$this->cms->format_url($this->plugin['name'], 'editTag', $arg).'">'.$iTitle.'</a></td>
      <td class="act">'.$switch.'</td>
      <td class="act">'.$actions['edit'].'</td>
      <td class="act last">'.$actions['delete'].'</td>
      </tr>
      ';
      }
      $output .= '</table></div></div>';
      $add_link = '<p><input type="button" class="button" value="Добавить тег" onClick="javascript:window.location.href='."'".$this->cms->format_url($this->plugin['name'], 'addTag', $arguments)."'".'"></p>';
      $output .= $add_link;
      return $output;
      }
      function addTag($arguments) {

      $this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'tags', $arguments).'">Теги</a>';
      $this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'addTag', $arguments).'">Добавление тега</a>';
      $this->cms->intSetHierarchy();

      $output = '';
      $fob = $this->init_fob('fob', $this->cms->admin_url);
      $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
      $fob->add_hidden('$'.$this->cms->request_vars['command'], 'addTag');
      $fob->add_text(true, 'title', '', 'Тег', '', 'fob-text', '', 'text', '', '');
      $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', true, 'fob-check', '');

      $fob->add_button('submit', 'submit', 'Сохранить', '');
      $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
      $output .= $fob->display('return');
      if ($fob->is_submited()  && $fob->is_valid_submit() ) {
      $values = $fob->get_submited_values();
      $item = array(
      'title' => "'".addslashes(trim($values['title']))."'",
      'enabled' => (int)$values['enabled'],
      );

      $sql = "INSERT INTO ".$this->cms->tables["news_tags"]." (".join(",", array_keys($item)).") VALUES(".join(",", $item).")";
      $result = $this->dbc->Execute($sql);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      $newId = $this->dbc->insert_id();
      if($values["submit"]=="Сохранить и остаться") {
      $target_url = $this->cms->format_url($this->plugin['name'], 'editTag', array("tag_id"=>$newId));
      }
      else {
      $target_url = $this->cms->format_url($this->plugin['name'], 'tags', array());
      }
      header('Location: '.$target_url);
      return $output;

      }
      return $output;
      }

      function editTag($arguments) {
      $output = '';
      $id = (int) $arguments['tag_id'];
      $arg = $arguments;
      unset($arg["tag_id"]);

      $this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'tags', array()).'">Теги</a>';
      $this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'editTag', $arguments).'">Редактирование тега</a>';
      $this->cms->intSetHierarchy();

      $query = 'SELECT * FROM '.$this->cms->tables['news_tags'].' WHERE tag_id='.$id;
      $result = $this->dbc->Execute($query);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      elseif ($result->RecordCount()==0) {
      $this->cms->int_set_message('top', 'Required pic not found<br>'.$query, $this->plugin['name'], 'error');
      return false;
      }

      $item = $result->FetchRow();
      $fob = $this->init_fob('fob', $this->cms->admin_url);
      $fob->add_hidden('$'.$this->cms->request_vars['plugin'],  $this->plugin['name']);
      $fob->add_hidden('$'.$this->cms->request_vars['command'], 'editTag');
      $fob->add_hidden('$'.$this->cms->request_vars['arguments'].'[tag_id]', $id);

      $fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
      $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', $item['enabled'], 'fob-check', '');

      $fob->add_button('submit', 'submit', 'Сохранить', '');
      $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
      $output .= $fob->display('return');

      if ($fob->is_submited()  && $fob->is_valid_submit() ) {
      $values = $fob->get_submited_values();

      $update = array(
      'title' => "'".addslashes(trim($values['title']))."'",
      'enabled' => (int)$values['enabled'],
      );

      $update_sql = '';
      $update_sep = '';
      while (list($field, $value) = each($update)) {
      $update_sql .= $update_sep.$field.'='.$value.' ';
      $update_sep = ', ';
      }
      $query = 'UPDATE '.$this->cms->tables['news_tags'].' SET '.$update_sql.' WHERE tag_id='.$id;
      $result = $this->dbc->Execute($query);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
      return false;
      }
      unset($arguments["id"]);
      if($values["submit"]=="Сохранить и остаться") {
      $target_url = $this->cms->format_url($this->plugin['name'], 'editTag', array("tag_id"=>$id));
      }
      else {
      $target_url = $this->cms->format_url($this->plugin['name'], 'tags', array());
      }
      header('Location: '.$target_url);
      return $output;
      }
      return $output;
      }
      function switchStateTag($arguments) {
      $item_id	 = (int) $arguments['tag_id'];

      $query = 'UPDATE '.$this->cms->tables['news_tags'].' SET enabled=abs(enabled-1) WHERE tag_id='.$item_id;
      $result = $this->dbc->Execute($query);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      $arg = array();
      $target_url = $this->cms->format_url($this->plugin['name'], 'tags', $arg);
      header('Location: '.$target_url);
      return '';
      }
      function deleteTag($arguments) {
      $id		  = (int) $arguments['tag_id'];
      $query = 'SELECT * FROM '.$this->cms->tables['news_tags'].' WHERE tag_id='.$id;
      $result = $this->dbc->Execute($query);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      elseif ($result->RecordCount()==0) {
      return false;
      }
      $query = 'DELETE FROM '.$this->cms->tables['news_tags'].' WHERE tag_id='.$id;
      $result = $this->dbc->Execute($query);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return false;
      }
      $arg = array();
      $target_url = $this->cms->format_url($this->plugin['name'], 'tags', $arg);
      header('Location: '.$target_url);
      } */

    function updateItemTags($tags) {
        $tags = explode(',', trim($tags));
        foreach ($tags as $key => $value) {
            $value = trim(addslashes($value));
            $sql = "SELECT * FROM " . $this->cms->tables['tags'] . " WHERE title = '" . $value . "'";
            $search_result = $this->dbc->Execute($sql);
            //если тега нет - добавляем в таблицу тегов
            if ($search_result->RecordCount() == 0 && $value != '') {
                $sql = "INSERT INTO " . $this->cms->tables['tags'] . " SET title='" . $value . "'";
                $result = $this->dbc->Execute($sql);
            }
        }
    }

    /* 	function getAllTags() {
      $ret = array();
      $sql = "SELECT * FROM ".$this->cms->tables["news_tags"]." ORDER BY BINARY(title) ASC";
      $result = $this->dbc->Execute($sql);
      if (!$result) {
      $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$sql, 'SQL Error', 'error');
      return $ret;
      }
      elseif ($result->RecordCount()==0) {
      return $ret;
      }
      while($item = $result->FetchRow()) {
      $ret[$item['tag_id']] = $item;
      }
      return $ret;
      } */

    function init() {
        require_once(cms_LIB_PATH . 'form_builder.class.php');
        $this->dbc = $this->cms->dbc;
        $this->initTables();

        $this->cms->pathModule[] = '<a href="/admin/index.php?grp=modules">Програмные модули</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'index', array()) . '">' . $this->plugin['title'] . '</a>';

        $this->setDefaultVar();
    }

    function initTables() {
        $pathToTable = cms_PLUGINS_PATH . $this->plugin['name'] . '/tables.php';
        if (file_exists($pathToTable)) {
            require_once($pathToTable);
            $this->cms->tables = array_merge($this->tables, $this->cms->tables);
            $this->cms->install->dbc = &$this->dbc;
            $this->cms->install->tables = $this->tables;
            $this->cms->install->tablesColumn = $this->tableColumn;
            $this->cms->install->runInstall();
        }
    }

    function setDefaultVar() {
        $this->installDefaultOptions();
        //Размер для страницы новости
        $this->defaultWidth = $this->getValueOption("defaultWidth");
        $this->defaultHeight = $this->getValueOption("defaultHeight");

        //Размер для страницы списка новостей
        $this->defaultWidthSm = $this->getValueOption("defaultWidthSm");
        $this->defaultHeightSm = $this->getValueOption("defaultHeightSm");

        //Размер для страницы списка анонса новостей или что-то другое
        $this->defaultWidthSm1 = $this->getValueOption("defaultWidthSm1");
        $this->defaultHeightSm1 = $this->getValueOption("defaultHeightSm1");

        //Размер для страницы списка анонса новостей или что-то другое
        $this->defaultWidthSm2 = $this->getValueOption("defaultWidthSm2");
        $this->defaultHeightSm2 = $this->getValueOption("defaultHeightSm2");
    }

    function options($arguments) {
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'options', array()) . '">Настройки</a>';
        $this->cms->intSetHierarchy();

        $sql = "SELECT * FROM " . $this->cms->tables["news_options"] . " ORDER BY groups ASC, name DESC";
        $result = $this->dbc->Execute($sql);

        $output = $this->cms->int_set_h1("Изменение настроек модуля");

        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'options');
        $prevGroup = '';
        $fob->add_html('img', $this->cms->int_add_h3("Изменение настроек модуля"), 'html_code');
        while ($row = $result->FetchRow()) {
            if ($prevGroup != $row["groups"]) {
                $fob->add_html('img', '<hr>', 'html_code');
                $prevGroup = $row["groups"];
            }
            $name = $this->getNameOption($row["name"]);
            if ($name) {
                $fob->add_text(true, $row["name"], $row["value"], $name, '', 'fob-text', '', 'text', '', '');
            }
        }
        $fob->add_button('submit', 'submit', 'Save', '');
        $output .= $fob->display('return');
        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();
            foreach ($values as $name => $value) {
                $sql = "UPDATE " . $this->cms->tables['news_options'] . " SET value='" . (int)$value . "', value2='" . (float)str_replace(",", ".", $value) . "' WHERE name='" . $name . "'";
                $result = $this->dbc->Execute($sql);
            }
            $target_url = $this->cms->format_url($this->plugin['name'], 'options', array());
            header('Location: ' . $target_url);

            return $output;
        }

        return $output;
    }

    function getNameOption($name) {
        switch ($name) {
            case "defaultWidth":
                $ret = "Ширина в подробном";
                break;
            case "defaultHeight":
                $ret = "Высота в подробном";
                break;

            case "defaultWidthSm":
                $ret = "Ширина для списка";
                break;
            case "defaultHeightSm":
                $ret = "Высота для списка";
                break;

            case "defaultWidthSm1":
                $ret = "Ширина для анонса";
                break;
            case "defaultHeightSm1":
                $ret = "Высота для анонса";
                break;

            case "defaultWidthSm2":
                $ret = "Ширина для анонса 2";
                break;
            case "defaultHeightSm2":
                $ret = "Высота для анонса 2";
                break;

            default:
                $ret = '';
                break;
        }

        return $ret;
    }

    function installDefaultOptions() {
        $this->insertOption("defaultWidth", $this->defaultWidth, 1);
        $this->insertOption("defaultHeight", $this->defaultHeight, 1);

        $this->insertOption("defaultWidthSm", $this->defaultWidthSm, 2);
        $this->insertOption("defaultHeightSm", $this->defaultHeightSm, 2);

        $this->insertOption("defaultWidthSm1", $this->defaultWidthSm1, 3);
        $this->insertOption("defaultHeightSm1", $this->defaultHeightSm1, 3);

        $this->insertOption("defaultWidthSm2", $this->defaultWidthSm1, 4);
        $this->insertOption("defaultHeightSm2", $this->defaultHeightSm1, 4);
    }

    function insertOption($name, $value, $groups, $value2 = 0) {
        if (!$value2)
            $value2 = $value;

        $sql = "SELECT id FROM " . $this->cms->tables["news_options"] . " WHERE name='" . $name . "'";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() == 0) {
            $sql = "INSERT INTO " . $this->cms->tables["news_options"] . " SET name='" . $name . "', value='" . $value . "', value2='" . $value2 . "', groups='" . $groups . "'";
            $this->dbc->Execute($sql);
        }
    }

    function getValueOption($name, $field = "value") {
        $sql = "SELECT " . $field . " FROM " . $this->cms->tables["news_options"] . " WHERE name='" . $name . "'";
        $result = $this->dbc->Execute($sql);
        $item = $result->FetchRow();

        return $item['value'];
    }

    function print_tabs($active_tab, $arguments = array()) {
        $tabs = array(
            'index'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'index', $arguments) . '">' . $this->plugin['title'] . '</a>',
            'options' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'options', array()) . '">Настройки</a>',
            'words'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'words', $arguments) . '">Теги</a>',
        );
        $active_tab = $this->getActiveTabs($tabs, $active_tab);
        setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');
        $output = $this->cms->int_add_tabs($tabs, $active_tab);

        return $output;
    }

    function getActiveTabs(&$tabs, $activeTab) {
        if (array_key_exists($activeTab, $tabs))
            return $activeTab;
        switch ($activeTab) {
            case "options":
                $ret = "options";
            case "words":
                $ret = "words";
                break;
            default:
                $ret = "index";
                break;
        }

        return $ret;
    }

    function main($command, $arguments) {
        $this->init();
        if (method_exists($this, $command)) {
            $result = $this->$command($arguments);
            $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');

            return $result;
        }
        switch ($command) {
            default:
            case 'index':
                $result = $this->index($arguments);
                break;
        }
        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');

        return $result;
    }

    function words($arguments) {
        $output = '';
        $id = (int)$arguments['id'];
        $rubric_id = (int)$arguments['rubric_id'];
        $query = 'SELECT * FROM ' . $this->cms->tables['news'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');

            return false;
        } elseif ($result->RecordCount() <> 1) {
            return false;
        }
        $structure = $result->FetchRow();

        $arrFlip = array();
        if (!empty($structure["words"])) {
            $arrFlip = array_flip(explode("|", substr($structure["words"], 1, -1)));
        }

        $this->cms->int_set_hierarchy($id);

        $fob = $this->init_fob('fob', $this->cms->admin_url);

        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'words');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);

        $fob->add_html('sep', $this->cms->int_add_h2('Теги'));

        $src = '';
        $src .= '<div class="admin-table2"><div class="width-fix">';
        $src .= '<table>';
        $words = $this->getWords();
        $countWord = count($words);
        $src .= '<tr>';
//		echo "<pre>"; print_r($arrFlip); die();
        foreach ($words as $key => $word) {
            $src .= '<td class="act"><input type="checkbox" name="word[]" value="' . $word["word_id"] . '"' . ((isset($arrFlip[ $word["word_id"] ]) ? " checked" : "")) . '></td>';
            $src .= '<td class="act' . (($key % 5 == 0 && $key > 0) ? " last" : "") . '">' . $word["word_ru"] . '</td>';
            if ($key % 5 == 0 && $key > 0) {
                $src .= '</tr>';
                $src .= '<tr>';
            }
        }
        $src .= '</tr>';
        $src .= '</table>';
        $src .= '</div></div>';
        $fob->add_html('sep', $src);

        $fob->add_button('submit', 'submit', 'Сохранить', '', '', '', '', 'button', '', '');

        $variables['output'] = $fob->display('return');
        $output .= $this->cms->parse_template($variables, 'workarea');
        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();

            $str = '';
            $arrWordId = array();

            if (!empty($_POST["word"]) && count($_POST["word"])) {
                foreach ($_POST["word"] as $key => $val) {
                    if ((int)$val > 0) {
                        $arrWordId[] = (int)$val;
                    }
                }
                if (count($arrWordId) > 0) {
                    $sql = "UPDATE " . $this->cms->tables['news'] . " SET words='|" . implode("|", $arrWordId) . "|' WHERE id='" . $id . "'";
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
                }
            } else {
                $sql = "UPDATE " . $this->cms->tables['news'] . " SET words='' WHERE id='" . $id . "'";
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }
            }
//			echo "<pre>"; print_r($arrWordId); die();
            $target_url = $this->cms->format_url($this->plugin['name'], 'edit', array('id' => $id));
            //$target_url = $this->cms->format_url("sa", 'index', array());
            header('Location: ' . $target_url);

            return $this->index(array());
        }

        return $output;
    }

    function getWords() {
        $ret = array();
        $sql = "SELECT * FROM pm_words ORDER BY BINARY(word_ru) ASC";
        $result = $this->dbc->Execute($sql);
        if ($result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {
                $ret[] = $item;
            }
        }

        return $ret;
    }

    function init_languages() {

        $languages = array();
        $lang = "";

        // Читаем список поддерживаемых на сайте языков
        $sql = "SELECT language_id, short_title, alias FROM " . $this->cms->tables["languages"] . " ORDER BY ord ASC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        if ($result->RecordCount() > 0) {

            $counter = 1;
            while ($record = $result->FetchRow()) {

                // Заполняем массив поддерживаемых языков
                $languages[ $record["language_id"] ] = $record["alias"];

                // Определяем текущий язык сайта как первый в списке
                if ($counter == 1) {
                    $lang = $record["alias"];
                }

                $counter++;
            }
        }

        // Сохраняем список языков и текущий язык в переменных класса
        $this->langs = $languages;
        $this->lang = $lang;
    }

}

?>