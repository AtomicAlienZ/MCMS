<?php

class plugin_admin_interface extends cms_plugin_admin {

    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms    = '';

    // Флаг работы каталога в режиме интернет-магазина
    var $shop_flag = 1;

    // Список поддерживаемых языков
    var $langs = array();

    // Текущий язык сайта
    var $lang = "ru";

    // Список доступных табов
    var $tabs = array(
        "records"   => "Список каталогов",
        "orders"    => "Заказы",
        "preorders" => "Предзаказы",
        "actions"	=> "Акционные товары",
        "fieldsets" => "Наборы полей",
        "selects"   => "Списки выбора",
        "currency"  => "Валюта",
        "options"   => "Настройки"
    );

    // Список допустимых типов полей
    var $fields_types = array(
        "string"        => "Строка символов",
        "text"          => "Текст (N строк)",
        "html"          => "Текст (HTML-редактор)",
        "image"         => "Изображение",
        "select"        => "Выбор (1 вариант)",
        "multiple"      => "Выбор (N вариантов)",
        "multipleprice" => "Выбор (N вариантов) с ценой",
        "date"          => "Дата",
        "number"        => "Целое число",
        "float"         => "Вещественное число",
    );

    // Список допустимых типов полей в формате MySQL
    var $fields_types_mysql = array(
        "number"        => "int(11) NOT NULL default '0'",
        "float"         => "float(8,2) NOT NULL default '0.0'",
        "string"        => "varchar(255) NOT NULL default ''",
        "text"          => "text NOT NULL",
        "html"          => "text NOT NULL",
        "image"         => "varchar(255) NOT NULL default ''",
        "select"        => "varchar(255) NOT NULL default ''",
        "multiple"      => "varchar(255) NOT NULL default ''",
        "multipleprice" => "varchar(255) NOT NULL default ''",
        "date"          => "date NOT NULL default '0000-00-00'"
    );

    // Относительно путь к папке в которой сохраняются загруженные изображения и файлы
    var $path_upload = "content/catalog/";

    // Текущие опции каталога (значения = значения по умолчанию)
    var $options = array(
        "img_width"              => 800,
        "img_height"             => 800,
        "img_sm_width"           => 132,
        "img_sm_height"          => 132,
        "img_sm1_width"          => 115,
        "img_sm1_height"         => 115,
        "img_sm2_width"          => 56,
        "img_sm2_height"         => 56,
        "records_per_page"       => 3,
        "records_per_page_admin" => 5
    );

    // Названия опций каталога (в том же порядке, что и опции)
    var $options_titles = array(
        "img_width"              => "Ширина изображения элемента",
        "img_height"             => "Высота изображения элемента",
        "img_sm_width"           => "Ширина первой миниатюры",
        "img_sm_height"          => "Высота первой миниатюры",
        "img_sm1_width"          => "Ширина второй миниатюры",
        "img_sm1_height"         => "Высота второй миниатюры",
        "img_sm2_width"          => "Ширина третьей миниатюры",
        "img_sm2_height"         => "Высота третьей миниатюры",
        "records_per_page"       => "Количество элементов на странице",
        "records_per_page_admin" => "Количество элементов на странице для администратора"
    );

    // Текстовые идентификаторы списков для которых необходимо загрузить изображение
    var $options_img_enabled = array(
        "brands" => "Производители"
    );

    // Опции масштабирования для изображений списков
    var $options_img_selects = array(
        "img_height" => 80,
        "img_width"  => 80
    );

    var $delivery = array(
        0 => array(
            "title" => "Самовывоз",
            "price" => "0",
            "descr" => "Со складов в Киеве и Харькове."
        ),
        1 => array(
            "title" => "Доставка курьером",
            "price" => "4",
            "descr" => "Быстрая доставка курьером, только для Киева. При цене заказа более 100\$ доставка производится бесплатно!"
        ),
        2 => array(
            "title" => "Доставка в регионы",
            "price" => "20",
            "descr" => "Доставка службой Ночной Экспресс, Автолюкс, Нова Пошта в любую точку Украины за 1 день. <br />Оплачивается(доставка) по факту получения, стоимость смотрите на сайте: <a href=\"http://www.nexpress.com.ua/\" targer=\"_blank\">www.nexpress.com.ua</a>, <a href=\"http://www.novaposhta.com.ua/\" targer=\"_blank\">www.novaposhta.com.ua</a> , <a href=\"http://www.autolux.ua\" targer=\"_blank\">www.autolux.ua</a>."
        )
    );

    //............................................................ Основная функция запуска плагина

    // Функция инициализации плагина
    function main($command, $arguments) {

        // Подключаем класс для работы с формами
        require_once(cms_LIB_PATH . 'form_builder.class.php');

        // Инициализируем список поддерживаемых языков
        $this->init_languages();

        // Инициализируем таблицы
        $this->dbc = $this->cms->dbc;
        $this->init_tables();

        // Инициализируем опции модуля
        $this->init_options();

        // Если каталог работает в обычном режиме, а не как интернет магазин, то удаляем из меню ссылку на заказы
        if (!$this->shop_flag) {
            unset($this->tabs["orders"]);
        }

        // Формируем путь к текущей страничке
        $this->cms->pathModule[] = '<a href="/admin/index.php?' . $this->cms->request_vars['group'] . '=modules">Програмные модули</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'index', array()) . '">Каталог</a>';

        // Если запрашиваемая команда отсутствует, то показываем первый таб
        if (!method_exists($this, $command)) {
            reset($this->tabs);
            $command = key($this->tabs);
        }

        // Выполняем запрошенную команду
        $result = $this->$command($arguments);

        // Печатаем табы в административном интерфейсе
        $result = $this->cms->parse_template(array('tabs' => $this->print_tabs($command, $arguments), 'output' => $result), 'workarea');

        return $result;
    }

    //........................................................................... Элементы каталога

    // Функция печати списка элементов каталога
    function records($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $id = 0;
        if (isset($arguments['id'])) {
            $id = (int)$arguments['id'];
        }
        $page = 0;
        if (isset($arguments['page'])) {
            $page = (int)$arguments['page'];
        }

        // Читаем путь к текущему элементу
        $path = $this->get_path($id);

        // Читаем список определенных наборов полей
        $fieldsets = $this->get_fieldsets();

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->intSetHierarchy();

        // Печатаем ссылку возврата на уровень выше
        if (!empty($path)) {
            if (count($path) == 1) {
                $parent_id = 0;
            } else {
                // Читаем родительский идентификатор, как второй элемент в пути с конца
                $parent_id = current(array_slice(array_keys($path), -2, 1));
            }
            $output .= '<p><a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $parent_id)) . '"><img src="/admin/img/cat_up.gif" class="ico">На уровень вверх</a></p>';
        }

        // Рассчитываем общее количество элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog'] . ' WHERE parent_id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $record = $result->FetchRow();
        $num_records = $record["num_records"];

        // Выводим список элементов
        if ($num_records > 0) {

            // Рассчитываем количество записей на страничку
            $records_per_page = (int)$this->options["records_per_page_admin"];

            // Корректируем номер страницы
            if ($page < 1) {
                $page = 1;
            }
            if ($page > ceil($num_records / $records_per_page)) {
                $page = ceil($num_records / $records_per_page);
            }

            // Формируем строку навигации по записям
            $navigation_string = $this->get_navigation_string($page, $num_records, $records_per_page, 'records', array("id" => $id));

            // Читаем записи из каталога
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE parent_id=' . $id . ' ORDER BY ord DESC, title_' . $this->lang . ', id LIMIT ' . ($page - 1) * $records_per_page . ', ' . $records_per_page;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

            // Начинаем формирование формы для групповой обработки элементов
            $output .= '<form name="update" method="post">';

            // Печатаем заголовок таблицы
            $output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
                    <td class="title-h" style="width: 5%">ID</td>
					<td class="title-h" style="width: 45%">Название<br>Алиас</td>
					<td class="act-h" style="width: 5%"><img src="/admin/img/folder.gif" width="12" height="14" alt="Разделы" title="Разделы"></td>
					<td class="act-h" style="width: 5%"><img src="/admin/img/page.gif" width="12" height="14" alt="Элементы" title="Элементы"></td>
					<td class="act-h">Цена</td>
<!--					<td class="act-h">Акция N-му юзеру</td>-->
					<!--<td class="act-h">Состояние</td>-->
					<td class="act-h" style="width: 10%">Приор.</td>
					<td class="act-h" style="width: 10%">Вкл./Выкл.</td>
					<td class="act-h" style="width: 20%">Набор полей</td>
					<td class="act-h" colspan="4">Операции</td>
					<td class="act-h last"><input type="checkbox" onclick="javascript:setCheckboxes(\'update\', \'items[]\', this.checked);"></td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            $findItem = false;
            while ($item = $result->FetchRow()) {
                if ($item["type"] == 'item') {
                    $findItem = true;
                }

                // Формируем список аргументов
                $arguments['id'] = $item['id'];
                $arguments['page'] = $page;

                // Находим название набора полей
                if (!empty($fieldsets[ $item["fieldset"] ])) {
                    $fieldset = htmlspecialchars($fieldsets[ $item["fieldset"] ]);
                } else {
                    $fieldset = "Пустой";
                }

                // Рассчитываем число вложенных разделов и элементов
                $num_elements = $this->get_num_elements($item["id"]);
                $num_folders = $num_elements["folders"];
                $num_items = $num_elements["items"];

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                // Формируем параметры, зависящие от типа элемента
                if ($item["type"] == "folder") {
                    $title_link = $this->cms->format_url($this->plugin['name'], "records", array("id" => $item["id"]));
                    $type_class = "folder";
                } else {
                    $title_link = $this->cms->format_url($this->plugin['name'], "records_edit", $arguments);
                    $type_class = "page";
                }

                // Формируем список доступных действий
                $actions = array(
                    'comments' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments', $arguments) . '">отзывы</a>',
                    'copy'     => '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_copy', $arguments) . '">копировать</a>',
                    'edit'     => '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', $arguments) . '">изменить</a>',
                    'delete'   => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'records_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
     <td class="title" style="width: 5%">' . $item["id"] . '</td>
					<td class="title" style="width: 45%"><div class="' . $type_class . '"><a href="' . $title_link . '" title="id: ' . $item["id"] . '">' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '</a><br>' . htmlspecialchars($item['alias']) . '</div></td>
					<td class="act" style="width: 5%">' . $num_folders . '</td>
					<td class="act" style="width: 5%">' . $num_items . '</td>
					<td class="act">' . (($item["type"] == 'item') ? '<input type="text" name="price[' . $item["id"] . ']" style="width: 54px;" class="fob-text" value="' . $item["price"] . '">' : '') . '</td>
<!--					<td class="act">' . (($item["type"] == 'item') ? '<input type="text" name="action_for_user[' . $item["id"] . ']" style="width: 54px;" class="fob-text" value="' . $item["action_for_user"] . '">' : '') . '</td>-->';

                if ($item["type"] == 'item') {
//                    $output .= '<td class="act"><select name="state[' . $item["id"] . ']">';
//                    $output .= '<option value="0"' . (($item["state"] == 0) ? " selected" : "") . '>Набор открыт</option>';
//                    $output .= '<option value="1"' . (($item["state"] == 1) ? " selected" : "") . '>Набор закрыт</option>';
//					$output .= '<option value="2"'.(($item["state"]==2)?" selected":"").'>ожидается</option>';
//                    $output .= '</select></td>';
                } else {
//                    $output .= '<td class="act">&nbsp;</td>';
                }

                $output .= '
					<td class="act" style="width: 10%">' . $item["ord"] . '</td>
					<td class="act" style="width: 10%">' . $switch . '</td>
					<td class="act" style="width: 20%"><a href="' . $this->cms->format_url($this->plugin['name'], "records_edit_fieldset", $arguments) . '">' . $fieldset . '</a></td>';
                if ($item["type"] == 'item') {
                    $output .= '<td class="act">' . $actions['comments'] . '</td>';
                    $output .= '<td class="act">' . $actions['copy'] . '</td>';
                } else {
                    $output .= '<td class="act">&nbsp;</td>';
                    $output .= '<td class="act">&nbsp;</td>';
                }
                $output .= '
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act">' . $actions['delete'] . '</td>
					<td class="act last"><input type="checkbox" name="items[]" value="' . $item['id'] . '"></td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список элементов пуст</p>';
        }

        // Печатаем форму групповых операций над элементами
        $output .= '<table style="float:right">';
        $output .= '<tr>';
        $output .= '<td>Выбранные элементы:&nbsp;</td>';
        $output .= '<td>';
        $output .= '<select onchange="if(this.value>\'\'){if(this.value==\'delete\'){if(confirm(\'Вы действительно желаете удалить выбранные элементы?\')!==true){return false;}}document.forms[\'update\'].action=\'' . $this->cms->format_url($this->plugin['name'], 'records_update', array("id" => $id, "page" => $page)) . '&action=\'+this.value;document.forms[\'update\'].submit();}" style="align:right">';
        $output .= '<option value="">выбрать операцию...</option>';
        $output .= '<option value="on">включить</option>';
        $output .= '<option value="off">выключить</option>';
        $output .= '<option value="delete">удалить</option>';
        $output .= '</select>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        // Заканчиваем формирование формы для групповых операций
        $output .= '</form>';

        // Печатаем форму добавления нового элемента
        $output .= '<form name="records">';
        $output .= '<input type="button" class="button" value="Добавить раздел" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'records_add', array("id" => $id, "type" => "folder")) . "&arg[fieldset]=empty'" . '">&nbsp;&nbsp;';

        $output .= '</select>';
        $output .= '<input type="button" class="button" value="Добавить элемент" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'records_add', array("id" => $id, "type" => "item")) . "&arg[fieldset]='+document.forms['records'].fieldset.value" . '">';
        $output .= 'Тип нового элемента: ';
        $output .= '<select name="fieldset">';
//		$output .= '<option value="empty">Пустой</option>';
        if (!empty($fieldsets)) {
            foreach ($fieldsets as $key => $value) {
                $output .= '<option value="' . $key . '">' . htmlspecialchars($value) . '</option>\n';
            }
        }
        if (@$findItem === true) {
            $output .= '&nbsp;&nbsp;<input type="button" class="button" value="Сохранить изменения" onClick="document.forms[\'update\'].action=\'' . $this->cms->format_url($this->plugin['name'], 'update_price', array("id" => $id, "page" => $page)) . '\';document.forms[\'update\'].submit();">';
        }
        $output .= '</form>';

        if (file_exists(ROOT_HTML_PATH . "content/files/price.xls")) {
            $output .= '<h3><a href="/content/files/price.xls?t=' . time() . '">Прайс</a></h3>';
        }

        return $output;
    }

    function records_copy($arguments) {
        $id = (int)$arguments['id'];
        $page = 1;
        if (isset($arguments['page'])) {
            $page = (int)$arguments['page'];
        }

        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            $item = $result->FetchRow();
            unset($item["id"]);
            $alias = $item["alias"] . "_" . time();
            $relative = $this->get_relative_url($item["parent_id"], $alias);
            $item["enabled"] = 0;
            $item["alias"] = $alias;
            $item["relative_url"] = $relative['relative_url'];
            $item["full_relative_url"] = $relative['full_relative_url'];
            $item["title_ru"] = $item['title_ru'] . " копия";
            $update_sql = array();
            foreach ($item as $key => $val) {
                $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
            }
            $sql = "INSERT INTO " . $this->cms->tables["catalog"] . " SET " . join(", ", $update_sql);
//			echo $sql."<br>";
            $result = $this->dbc->Execute($sql);
            $new_id = $this->dbc->insert_id();

            $imgs = $this->copy_images_item($item, $id, $new_id);
            $update_sql = array();
            foreach ($imgs as $key => $val) {
                $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
            }
            $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET " . join(", ", $update_sql) . " WHERE id='" . $new_id . "'";
//			echo $sql."<br>";
            $this->dbc->Execute($sql);

            $table = PREFIX_TABLE . "_catalog_table_" . $item["fieldset"];
            $sql = "SELECT * FROM " . $table . " WHERE record_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                $item_data = $result->FetchRow();
                unset($item_data["id"]);
                $item_data["record_id"] = $new_id;
                $update_sql = array();
                foreach ($item_data as $key => $val) {
                    $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
                }
                $sql = "INSERT INTO " . $table . " SET " . join(", ", $update_sql);
//				echo $sql."<br>";
                $this->dbc->Execute($sql);
            }

            $table = $this->cms->tables["catalog_gallery"];
            $sql = "SELECT * FROM " . $table . " WHERE record_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                while ($item_data = $result->FetchRow()) {
                    $gallery_id = $item_data["id"];
//					unset($item_data["id"]);
                    $item_data["record_id"] = $new_id;
                    $update_sql = array();
                    foreach ($item_data as $key => $val) {
                        $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
                    }
                    $sql = "INSERT INTO " . $table . " SET " . join(", ", $update_sql);
//					echo $sql."<br>";
                    $this->dbc->Execute($sql);
                    $new_gallery_id = $this->dbc->insert_id();

                    $imgs = $this->copy_gallery_item($item_data, $id, $new_id, $gallery_id, $new_gallery_id);
                    $update_sql = array();
                    foreach ($imgs as $key => $val) {
                        $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
                    }
                    $sql = "UPDATE " . $table . " SET " . join(", ", $update_sql) . " WHERE id='" . $new_gallery_id . "'";
//					echo $sql."<br>";
                    $this->dbc->Execute($sql);
                }
            }

            $table = $this->cms->tables["catalog_like"];
            $sql = "SELECT * FROM " . $table . " WHERE from_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                while ($item_data = $result->FetchRow()) {
                    unset($item_data["id"]);
                    $item_data["from_id"] = $new_id;
                    $update_sql = array();
                    foreach ($item_data as $key => $val) {
                        $update_sql[] = $key . "='" . addslashes(trim($val)) . "'";
                    }
                    $sql = "INSERT INTO " . $table . " SET " . join(", ", $update_sql);
//					echo $sql."<br>";
                    $this->dbc->Execute($sql);
                }
            }
        }
        $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $item["parent_id"], "page" => $page));
        header('Location: ' . $target_url);
    }

    function copy_images_item($item, $record_id, $new_id) {
        $ret = array(
            "img"     => "",
            "img_sm"  => "",
            "img_sm1" => "",
            "img_sm2" => "",
        );

        $from_target = ROOT_HTML_PATH . substr($item["img"], 1);
        $filename = str_replace("_" . $record_id . ".", "_" . $new_id . ".", $item["img"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm"], 1);
        $filename = str_replace("_" . $record_id . "_", "_" . $new_id . "_", $item["img_sm"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm1"], 1);
        $filename = str_replace("_" . $record_id . "_", "_" . $new_id . "_", $item["img_sm1"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm1"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm2"], 1);
        $filename = str_replace("_" . $record_id . "_", "_" . $new_id . "_", $item["img_sm2"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm2"] = $filename;

        return $ret;
    }

    function copy_gallery_item($item, $record_id, $new_record_id, $gallery_id, $new_gallery_id) {
        $ret = array(
            "img"     => "",
            "img_sm"  => "",
            "img_sm1" => "",
            "img_sm2" => "",
        );

        $from_target = ROOT_HTML_PATH . substr($item["img"], 1);
        $filename = str_replace("_" . $record_id . "_" . $gallery_id . ".", "_" . $new_record_id . "_" . $new_gallery_id . ".", $item["img"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm"], 1);
        $filename = str_replace("_" . $record_id . "_" . $gallery_id . "_", "_" . $new_record_id . "_" . $new_gallery_id . "_", $item["img_sm"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm1"], 1);
        $filename = str_replace("_" . $record_id . "_" . $gallery_id . "_", "_" . $new_record_id . "_" . $new_gallery_id . "_", $item["img_sm1"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm1"] = $filename;

        $from_target = ROOT_HTML_PATH . substr($item["img_sm2"], 1);
        $filename = str_replace("_" . $record_id . "_" . $gallery_id . "_", "_" . $new_record_id . "_" . $new_gallery_id . "_", $item["img_sm2"]);
        $target = ROOT_HTML_PATH . substr($filename, 1);
        @copy($from_target, $target);
        $ret["img_sm2"] = $filename;

        return $ret;
    }

    // Функция добавления элемента каталога
    function records_add($arguments) {

        // Читаем идентификатор родительской записи, тип элемента и добавляемый набор полей
        $parent_id = (int)$arguments['id'];
        $type = $arguments['type'];
        $fieldset = $arguments['fieldset'];

        $output = '';

        // Читаем список определенных наборов полей
        $fieldsets = $this->get_fieldsets();
        $fieldsets = array("empty" => "Пустой") + $fieldsets;
        if (empty($fieldset) || !isset($fieldsets[ $fieldset ])) {
            $fieldset = "empty";
        }

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Читаем полное дерево каталога
        $tree = $this->get_tree();
        $tree = array("0" => "Список каталогов") + $tree;

        // Формируем список возможных типов элемента
        $types = array(
            "folder" => "Раздел",
            "item"   => "Элемент"
        );

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_add', array()) . '">Добавление элемента</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления элемента каталога
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'records_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[fieldset]', $fieldset);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $parent_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[type]', $type);

        // Печатаем название набора полей
        $fob->add_html('sep', $this->cms->int_add_h1("Новый элемент &quot;" . $fieldsets[ $fieldset ] . "&quot;"));

        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, '', 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
//		$fob->add_text(false, 'code', '', 'Код товара', '', 'fob-text', '', 'text', '', '');

        $fob->add_text(true, 'alias', time(), 'Алиас', '', 'fob-text', '', 'text', '', '');

        // Печатаем списки выбора типа элемента
        $fob->add_select(true, 'type', $types, 'Тип элемента', '', $type, '');

        // Печатаем список выбора базового раздела
        $fob->add_select(false, 'parent_id', $tree, 'Базовый раздел', '', $parent_id, '');

        // Ключевые слова и описание для мета-тегов
        $fob->add_html('meta', $this->cms->int_add_h2('Мета теги:'));
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_title_' . $lang, '', 'Title', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_keywords_' . $lang, '', 'Ключевые слова', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_description_' . $lang, '', 'Описание', $lang, 'fob-text', '', 'text', '', '');
        }

        // Информация об элементе
        $fob->add_html('info', $this->cms->int_add_h2('Информация о элементе:'));
        $fob->add_file("img", 'Основное изображение');

        foreach ($this->langs as $lang) {
            $fob->add_wysiwyg(false, 'short_description_' . $lang, '', 'Краткое описание', $lang, 57, 20, 'fob-textarea');
        }
        foreach ($this->langs as $lang) {
            $fob->add_wysiwyg(false, 'description_' . $lang, '', 'Описание', $lang, 57, 40, 'fob-textarea');
        }

        if ($type == "item") {
//			foreach($this->langs as $lang) {
//				$fob->add_textarea(false, 'video_'.$lang, '', 'Код видео', $lang, 57, 3, 'fob-textarea');
//			}
            // Если включен режим интернет-магазина, то отображаем поле цены
            if ($this->shop_flag) {
                $fob->add_html('info', $this->cms->int_add_h2(''));

// additional fields, plisovyi, 20-09-11
//$fob->add_date('tour_start', Date('d.m.Y', time()), 'Дата начала');
//$fob->add_date('tour_finish', Date('d.m.Y', time()), 'Дата конца');
//$fob->add_text(false, 'season', 0, 'Время проведения', '', 'fob-text', '', 'text', '', '');
//$fob->add_text(false, 'duration', 0, 'Продолжительность', '', 'fob-text', '', 'text', '', '');
//$fob->add_text(false, 'all_places', 0, 'Количество мест (всего)', '', 'fob-text', '', 'text', '', '');
//$fob->add_text(false, 'booked_places', 0, 'Количество занятых мест', '', 'fob-text', '', 'text', '', '');
//$fob->add_textarea(false, 'itinerary', '', 'Маршрут', 'meta', 30, 3, 'fob-textarea');
// EO additional


                $fob->add_text(false, 'price', 0, 'Цена, $.', '', 'fob-text', '', 'text', '', '');
//              $fob->add_select(false, 'state', $this->getState(), 'Состояние', '', 0, '');
//				$fob->add_text(false, 'action_for_user', 0, 'В подарок каждому N-му посетителю', '', 'fob-text', '', 'text', '', '');
            }

//			$fob->add_html('info', $this->cms->int_add_h2(''));
//			$fob->add_checkbox(false, 'hot', 1, 'Горящий', '', '', (bool) 0, 'fob-check', '');
//            $fob->add_checkbox(false, 'cheap', 1, 'Бюджетный', '', '', (bool) 0, 'fob-check', '');
//            $fob->add_checkbox(false, 'nregular', 1, 'Нерегулярный', '', '', (bool) 0, 'fob-check', '');

//            $fob->add_checkbox(false, 'news', 1, 'Новинка', '', '', (bool) 0, 'fob-check', '');
//			$fob->add_checkbox(false, 'best', 1, 'Бестселлер', '', '', (bool) 0, 'fob-check', '');
//			$fob->add_checkbox(false, 'action', 1, 'Акционный', '', '', (bool) 0, 'fob-check', '');
//			$fob->add_checkbox(false, 'recomended', 1, 'Рекомендуемый', '', '', (bool) 0, 'fob-check', '');
        }
        $fob->add_text(false, 'ord', 0, 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)1, 'fob-check', '');

        // Вызываем функцию для формирования дополнительных полей элемента
        if ($fieldset != "empty") {
            $fob = $this->records_additional_form($fob, $fieldset);
        }

        $src = '';
        $src .= '<b>Теги:</b><br>';
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

        $fob->add_button('submit', 'save', 'Сохранить', '');
        $fob->add_button('submit', 'save_stay', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        // Добавляем элемент в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();
//			echo "<prE>"; print_r($values); echo "</pre>";
//			echo "<prE>"; print_r($_POST); echo "</pre>";

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем родительский идентификатор
            $parent_id = 0 + (int)$values['parent_id'];

            if ($this->is_valid_alias($values['alias']) === false) {
                $this->cms->int_set_message('top', 'Алиас ' . $values['alias'] . ' имеет не корректный формат. Алиас может содержать латинские буквы a-z, цифры 0-9 и символы "-", "_"', 'SQL Error', 'error');

                return false;
            }
            if ($this->check_exist_alias($parent_id, $values['alias']) === true) {
                $this->cms->int_set_message('top', 'Алиас ' . $values['alias'] . ' уже существует', 'SQL Error', 'error');

                return false;
            }

            $relative = $this->get_relative_url($parent_id, $values['alias']);

            $dateStartArrs = explode('.', $values['tour_start']);
            $dateStartArrf = explode('.', $values['tour_finish']);

            // Формируем SQL-запрос
            $insert_sql = array(
                'type'              => "'" . $values['type'] . "'",
                'parent_id'         => $parent_id,
                'fieldset'          => "'" . $fieldset . "'",
                'price'             => (float)str_replace(",", ".", $values['price']),
                'ord'               => (int)$values['ord'],
                'created'           => "NOW()",
                'enabled'           => (0 + !empty($values['enabled'])),
                'alias'             => "'" . addslashes(trim($values['alias'])) . "'",
                //				'code'	=> "'".addslashes(trim($values['code']))."'",
                'relative_url'      => "'" . addslashes(trim($relative['relative_url'])) . "'",
                'full_relative_url' => "'" . addslashes(trim($relative['full_relative_url'])) . "'",
                //				'hot'	=> (int)$values['hot'],
                //				'cheap'	=> (int)$values['cheap'],
                //				'nregular'	=> (int)$values['nregular'],
                //				'recomended'	=> (int)$values['recomended'],
                'state'             => (int)$values['state'],
                //				'action_for_user'	=> (int)$values['action_for_user'],
                // additional fields, plisovyi, 20-09-11
                //                'tour_start' => "'" . $dateStartArrs[2] . "-" . $dateStartArrs[1] . "-" . $dateStartArrs[0] . "'",
                //                'tour_finish' => "'" . $dateStartArrf[2] . "-" . $dateStartArrf[1] . "-" . $dateStartArrf[0] . "'",
                'duration'          => "'" . addslashes(trim($values['duration'])) . "'",
                'season'            => "'" . addslashes(trim($values['season'])) . "'",
                //                'all_places' => (int)$values['all_places'],
                //                'booked_places' => (int)$values['booked_places'],
                //                'itinerary' => "'".addslashes(trim($values['itinerary']))."'",
                // EO additional
            );
            if (!empty($_POST["word"]) && count($_POST["word"])) {
                foreach ($_POST["word"] as $key => $val) {
                    if ((int)$val > 0) {
                        $arrWordId[] = (int)$val;
                    }
                }
                if (count($arrWordId) > 0) {
                    $insert_sql["words"] = "'|" . implode("|", $arrWordId) . "|'";
                }
            }
            foreach ($this->langs as $lang) {
                $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $insert_sql[ "meta_title_" . $lang ] = "'" . addslashes(trim($values[ 'meta_title_' . $lang ])) . "'";
                $insert_sql[ "meta_keywords_" . $lang ] = "'" . addslashes(trim($values[ 'meta_keywords_' . $lang ])) . "'";
                $insert_sql[ "meta_description_" . $lang ] = "'" . addslashes(trim($values[ 'meta_description_' . $lang ])) . "'";
                $insert_sql[ "short_description_" . $lang ] = "'" . addslashes(trim($values[ 'short_description_' . $lang ])) . "'";
                $insert_sql[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
//				$insert_sql["video_".$lang] 			= "'".addslashes(trim($values['video_'.$lang]))."'";
            }
            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
//			die($sql);
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Читаем идентификатор только что добавленной записи
            $record_id = $this->dbc->insert_id();

            // Корректируем ошибку в form_builder из-за которой она неправильно обрабатывает
            // многомерные переменные в формах, заменяя такие переменные данными из $_POST
            $values["additional"] = $_POST["fob"]["additional"];
            $values["additional_p"] = array();
            if (isset($_POST["fob"]["additional_p"])) {
                $values["additional_p"] = $_POST["fob"]["additional_p"];
            }

            // Добавляем в базу данных дополнительные поля
            if ($fieldset != "empty") {
                $this->records_additional_add($record_id, $fieldset, $values["additional"], $values["additional_p"]);
            }

            // Копируем фотографии в папку каталога
            if (!empty($files)) {
                $this->upload_images($record_id, $fieldset, $files);
            }

            $this->createExcelFile();

            // Делаем редирект либо на страничку редактирования элемента, либо на список сестринских элементов
            if (!empty($values["save_stay"])) {
                $target_url = $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id, "page" => $page));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $parent_id, "page" => $page));
            }
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования элемента каталога
    function records_edit($arguments) {

        $output = "";

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Читаем полное дерево каталога
        $tree = $this->get_tree();
        $tree = array("0" => "Список каталогов") + $tree;

        // Формируем список возможных типов элемента
        $types = array(
            "folder" => "Раздел",
            "item"   => "Элемент"
        );

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $id)) . '">Редактирование элемента</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму редактирование элемента каталога
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'records_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[page]', $page);

        // Печатаем название элемента
        $fob->add_html('sep', $this->cms->int_add_h1($item[ 'title_' . $this->lang ]));

        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, $item[ 'title_' . $lang ], 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
//		$fob->add_text(false, 'code', $item['code'], 'Код товара', '', 'fob-text', '', 'text', '', '');

        $fob->add_text(true, 'alias', $item['alias'], 'Алиас', '', 'fob-text', '', 'text', '', '');

        // Печатаем списки выбора типа элемента
        $fob->add_select(true, 'type', $types, 'Тип элемента', '', $item['type'], '');

        // Печатаем список выбора базового раздела
        $fob->add_select(false, 'parent_id', $tree, 'Базовый раздел', '', $item['parent_id'], '');

        // Формируем ссылки на список изображений и файлов, прикрепленных к элементу
        $url_gallery = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $id));
        $url_files = $this->cms->format_url($this->plugin['name'], 'files', array("id" => $id));
        $fob->add_html('gallery', '<br><a href="' . $url_gallery . '">Прикрепленные изображения</a>', 'input');
//		$fob->add_html('files', '<a href="'.$url_files.'">Прикрепленные файлы</a>', 'input');

        // Ключевые слова и описание для мета-тегов
        $fob->add_html('meta', $this->cms->int_add_h2('Мета теги:'));
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_title_' . $lang, $item[ 'meta_title_' . $lang ], 'Title', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_keywords_' . $lang, $item[ 'meta_keywords_' . $lang ], 'Ключевые слова', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'meta_description_' . $lang, $item[ 'meta_description_' . $lang ], 'Описание', $lang, 'fob-text', '', 'text', '', '');
        }

        if ($item["type"] == "item") {
            $fob->add_html('meta', $this->cms->int_add_h2('Похожие товары:'));

            $sql = "SELECT c.id, c.title_" . $this->lang . " as title FROM " . $this->cms->tables["catalog"] . " c LEFT JOIN " . $this->cms->tables["catalog_like"] . " cl ON cl.to_id=c.id WHERE cl.from_id='" . $id . "'";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
            $txt = '';
            if ($result->RecordCount() > 0) {
                $txt .= '
					<div class="admin-table2"><div class="width-fix"><table>
						<tr>
							<td class="title-h" style="width: 10px;">#</td>
							<td class="title-h" style="width: 90%">Название</td>
							<td class="act-h last">Удалить</td>
						</tr>
				';
                $counter = 1;
                while ($item_like = $result->FetchRow()) {
                    $txt .= '
						<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
							<td class="title" style="width: 10px;">' . $counter++ . '</td>
							<td class="title" style="width: 90%">' . $item_like['title'] . '</td>
							<td class="act last"><input type="checkbox" name="delete_like[]" value="' . $item_like['id'] . '"></td>
						</tr>
					';
                }
                $txt .= '</table></div></div>';
                $fob->add_html('txt', $txt);
            }


            $likes = array();
            $this->get_likes($id, $likes);
            $fob->add_select(false, 'likes[]', $likes, 'Добавить похожий товар<br /><br />Что бы выбрать несколько элементов,<br />удержывайте кнопку <b>Ctrl</b>. Для выбора диапазона<br />элементов, удержывайте кнопку <b>Shift</b>', '', -1, '', 20, true);
        }

        // Информация об элементе
        $fob->add_html('info', $this->cms->int_add_h2('Информация о элементе:'));

        // Показываем изображение, если оно было загружено
        if (!empty($item["img_sm"])) {
            $image = '<a href="' . $item['img'] . '?rnd=' . md5(time()) . '" target="_blank"><img src="' . $item['img_sm'] . '?rnd=' . md5(time()) . '" border="0" /></a>';
            $fob->add_html('img', $image, 'input');
            $fob->add_checkbox(false, 'img_delete', 1, '', 'Удалить', '', 0, 'fob-check', '');
        }
        $fob->add_file("img", 'Изображение');

        foreach ($this->langs as $lang) {
            //$fob->add_textarea(false, 'short_description_'.$lang, $item['short_description_'.$lang], 'Краткое описание', $lang, 57, 3, 'fob-textarea');
            $fob->add_wysiwyg(false, 'short_description_' . $lang, $item[ 'short_description_' . $lang ], 'Краткое описание', $lang, 57, 20, 'fob-textarea');
        }
        foreach ($this->langs as $lang) {
            $fob->add_wysiwyg(false, 'description_' . $lang, $item[ 'description_' . $lang ], 'Описание', $lang, 57, 40, 'fob-textarea');
        }

        if ($item["type"] == "item") {
//			foreach($this->langs as $lang) {
//				$fob->add_textarea(false, 'video_'.$lang, $item['video_'.$lang], 'Код видео', $lang, 57, 3, 'fob-textarea');
//			}
            // Если включен режим интернет-магазина, то отображаем поле цены
            if ($this->shop_flag) {
                $fob->add_html('info', $this->cms->int_add_h2(''));

                // additional fields, plisovyi, 20-09-11
//                 $fob->add_date('tour_start',  Date('d.m.Y', strtotime($item['tour_start'])), 'Дата начала');
//                 $fob->add_date('tour_finish',  Date('d.m.Y', strtotime($item['tour_finish'])), 'Дата конца');
//                $fob->add_text(false, 'season', $item['season'], 'Время проведения', '', 'fob-text', '', 'text', '', '');
//                $fob->add_text(false, 'duration', $item['duration'], 'Продолжительность', '', 'fob-text', '', 'text', '', '');
//                 $fob->add_text(false, 'all_places', $item['all_places'], 'Количество мест (всего)',  '', 'fob-text', '', 'text', '', '');
//                 $fob->add_text(false, 'booked_places', $item['booked_places'], 'Количество занятых мест',  '', 'fob-text', '', 'text', '', '');
//                 $fob->add_textarea(false, 'itinerary',  $item['itinerary'], 'Маршрут', 'meta', 30, 3, 'fob-textarea');
                // EO additional

                $fob->add_text(false, 'price', $item['price'], 'Цена, $.', '', 'fob-text', '', 'text', '', '');
                $fob->add_select(false, 'state', $this->getState(), 'Состояние', '', $item['state'], '');
//				$fob->add_text(false, 'count_last', $item['count_last'], 'Осталось в наличии', '', 'fob-text', '', 'text', '', '');
//				$fob->add_text(false, 'action_for_user', $item['action_for_user'], 'В подарок каждому N-му посетителю', '', 'fob-text', '', 'text', '', '');
            }

//			$fob->add_html('info', $this->cms->int_add_h2(''));
//
//            $fob->add_checkbox(false, 'hot', 1, 'Горящий', '', '', (bool) $item['hot'], 'fob-check', '');
//            $fob->add_checkbox(false, 'cheap', 1, 'Бюджетный', '', '', (bool) $item['cheap'], 'fob-check', '');
//            $fob->add_checkbox(false, 'nregular', 1, 'Нерегулярный', '', '', (bool) $item['nregular'], 'fob-check', '');


//			$fob->add_checkbox(false, 'news', 1, 'Новинка', '', '', (bool) $item['news'], 'fob-check', '');
//			$fob->add_checkbox(false, 'best', 1, 'Бестселлер', '', '', (bool) $item['best'], 'fob-check', '');
//			$fob->add_checkbox(false, 'action', 1, 'Акционный', '', '', (bool) $item['action'], 'fob-check', '');
//			$fob->add_checkbox(false, 'recomended', 1, 'Рекомендуемый', '', '', (bool) $item['recomended'], 'fob-check', '');
        }

        $fob->add_text(false, 'ord', $item['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)$item['enabled'], 'fob-check', '');

        // Вызываем функцию для формирования дополнительных полей элемента
        $fob = $this->records_additional_form($fob, $item["fieldset"], $id);


        $arrFlip = array();
        if (!empty($item["words"])) {
            $arrFlip = array_flip(explode("|", substr($item["words"], 1, -1)));
        }

        $src = '';
        $src .= '<b>Теги:</b><br>';
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

        $fob->add_button('submit', 'save', 'Сохранить', '');
        $fob->add_button('submit', 'save_stay', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        // Обновляем информацию об элементе
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Корректируем набор полей, если выбран пустой набор
            if (empty($values['fieldset'])) {
                $values['fieldset'] == "empty";
            }

            // Формируем родительский идентификатор
            if ($values['parent_id'] != $id) {
                $parent_id = 0 + (int)$values['parent_id'];
            }

            if ($this->is_valid_alias($values['alias']) === false) {
                $this->cms->int_set_message('top', 'Алиас ' . $values['alias'] . ' имеет не корректный формат. Алиас может содержать латинские буквы a-z, цифры 0-9 и символы "-", "_"', 'SQL Error', 'error');

                return false;
            }
            if ($this->check_exist_alias($parent_id, $values['alias'], $id) === true) {
                $this->cms->int_set_message('top', 'Алиас ' . $values['alias'] . ' уже существует', 'SQL Error', 'error');

                return false;
            }
            $relative = $this->get_relative_url($parent_id, $values['alias']);
            if ($values['alias'] != $item['alias']) {
                $this->update_child_relative($item, $relative);
            }


//             $dateStartArrs = explode('.', $values['tour_start']);
//             $dateStartArrf = explode('.', $values['tour_finish']);

            // Формируем SQL-запрос на обновление
            $update = array(
                'type'              => "'" . $values['type'] . "'",
                'parent_id'         => $parent_id,
                'price'             => (float)str_replace(",", ".", $values['price']),
                'ord'               => (int)$values['ord'],
                'enabled'           => (0 + !empty($values['enabled'])),
                //				'code'	=> "'".addslashes(trim($values['code']))."'",
                'alias'             => "'" . addslashes(trim($values['alias'])) . "'",
                'relative_url'      => "'" . addslashes(trim($relative['relative_url'])) . "'",
                'full_relative_url' => "'" . addslashes(trim($relative['full_relative_url'])) . "'",
                //				'hot'	=> (0 + !empty($values['hot'])),
                //				'cheap'	=> (0 + !empty($values['cheap'])),
                //				'nregular'	=> (0 + !empty($values['nregular'])),
                //                'tour_start' => "'" . $dateStartArrs[2] . "-" . $dateStartArrs[1] . "-" . $dateStartArrs[0] . "'",
                //                'tour_finish' => "'" . $dateStartArrf[2] . "-" . $dateStartArrf[1] . "-" . $dateStartArrf[0] . "'",
                'duration'          => "'" . addslashes(trim($values['duration'])) . "'",
                'season'            => "'" . addslashes(trim($values['season'])) . "'",
                //                'all_places' => (int)$values['all_places'],
                //                'booked_places' => (int)$values['booked_places'],
                //                'itinerary' => "'".addslashes(trim($values['itinerary']))."'",
                'state'             => (int)$values['state'],
                //				'action_for_user'	=> (int)$values['action_for_user'],
            );
            if (!empty($_POST["word"]) && count($_POST["word"])) {
                foreach ($_POST["word"] as $key => $val) {
                    if ((int)$val > 0) {
                        $arrWordId[] = (int)$val;
                    }
                }
                if (count($arrWordId) > 0) {
                    $update["words"] = "'|" . implode("|", $arrWordId) . "|'";
                }
            }
            foreach ($this->langs as $lang) {
                $update[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $update[ "meta_title_" . $lang ] = "'" . addslashes(trim($values[ 'meta_title_' . $lang ])) . "'";
                $update[ "meta_keywords_" . $lang ] = "'" . addslashes(trim($values[ 'meta_keywords_' . $lang ])) . "'";
                $update[ "meta_description_" . $lang ] = "'" . addslashes(trim($values[ 'meta_description_' . $lang ])) . "'";
                $update[ "short_description_" . $lang ] = "'" . addslashes(trim($values[ 'short_description_' . $lang ])) . "'";
                $update[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
//				$update["video_".$lang] 			= "'".addslashes(trim($values['video_'.$lang]))."'";
            }

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }

            $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Корректируем ошибку в form_builder из-за которой она неправильно обрабатывает
            // многомерные переменные в формах, заменяя такие переменные данными из $_POST
            $values["additional"] = $_POST["fob"]["additional"];
            $values["additional_p"] = array();
            if (isset($_POST["fob"]["additional_p"])) {
                $values["additional_p"] = $_POST["fob"]["additional_p"];
            }
            $values["img_additional_delete"] = $_POST["fob"]["img_additional_delete"];

            // Обновляем значения дополнительных полей
            if ($item["fieldset"] != "empty") {
                $this->records_additional_edit($id, $item["fieldset"], $values["additional"], $values["additional_p"]);
            }

            // Формируем список изображений, которые нужно удалить
            $images = array();
            $images["img"] = 0 + !empty($values['img_delete']);
            if (isset($files["img"]) && $files["img"]["error"] == 0) {
                $images["img"] = 1;
            }
            if ($item["fieldset"] != "empty") {

                // Читаем информацию о наборе полей, которые содержат изображения
                $fieldset = $this->get_fieldsets_fields($item["fieldset"]);
                foreach ($fieldset as $field_id => $field) {
                    if ($field["type"] != "image") {
                        unset($fieldset[ $field_id ]);
                    }
                }

                // Если в наборе полей есть изображения
                if (!empty($fieldset)) {
                    foreach ($fieldset as $field_id => $field) {
                        $images["additional"][ $field_id ] = 0 + !empty($values["img_additional_delete"][ $field_id ]);
                        if (isset($files["img_additional"]["name"][ $field_id ]) && $files["img_additional"]["error"][ $field_id ] == 0) {
                            $images["additional"][ $field_id ] = 1;
                        }
                    }
                }
            }

            // Удаляем выбранные пользователем и загружаемые заново изображения
            $this->delete_images($id, $images);

            // Загружаем изображения из формы
            if (!empty($files)) {
                $this->upload_images($id, $item["fieldset"], $files);
            }

            if (isset($_POST["fob"]["likes"]) && count($_POST["fob"]["likes"]) > 0) {
                foreach ($_POST["fob"]["likes"] as $like_id) {
                    $this->add_one_item_like($id, $like_id);
                }
            }
            if (isset($_POST["delete_like"]) && count($_POST["delete_like"]) > 0) {
                foreach ($_POST["delete_like"] as $delete_like) {
                    $this->delete_one_item_like($id, $delete_like);
                }
            }

            $this->createExcelFile();


            // Делаем редирект либо на страничку редактирования элемента, либо на список сестринских элементов
            if (!empty($values["save_stay"])) {
                $target_url = $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $id, "page" => $page));
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $parent_id, "page" => $page));
            }
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция изменения набора полей элемента
    function records_edit_fieldset($arguments) {

        $output = "";

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем список определенных наборов полей и добавляем к нему "пустой набор"
        $fieldsets = $this->get_fieldsets();
        $fieldsets = array("empty" => "Пустой") + $fieldsets;

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Читаем полное дерево каталога
        $tree = $this->get_tree();
        $tree = array("0" => "Список каталогов") + $tree;

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit_fieldset', array("id" => $id)) . '">Изменение набора полей</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму редактирование элемента каталога
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'records_edit_fieldset');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[page]', $page);

        // Печатаем название элемента
        $fob->add_html('sep', $this->cms->int_add_h1($item[ 'title_' . $this->lang ]));

        // Печатаем список наборов полей
        $fob->add_select(false, 'fieldset', $fieldsets, 'Набор полей', '', $item['fieldset'], '');

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию об элементе
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Корректируем набор полей, если выбран пустой набор
            if (empty($values['fieldset'])) {
                $values['fieldset'] == "empty";
            }

            // Формируем SQL-запрос на обновление
            $update = array(
                'fieldset' => "'" . $values['fieldset'] . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }

            $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Корректируем дополнительную информацию в таблицах при изменении набора полей
            if ($values['fieldset'] != $item["fieldset"]) {

                // Удаляем дополнительную информацию старого типа о записи
                $this->records_additional_delete($id, $item["fieldset"]);

                // Добавляем новую запись с выбранным типом и набором полей по умолчанию
                if ($values['fieldset'] != "empty") {
                    $this->records_additional_add($id, $values['fieldset'], "");
                }
            }

            // Делаем редирект на список сестринских элементов
            $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $parent_id, "page" => $page));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления элемента каталога
    function records_delete($arguments) {

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Читаем информацию об удаляемом элементе
        $record = $this->get_record($id);

        // Начинаем формирование массив элементов для удаления с исходного элемента
        $tree = array();
        $tree[ $id ] = $record[ "title_" . $this->lang ];

        // Если элемент является папкой, то читаем дерево вложенных элементов
        if ($record["type"] == "folder") {
            $tree += $this->get_tree($id, "all");
        }

        // Удаляем все дерево элементов
        $this->records_delete_tree($tree);

        $this->createExcelFile();

        // Делаем редирект на список сестринских элементов
        $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $record["parent_id"], "page" => $page));
        header('Location: ' . $target_url);
    }

    // Функция включения и выключения элемента каталога
    function records_switch_state($arguments) {

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Читаем информацию об элементе
        $record = $this->get_record($id);

        // Переключаем состояние элемента
        $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET enabled = abs(enabled - 1) WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        $this->createExcelFile();

        // Делаем редирект на список сестринских элементов
        $target_url = $this->cms->format_url($this->plugin['name'], 'records', array("id" => $record["parent_id"], "page" => $page));
        header('Location: ' . $target_url);
    }

    // Функция обработки групповых операций над элементами каталога
    function records_update($arguments) {

        // Читаем команду, которую выполнить
        if (!empty($_GET["action"])) {
            $action = $_GET["action"];
        } else {
            $action = "";
        }

        // Читаем список выбранных записей
        if (!empty($_POST["items"])) {
            $items = $_POST["items"];
        } else {
            $items = "";
        }

        // Если выбрана хотя бы одна запись
        if (!empty($items)) {

            // Включаем выбранные элементы каталога
            if ($action == "on") {

                $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET enabled=1 WHERE id IN (' . implode(", ", $items) . ')';
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }

                // Выключаем выбранные элементы каталога
            } elseif ($action == "off") {

                $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET enabled=0 WHERE id IN (' . implode(", ", $items) . ')';
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }

                // Удаляем выбранные элементы каталога
            } elseif ($action == "delete") {

                // Формируем дерево элементов для удаления
                $tree = array();
                foreach ($items as $item_id) {

                    // Читаем информацию об удаляемом элементе
                    $record = $this->get_record($item_id);

                    // Начинаем формирование массив элементов для удаления с исходного элемента
                    $tree[ $item_id ] = $record[ "title_" . $this->lang ];

                    // Если элемент является папкой, то читаем дерево вложенных элементов
                    if ($record["type"] == "folder") {
                        $tree += $this->get_tree($item_id, "all");
                    }
                }

                // Удаляем все дерево элементов
                $this->records_delete_tree($tree);
            }
        }

        $this->createExcelFile();

        // Делаем редирект на список сестринских элементов
        $target_url = $this->cms->format_url($this->plugin['name'], 'records', $arguments);
        header('Location: ' . $target_url);
    }

    // Функция обработки групповых операций над элементами каталога
    function update_price($arguments) {

        // Читаем список выбранных записей
        if (!empty($_POST["price"])) {
            $items = $_POST["price"];
        } else {
            $items = "";
        }

        // Если выбрана хотя бы одна запись
        if (!empty($items)) {

            foreach ($items as $id => $price) {
                if (isset($_POST["state"][ $id ])) {
                    $state = (int)$_POST["state"][ $id ];
                } else {
                    $state = 0;
                }
                if (isset($_POST["action_for_user"][ $id ])) {
                    $action_for_user = (int)$_POST["action_for_user"][ $id ];
                } else {
                    $action_for_user = 0;
                }
                $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET price=' . (int)str_replace(",", ".", $price) . ', state=' . $state . ', action_for_user=' . $action_for_user . ' WHERE id=' . $id . '';
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }
            }
        }

        $this->createExcelFile();

        // Делаем редирект на список сестринских элементов
        if (!isset($arguments["id"])) {
            $target_url = $this->cms->format_url($this->plugin['name'], 'actions', $arguments);
        } else {
            $target_url = $this->cms->format_url($this->plugin['name'], 'records', $arguments);
        }
        header('Location: ' . $target_url);
    }

    // Функция удаления дерева элементов
    function records_delete_tree($tree) {

        // По очереди удаляем все дерево элементов
        foreach ($tree as $record_id => $record) {

            // Читаем информацию об удаляемом элементе
            $record = $this->get_record($record_id);

            // Удаляем изображения элемента каталога
            $this->delete_images($record_id);

            // Удаляем дополнительную информацию об элементе
            if ($record["fieldset"] != "empty") {
                $this->records_additional_delete($record_id, $record["fieldset"]);
            }

            // Выполняем SQL-запрос на удаление элемента каталога
            $sql = 'DELETE FROM ' . $this->cms->tables['catalog'] . ' WHERE id = ' . $record_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Выполняем SQL-запрос на удаление элемента каталога
            $sql = "DELETE FROM " . $this->cms->tables["catalog_like"] . " WHERE from_id='" . $record_id . "' OR to_id='" . $record_id . "'";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
        }
    }

    //..................................................... Элементы каталога (дополнительные поля)

    // Функция формирования дополнительных полей элемента
    function records_additional_form($fob, $fieldset, $record_id = 0) {

        // Дополнительные поля формируются только если задан не пустой набор
        if ($fieldset != "empty") {

            // Если форма вызывается для редактирования элемента, то читаем данные из базы
            if (!empty($record_id)) {
                $additional = $this->get_record_additional($record_id, $fieldset);
            }

            // Читаем информацию о дополнительных полях
            $sql = "SELECT * FROM " . $this->cms->tables["catalog_fieldsets_data"] . " WHERE fieldset='" . $fieldset . "' ORDER BY ord DESC";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
            if ($result->RecordCount() > 0) {

                $fob->add_html('info', $this->cms->int_add_h2('Дополнительные поля:'));

                // По очереди отрисовываем в форме все дополнительные поля элемента
                while ($record = $result->FetchRow()) {

                    // .................. Инициализация значения поля

                    // Целое число
                    if ($record["type"] == "number") {

                        $value = 0;
                        if (isset($additional[ $record['id_text'] ])) {
                            $value = (int)$additional[ $record['id_text'] ];
                        }
                        $fob->add_text(false, 'additional[' . $record['id_text'] . ']', $value, $record['title'], $lang, 'fob-text', '', 'text', '', '');

                        // Вещественное число
                    } elseif ($record["type"] == "float") {

                        $value = 0;
                        if (isset($additional[ $record['id_text'] ])) {
                            $value = (float)$additional[ $record['id_text'] ];
                        }
                        $fob->add_text(false, 'additional[' . $record['id_text'] . ']', $value, $record['title'], $lang, 'fob-text', '', 'text', '', '');

                        // Строка символов
                    } elseif ($record["type"] == "string") {

                        $value = "";
                        foreach ($this->langs as $lang) {
                            if (isset($additional[ $record['id_text'] . "_" . $lang ])) {
                                $value = $additional[ $record['id_text'] . "_" . $lang ];
                            }
                            $fob->add_text(false, 'additional[' . $record['id_text'] . '_' . $lang . ']', $value, $record['title'], $lang, 'fob-text', '', 'text', '', '');
                        }

                        // Текст (N строк)
                    } elseif ($record["type"] == "text") {

                        $value = "";
                        foreach ($this->langs as $lang) {
                            if (isset($additional[ $record['id_text'] . "_" . $lang ])) {
                                $value = $additional[ $record['id_text'] . "_" . $lang ];
                            }
                            $fob->add_textarea(false, 'additional[' . $record['id_text'] . '_' . $lang . ']', $value, $record['title'], $lang, 57, 3, 'fob-textarea');
                        }

                        // Текст (HTML-редактор)
                    } elseif ($record["type"] == "html") {

                        $value = "";
                        foreach ($this->langs as $lang) {
                            if (isset($additional[ $record['id_text'] . "_" . $lang ])) {
                                $value = $additional[ $record['id_text'] . "_" . $lang ];
                            }
                            $fob->add_wysiwyg(false, 'additional[' . $record['id_text'] . '_' . $lang . ']', $value, $record['title'], $lang, 57, 20, 'fob-textarea');
                        }

                        // Изображение
                    } elseif ($record["type"] == "image") {

                        // Показываем изображение, если оно было загружено
                        $image_id = $record['id_text'];
                        $image_id_sm = $record['id_text'] . "_sm";
                        if (!empty($additional[ $image_id ])) {
                            $image = '<a href="' . $additional[ $image_id ] . '?rnd=' . md5(time()) . '" target="_blank"><img src="' . $additional[ $image_id_sm ] . '?rnd=' . md5(time()) . '" border="0" /></a>';
                            $fob->add_html('img', $image, 'input');
                            $fob->add_checkbox(false, "img_additional_delete[" . $record['id_text'] . "]", 1, '', 'Удалить', '', 0, 'fob-check', '');
                        }

                        $fob->add_file("img_additional[" . $record['id_text'] . "]", $record['title']);

                        // Выбор (1 вариант)
                    } elseif ($record["type"] == "select") {

                        $value = 0;
                        if (isset($additional[ $record['id_text'] ])) {
                            $value = (int)$additional[ $record['id_text'] ];
                        }

                        // Рассчитываем список выбора
                        $select = trim($record["options"]);
                        $select_options = $this->get_select_options($select);

                        $fob->add_select(false, 'additional[' . $record['id_text'] . ']', $select_options, $record['title'], '', $value, '');

                        // Выбор (N вариантов)
                    } elseif ($record["type"] == "multiple") {

                        $value = array();
                        if (isset($additional[ $record['id_text'] ])) {
                            $value = explode("|", $additional[ $record['id_text'] ]);
                            $tmp = array();
                            foreach ($value as $valTmp) {
                                if (!empty($valTmp)) {
                                    $tmp[] = $valTmp;
                                }
                            }
                            $value = $tmp;
                        }

                        // Рассчитываем список выбора
                        $select = trim($record["options"]);
                        $select_options = $this->get_select_options($select);

                        $field_title = $record['title'];
                        foreach ($select_options as $option_id => $option_title) {

                            // При формировании элемента checkbox, содержащего многомерные массивы, убираем первую закрывающую скобку,
                            // т.е. вместо additional[n][m] пишем additional[n[m] - это ошибка в form_builder
                            $fob->add_checkbox(false, 'additional[' . $record['id_text'] . '[' . $option_id . ']', $option_id, $field_title, $option_title, '', (bool)in_array($option_id, $value), 'fob-check', '');

                            // Пишем название списка только рядом с первым пунктом
                            if (!empty($field_title)) {
                                $field_title = "";
                            }
                        }

                        // Выбор (N вариантов) с ценой
                    } elseif ($record["type"] == "multipleprice") {

                        $value = array();
                        if (isset($additional[ $record['id_text'] ])) {
                            $value = explode("|", $additional[ $record['id_text'] ]);
                            $tmp = array();
                            foreach ($value as $valTmp) {
                                if (!empty($valTmp)) {
                                    $arrVls = explode(":", $valTmp);
                                    $tmp[ $arrVls[0] ] = $arrVls[1];
                                }
                            }
                            $value = $tmp;
                        }

                        // Рассчитываем список выбора
                        $select = trim($record["options"]);
                        $select_options = $this->get_select_options($select, 1);

                        $field_title = $record['title'];
                        foreach ($select_options as $option_id => $option_title) {

                            // При формировании элемента checkbox, содержащего многомерные массивы, убираем первую закрывающую скобку,
                            // т.е. вместо additional[n][m] пишем additional[n[m] - это ошибка в form_builder
                            $fob->add_checkbox(false, 'additional[' . $record['id_text'] . '[' . $option_id . ']', $option_id, $field_title, $option_title["title"], '', isset($value[ $option_id ]), 'fob-check', '');
                            $fob->add_text(false, 'additional_p[' . $record['id_text'] . '[' . $option_id . ']', isset($value[ $option_id ]) ? $value[ $option_id ] : $option_title["def"], 'Цена с ' . $option_title["title"], '', 'fob-text', '', 'text', '', '');

                            // Пишем название списка только рядом с первым пунктом
                            if (!empty($field_title)) {
                                $field_title = "";
                            }
                        }

                        // Дата
                    } elseif ($record["type"] == "date") {

                        $value = date("d.m.Y");
                        if (isset($additional[ $record['id_text'] ])) {

                            // Преобразуем дату в нужный формат
                            $date_array = explode("-", $additional[ $record['id_text'] ]);
                            $value = $date_array[2] . "." . $date_array[1] . "." . $date_array[0];
                        }
                        $fob->add_date("additional[" . $record['id_text'] . "]", $value, $record['title'], '', 'fob-date');
                    }
                }
            }
        }

        return $fob;
    }

    // Функция добавления дополнительных полей к элементу
    function records_additional_add($record_id, $fieldset, $values, $values_price = array()) {

        // Формируем название таблицы, которая хранит дополнительную информацию
        $table = PREFIX_TABLE . "_catalog_table_" . $fieldset;

        // Читаем список полей
        $fields = $this->get_fieldsets_fields($fieldset);

        // Формируем данные для SQL-запроса
        $insert_sql = array();
        foreach ($fields as $field_id => $field) {

            // Целые значения
            if ($field["type"] == "number" || $field["type"] == "select") {

                if (isset($values[ $field_id ])) {
                    $insert_sql[ $field_id ] = (int)$values[ $field_id ];
                }

                // Вещественные значения
            } elseif ($field["type"] == "float") {

                if (isset($values[ $field_id ])) {
                    $insert_sql[ $field_id ] = (float)str_replace(",", ".", $values[ $field_id ]);
                }

                // Поля с поддержкой нескольких языков
            } elseif ($field["type"] == "string" || $field["type"] == "text" || $field["type"] == "html") {

                // Обрабатываем по очереди каждый язык
                foreach ($this->langs as $lang) {

                    if (isset($values[ $field_id . "_" . $lang ])) {
                        $insert_sql[ $field_id . "_" . $lang ] = "'" . addslashes(trim($values[ $field_id . "_" . $lang ])) . "'";
                    }
                }

                // Упакованные целые значения списка в формате "|value1|value2|value3|"
            } elseif ($field["type"] == "multiple") {

                if (isset($values[ $field_id ])) {

                    // Обрабатываем элементы списка
                    $list = array();
                    if (is_array($values[ $field_id ])) {
                        foreach ($values[ $field_id ] as $key => $value) {
                            $list[ $key ] = (int)$value;
                        }
                    }

                    // Упаковываем элементы списка в строку
                    $insert_sql[ $field_id ] = "'|" . implode("|", $list) . "|'";
                }
                // Упакованные целые значения списка в формате "|value1:price1|value2:price2|value3:price3|"
            } elseif ($field["type"] == "multipleprice") {

                if (isset($values[ $field_id ])) {

                    // Обрабатываем элементы списка
                    $list = array();
                    if (is_array($values[ $field_id ])) {
                        foreach ($values[ $field_id ] as $key => $value) {
                            $list[ $key ] = (int)$value . (isset($values_price[ $field_id ][ $key ]) ? ":" . $values_price[ $field_id ][ $key ] : "");
                        }
                    }

                    // Упаковываем элементы списка в строку
                    $insert_sql[ $field_id ] = "'|" . implode("|", $list) . "|'";
                }

                // Дата в формате "dd.mm.YYYY" преобразуется в формат "YYYY-mm-dd"
            } elseif ($field["type"] == "date") {

                if (isset($values[ $field_id ])) {

                    // Формируем дату в нужном формате
                    $date_array = explode(".", $values[ $field_id ]);
                    $date = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];

                    $insert_sql[ $field_id ] = "'" . addslashes($date) . "'";
                }

                // Обычная строка
            } else {

                if (isset($values[ $field_id ])) {
                    $insert_sql[ $field_id ] = "'" . addslashes(trim($values[ $field_id ])) . "'";
                }
            }
        }

        // Добавляем идентификатор записи
        $insert_sql["record_id"] = $record_id;

        // Выполняем SQL-запрос
        $sql = 'INSERT INTO ' . $table . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
    }

    // Функция редактирования дополнительных полей элемента
    function records_additional_edit($record_id, $fieldset, $values, $values_price = array()) {
        // Формируем название таблицы, которая хранит дополнительную информацию
        $table = PREFIX_TABLE . "_catalog_table_" . $fieldset;

        // Читаем список полей
        $fields = $this->get_fieldsets_fields($fieldset);

        // Формируем данные для SQL-запроса
        $update = array();
        foreach ($fields as $field_id => $field) {

            // Целое число
            if ($field["type"] == "number") {

                if (isset($values[ $field_id ])) {
                    $update[ $field_id ] = (int)$values[ $field_id ];
                }

                // Вещественные значения
            } elseif ($field["type"] == "float") {

                if (isset($values[ $field_id ])) {
                    $update[ $field_id ] = (float)str_replace(",", ".", $values[ $field_id ]);
                }

                // Поля с поддержкой нескольких языков
            } elseif ($field["type"] == "string" || $field["type"] == "text" || $field["type"] == "html") {

                // Обрабатываем по очереди каждый язык
                foreach ($this->langs as $lang) {

                    if (isset($values[ $field_id . "_" . $lang ])) {
                        $update[ $field_id . "_" . $lang ] = "'" . addslashes(trim($values[ $field_id . "_" . $lang ])) . "'";
                    }
                }

                // Одиночный список
            } elseif ($field["type"] == "select") {

                if (isset($values[ $field_id ])) {
                    $update[ $field_id ] = (int)$values[ $field_id ];
                }

                // Упакованные целые значения списка в формате "|value1|value2|value3|"
            } elseif ($field["type"] == "multiple") {

                // Обрабатываем элементы списка
                $list = array();
                if (isset($values[ $field_id ]) && is_array($values[ $field_id ])) {
                    foreach ($values[ $field_id ] as $key => $value) {
                        $list[ $key ] = (int)$value;
                    }
                }

                // Упаковываем элементы списка в строку
                $update[ $field_id ] = "'|" . implode("|", $list) . "|'";

                // Упакованные целые значения списка в формате "|value1:price1|value2:price3|value3:price3|"
            } elseif ($field["type"] == "multipleprice") {

                // Обрабатываем элементы списка
                $list = array();
                if (isset($values[ $field_id ]) && is_array($values[ $field_id ])) {
                    foreach ($values[ $field_id ] as $key => $value) {
                        $list[ $key ] = (int)$value . (isset($values_price[ $field_id ][ $key ]) ? ":" . $values_price[ $field_id ][ $key ] : "");
                    }
                }

                // Упаковываем элементы списка в строку
                $update[ $field_id ] = "'|" . implode("|", $list) . "|'";

                // Дата в формате "dd.mm.YYYY" преобразуется в формат "YYYY-mm-dd"
            } elseif ($field["type"] == "date") {

                if (isset($values[ $field_id ])) {

                    // Формируем дату в нужном формате
                    $date_array = explode(".", $values[ $field_id ]);
                    $date = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];

                    $update[ $field_id ] = "'" . addslashes($date) . "'";
                }

                // Обычная строка
            } else {

                if (isset($values[ $field_id ])) {
                    $update[ $field_id ] = "'" . addslashes(trim($values[ $field_id ])) . "'";
                }
            }
        }

        // Выполняем SQL-запрос
        if (!empty($update)) {

            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = '`' . $key . '` = ' . $val;
            }

            $sql = 'UPDATE ' . $table . ' SET ' . join(', ', $update_sql) . ' WHERE record_id=' . $record_id;

            $result = $this->dbc->Execute($sql);

            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                die($sql);

                return false;
            }
        }
    }

    // Функция удаления дополнительных полей элемента
    function records_additional_delete($record_id, $fieldset) {

        // Формируем название таблицы, которая хранит дополнительную информацию
        $table = PREFIX_TABLE . "_catalog_table_" . $fieldset;

        // Выполняем SQL-запрос на удаление дополнительных полей элемента
        $sql = 'DELETE FROM ' . $table . ' WHERE record_id=' . $record_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
    }

    //................................................................................. Фотогалерея

    // Функция печати списка изображений, прикрепленных к элементу
    function gallery($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $record_id = (int)$arguments['id'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id)) . '">Фотогалерея элемента</a>';
        $this->cms->intSetHierarchy();

        // Печатаем ссылку возврата к редактированию элемента
        $output .= '<p><a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '"><img src="/admin/img/cat_up.gif" class="ico">Вернуться к элементу</a></p>';

        // Читаем список изображений
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE record_id=' . $record_id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Выводим список изображений
        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				<td class="title-h" style="width: 10px;">#</td>
				<td class="title-h" style="width: 70%">Название фотографии</td>
				<td class="act-h" style="width: 15%">Изображение</td>
				<td class="act-h" style="width: 7%">Приор.</td>
				<td class="act-h" style="width: 8%">Вкл./Выкл.</td>
				<td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $record_id;
                $arguments['eid'] = $item['id'];

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'gallery_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 70%"><a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_edit', $arguments) . '">' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '</a></td>
					<td class="act" style="width: 15%"><a href="' . $item["img"] . '" target="_blank"><img src="' . $item["img_sm1"] . '" /></a></td>
					<td class="act" style="width: 7%">' . $item["ord"] . '</td>
					<td class="act" style="width: 8%">' . $switch . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Прикрепленные изображения отсутствуют</p>';
        }

        // Создаем форму добавления записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'gallery');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $record_id);

        for ($i = 1; $i <= 5; $i++) {
            $fob->add_html('sep', $this->cms->int_add_h1("Новое изображение " . $i));
            $fob->add_file("img_" . $i, 'Изображение');
            foreach ($this->langs as $lang) {
                $fob->add_text(false, 'title_' . $lang . "_" . $i, '', 'Название', $lang, 'fob-text', '', 'text', '', '');
            }
            $fob->add_text(false, 'ord_' . $i, 0, 'Приоритет', '', 'fob-text', '', 'text', '', '');
            $fob->add_text(false, 'meta_title_' . $i, '', 'Meta: title', '', 'fob-text', '', 'text', '', '');
            $fob->add_text(false, 'meta_description_' . $i, '', 'Meta: description', '', 'fob-text', '', 'text', '', '');
            $fob->add_text(false, 'meta_keywords_' . $i, '', 'Meta: keywords', '', 'fob-text', '', 'text', '', '');
            $fob->add_checkbox(false, 'enabled_' . $i, 1, 'Включено', '', '', 1, 'fob-check', '');
        }

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем запись в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            for ($i = 1; $i <= 5; $i++) {
                // Формируем SQL-запрос
                $insert_sql = array(
                    'record_id'        => $record_id,
                    'ord'              => (int)$values[ 'ord_' . $i ],
                    'created'          => "NOW()",
                    'enabled'          => (0 + !empty($values[ 'enabled_' . $i ])),
                    'meta_title'       => "'" . $values[ 'meta_title_' . $i ] . "'",
                    'meta_description' => "'" . $values[ 'meta_description_' . $i ] . "'",
                    'meta_keywords'    => "'" . $values[ 'meta_keywords_' . $i ] . "'"
                );
                foreach ($this->langs as $lang) {
                    $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang . '_' . $i ])) . "'";
                }

                // Выполняем SQL-запрос
                $sql = 'INSERT INTO ' . $this->cms->tables['catalog_gallery'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }

                // Читаем идентификатор только что добавленного изображения
                $image_id = $this->dbc->insert_id();

                // Копируем загруженное изображение в папку каталога
                if (!empty($files)) {
                    $this->upload_images_gallery($record_id, $image_id, $files, 'img_' . $i);
                }
            }
            // Делаем редирект на список записей
            $target_url = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id));
            header('Location: ' . $target_url);
        }

        // Печатаем кнопку добавления нового изображения
//		$output .= '<input type="button" class="button" value="Добавить изображение" onClick="location.href='."'".$this->cms->format_url($this->plugin['name'], 'gallery_add', array("id"=>$record_id))."'".'">';

        return $output;
    }

    // Функция добавления нового изображения к элементу каталога
    function gallery_add($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $record_id = (int)$arguments['id'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id)) . '">Фотогалерея элемента</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_add', array("id" => $record_id)) . '">Добавление изображения</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'gallery_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $record_id);

        // Печатаем название элемента каталога
        $fob->add_html('sep', $this->cms->int_add_h1("Новое изображение для &quot;" . htmlspecialchars($item[ "title_" . $this->lang ]) . "&quot;"));

        // Кнопка выбора изображения для загрузки
        $fob->add_file("img", 'Изображение');

        // Название и описание фотографии
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'title_' . $lang, '', 'Название', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_textarea(false, 'description_' . $lang, '', 'Краткое описание', $lang, 57, 3, 'fob-textarea');
        }

        $fob->add_text(false, 'meta_title', '', 'Meta: title', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_description', '', 'Meta: description', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', '', 'Meta: keywords', '', 'fob-text', '', 'text', '', '');

        $fob->add_text(false, 'ord', 0, 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 1, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем запись в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос
            $insert_sql = array(
                'record_id'        => $record_id,
                'ord'              => (int)$values['ord'],
                'created'          => "NOW()",
                'enabled'          => (0 + !empty($values['enabled'])),
                'meta_title'       => "'" . $values['meta_title'] . "'",
                'meta_description' => "'" . $values['meta_description'] . "'",
                'meta_keywords'    => "'" . $values['meta_keywords'] . "'"

            );
            foreach ($this->langs as $lang) {
                $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $insert_sql[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_gallery'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Читаем идентификатор только что добавленного изображения
            $image_id = $this->dbc->insert_id();

            // Копируем загруженное изображение в папку каталога
            if (!empty($files)) {
                $this->upload_images_gallery($record_id, $image_id, $files);
            }

            // Делаем редирект на список записей
            $target_url = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования изображения, прикрепленного к элементу
    function gallery_edit($arguments) {

        $output = "";

        // Читаем идентификатор записи и идентификатор изображения
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id)) . '">Фотогалерея элемента</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'gallery_edit', array("id" => $record_id, "eid" => $id)) . '">Редактирование изображения</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенной записи
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенная запись не найдена' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $record = $result->FetchRow();

        // Создаем форму редактирования записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'gallery_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $record_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[eid]', $id);

        // Печатаем название изображения
        $fob->add_html('sep', $this->cms->int_add_h1($record[ "title_" . $this->lang ]));

        // Показываем изображение, если оно было загружено
        if (!empty($record["img_sm"])) {
            $image = '<a href="' . $record['img'] . '?rnd=' . md5(time()) . '" target="_blank"><img src="' . $record['img_sm'] . '?rnd=' . md5(time()) . '" border="0" /></a><br>';
            $fob->add_html('img', $image, 'input');
        }

        $fob->add_file("img", 'Изображение');

        // Название и описание изображения
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'title_' . $lang, $record[ 'title_' . $this->lang ], 'Название', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_textarea(false, 'description_' . $lang, $record[ 'description_' . $this->lang ], 'Краткое описание', $lang, 57, 3, 'fob-textarea');
        }

        $fob->add_text(false, 'meta_title', $record['meta_title'], 'Meta: title', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_description', $record['meta_description'], 'Meta: description', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'meta_keywords', $record['meta_keywords'], 'Meta: keywords', '', 'fob-text', '', 'text', '', '');

        $fob->add_text(false, 'ord', $record['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)$record['enabled'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию о записи
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос на обновление
            $update = array(
                'ord'              => (int)$values['ord'],
                'enabled'          => (0 + !empty($values['enabled'])),
                'meta_title'       => "'" . $values['meta_title'] . "'",
                'meta_description' => "'" . $values['meta_description'] . "'",
                'meta_keywords'    => "'" . $values['meta_keywords'] . "'"
            );
            foreach ($this->langs as $lang) {
                $update[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $update[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_gallery'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Удаляем ранее загруженное в фотогалерею изображение
            if (isset($files["img"]) && $files["img"]["error"] == 0) {

                // Формируем список файлов для удаления
                $images = array(
                    "img"     => ROOT_HTML_PATH . substr($record["img"], 1),
                    "img_sm"  => ROOT_HTML_PATH . substr($record["img_sm"], 1),
                    "img_sm1" => ROOT_HTML_PATH . substr($record["img_sm1"], 1),
                    "img_sm2" => ROOT_HTML_PATH . substr($record["img_sm2"], 1)
                );

                // Удаляем загруженные в галерею изображения из папки каталога
                $this->delete_images_set($images);

                // Копируем загруженное изображение в папку каталога
                $this->upload_images_gallery($record_id, $record["id"], $files);
            }

            // Делаем редирект на список записей
            $target_url = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления изображения, прикрепленного к элементу
    function gallery_delete($arguments) {

        // Читаем идентификатор записи и идентификатор изображения
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об удаляемом элементе
        $record = $this->get_record_gallery($id);

        // Формируем список файлов для удаления
        $images = array(
            "img"     => ROOT_HTML_PATH . substr($record["img"], 1),
            "img_sm"  => ROOT_HTML_PATH . substr($record["img_sm"], 1),
            "img_sm1" => ROOT_HTML_PATH . substr($record["img_sm1"], 1),
            "img_sm2" => ROOT_HTML_PATH . substr($record["img_sm2"], 1)
        );

        // Удаляем загруженные в галерею изображения из папки каталога
        $this->delete_images_set($images);

        // Удаляем информацию об изображении
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список прикрепленных изображений
        $target_url = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id));
        header('Location: ' . $target_url);
    }

    // Функция включения и выключения элемента фотогалереи
    function gallery_switch_state($arguments) {

        // Читаем идентификатор записи и идентификатор изображения
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об элементе
        $record = $this->get_record($id);

        // Переключаем состояние элемента
        $sql = 'UPDATE ' . $this->cms->tables['catalog_gallery'] . ' SET enabled = abs(enabled - 1) WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список прикрепленных изображений
        $target_url = $this->cms->format_url($this->plugin['name'], 'gallery', array("id" => $record_id));
        header('Location: ' . $target_url);
    }

    //......................................................................... Прикрепленные файлы

    // Функция печати списка файлов, прикрепленных к элементу
    function files($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $record_id = (int)$arguments['id'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id)) . '">Файлы элемента</a>';
        $this->cms->intSetHierarchy();

        // Печатаем ссылку возврата к редактированию элемента
        $output .= '<p><a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '"><img src="/admin/img/cat_up.gif" class="ico">Вернуться к элементу</a></p>';

        // Читаем список файлов
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_files'] . ' WHERE record_id=' . $record_id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Выводим список файлов
        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				<td class="title-h" style="width: 10px;">#</td>
				<td class="title-h" style="width: 70%">Название</td>
				<td class="act-h" style="width: 15%">Файл</td>
				<td class="act-h" style="width: 7%">Приор.</td>
				<td class="act-h" style="width: 8%">Вкл./Выкл.</td>
				<td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $record_id;
                $arguments['eid'] = $item['id'];

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'files_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'files_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 70%"><a href="' . $this->cms->format_url($this->plugin['name'], 'files_edit', $arguments) . '">' . htmlspecialchars($item[ 'title_' . $this->lang ]) . '</a></td>
					<td class="act" style="width: 15%"><a href="' . $item["url"] . '" target="_blank">' . htmlspecialchars($item["filename"]) . '</a></td>
					<td class="act" style="width: 7%">' . $item["ord"] . '</td>
					<td class="act" style="width: 8%">' . $switch . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Прикрепленные файлы отсутствуют</p>';
        }

        // Печатаем кнопку добавления нового изображения
        $output .= '<input type="button" class="button" value="Добавить файл" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'files_add', array("id" => $record_id)) . "'" . '">';

        return $output;
    }

    // Функция добавления нового файла к элементу каталога
    function files_add($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $record_id = (int)$arguments['id'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id)) . '">Файлы элемента</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files_add', array("id" => $record_id)) . '">Добавление файла</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'files_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $record_id);

        // Печатаем название элемента каталога
        $fob->add_html('sep', $this->cms->int_add_h1("Новый файл для &quot;" . htmlspecialchars($item[ "title_" . $this->lang ]) . "&quot;"));

        // Кнопка выбора файла для загрузки
        $fob->add_file("file", 'Файл');

        // Название и описание файла
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'title_' . $lang, '', 'Название', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_textarea(false, 'description_' . $lang, '', 'Краткое описание', $lang, 57, 3, 'fob-textarea');
        }

        $fob->add_text(false, 'ord', 0, 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 1, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем запись в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос
            $insert_sql = array(
                'record_id' => $record_id,
                'ord'       => (int)$values['ord'],
                'created'   => "NOW()",
                'enabled'   => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $insert_sql[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_files'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Читаем идентификатор только что добавленного файла
            $file_id = $this->dbc->insert_id();

            // Копируем загруженный файл в папку каталога
            if (!empty($files)) {
                $this->upload_file($record_id, $file_id, $files);
            }

            // Делаем редирект на список записей
            $target_url = $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования файла, прикрепленного к элементу
    function files_edit($arguments) {

        $output = "";

        // Читаем идентификатор записи и идентификатор файла
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об элементе каталога
        $item = $this->get_record($record_id);

        // Читаем идентификатор исходной родительской категории
        $parent_id = $item["parent_id"];

        // Читаем путь к текущему элементу
        $path = $this->get_path($parent_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        if (!empty($path)) {
            foreach ($path as $path_id => $path_title) {
                $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array("id" => $path_id)) . '">' . htmlspecialchars($path_title) . '</a>';
            }
        }
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $record_id)) . '">' . htmlspecialchars($item[ "title_" . $this->lang ]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id)) . '">Файлы элемента</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'files_edit', array("id" => $record_id, "eid" => $id)) . '">Редактирование файла</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенной записи
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_files'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенная запись не найдена' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $record = $result->FetchRow();

        // Создаем форму редактирования записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'files_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $record_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[eid]', $id);

        // Печатаем название файла
        $fob->add_html('sep', $this->cms->int_add_h1($record[ "title_" . $this->lang ]));

        // Печатаем ссылку на файл
        if (!empty($record["url"])) {
            $file = '<a href="' . $record['url'] . '?rnd=' . md5(time()) . '" target="_blank">' . htmlspecialchars($record["filename"]) . '</a><br>';
            $fob->add_html('file', $file, 'input');
        }

        $fob->add_file("file", 'Файл');

        // Название и описание файла
        foreach ($this->langs as $lang) {
            $fob->add_text(false, 'title_' . $lang, $record[ 'title_' . $this->lang ], 'Название', $lang, 'fob-text', '', 'text', '', '');
        }
        foreach ($this->langs as $lang) {
            $fob->add_textarea(false, 'description_' . $lang, $record[ 'description_' . $this->lang ], 'Краткое описание', $lang, 57, 3, 'fob-textarea');
        }

        $fob->add_text(false, 'ord', $record['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)$record['enabled'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию о записи
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос на обновление
            $update = array(
                'ord'     => (int)$values['ord'],
                'enabled' => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $update[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
                $update[ "description_" . $lang ] = "'" . addslashes(trim($values[ 'description_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_files'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Удаляем ранее загруженный файл
            if (isset($files["file"]) && $files["file"]["error"] == 0) {

                // Формируем абсолютный путь к ранее загруженному файлу
                $file = ROOT_HTML_PATH . substr($record["url"], 1);

                // Проверяем, что файл существует
                if (!empty($file) && $file != ROOT_HTML_PATH && file_exists($file)) {

                    // Удаляем файл
                    $result = unlink($file);
                    if (!$result) {
                        $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                        return $output;
                    }
                }

                // Копируем загруженный файл в папку каталога
                $this->upload_file($record_id, $record["id"], $files);
            }

            // Делаем редирект на список записей
            $target_url = $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления файла, прикрепленного к элементу
    function files_delete($arguments) {

        // Читаем идентификатор записи и идентификатор файла
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об удаляемом элементе
        $record = $this->get_record_files($id);

        // Формируем абсолютный путь к файлу
        $file = ROOT_HTML_PATH . substr($record["url"], 1);

        // Проверяем, что файл существует
        if (!empty($file) && $file != ROOT_HTML_PATH && file_exists($file)) {

            // Удаляем файл
            $result = unlink($file);
            if (!$result) {
                $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');

                return $output;
            }
        }

        // Удаляем информацию о прикрепленном файле
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_files'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список прикрепленных файлов
        $target_url = $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id));
        header('Location: ' . $target_url);
    }

    // Функция включения и выключения прикрепленного файла
    function files_switch_state($arguments) {

        // Читаем идентификатор записи и идентификатор файла
        $record_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию об элементе
        $record = $this->get_record($id);

        // Переключаем состояние элемента
        $sql = 'UPDATE ' . $this->cms->tables['catalog_files'] . ' SET enabled = abs(enabled - 1) WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список прикрепленных файлов
        $target_url = $this->cms->format_url($this->plugin['name'], 'files', array("id" => $record_id));
        header('Location: ' . $target_url);
    }

    //................................................................................ Наборы полей

    // Функция печати списка доступных наборов полей
    function fieldsets() {

        $output = "";

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], '', array()) . '">Наборы полей</a>';
        $this->cms->intSetHierarchy();

        // Читаем доступные наборы полей
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets'] . ' ORDER BY title';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Выводим доступные наборы полей
        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				  <td class="title-h" style="width: 10px;">#</td>
				  <td class="title-h" style="width: 100%">Название</td>
				  <td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $item['id'];

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item['title']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 100%"><a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', $arguments) . '" title="id_text:' . htmlspecialchars($item["id_text"]) . '">' . $item['title'] . '</a></td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Наборы полей отсутствуют</p>';
        }

        // Печатаем кнопку добавления нового набора полей
        $output .= '<input type="button" class="button" value="Добавить набор полей" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'fieldsets_add', array()) . "'" . '">';

        return $output;

    }

    // Функция добавления нового набора полей
    function fieldsets_add() {

        $output = "";

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '">Наборы полей</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_add', array()) . '">Добавление набора полей</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления набора полей
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'fieldsets_add');
        $fob->add_html(false, '<div style="font-weight: bold; font-size: 12px; padding: 10px;">Внимание!!! Идентификатор должен быть уникальным!</div>');
        $fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(true, 'id_text', '', 'Идентификатор', '', 'fob-text', '', 'text', '', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем каталог в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Рассчитываем идентификатор набора
            $id_text = $this->calculate_ascii_string($values['id_text']);

            // Формируем SQL-запрос
            $insert_sql = array(
                'id_text' => "'" . $id_text . "'",
                'title'   => "'" . addslashes(trim($values['title'])) . "'"
            );

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_fieldsets'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Формируем и создаем в базе данных таблицу для хранения записей данного типа
            $table["name"] = PREFIX_TABLE . "_catalog_table_" . $id_text;
            $table["columns"]["id"] = "int(11) NOT NULL auto_increment";
            $table["columns"]["record_id"] = "int(11) NOT NULL default '0'";
            $table["primary_key"] = "id";
            $this->create_table($table);

            // Делаем редирект на список наборов полей
            $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets', array());
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования набора полей
    function fieldsets_edit($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $id = (int)$arguments['id'];

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '">Набор полей</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_edit', array($arguments)) . '">Редактирование набора полей</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенном наборе полей
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный набор полей не найден' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();

        // Создаем форму редактирования набора полей
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'fieldsets_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию о наборе полей
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Формируем SQL-запрос на обновление
            $update = array(
                'title' => "'" . addslashes(trim($values['title'])) . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_fieldsets'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Делаем редирект на список наборов полей
            $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets', array());
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления набора полей
    function fieldsets_delete($arguments) {

        // Читаем идентификатор записи
        $id = (int)$arguments['id'];

        // Читаем информацию о наборе полей
        $fieldset = $this->get_fieldset($id);

        // Формируем имя таблицы, хранящей набор полей
        $table_name = PREFIX_TABLE . "_catalog_table_" . $fieldset["id_text"];

        // Удаляем таблицу в которой хранятся элементы набора полей
        if ($table_name) {
            $sql = "DROP TABLE IF EXISTS " . $table_name;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
        }

        // Удаляем информацию об элементах набора полей
        $sql = "DELETE FROM " . $this->cms->tables['catalog_fieldsets_data'] . " WHERE fieldset = '" . $fieldset["id_text"] . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Удаляем описание набора полей
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_fieldsets'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Устанавливаем набор полей элементов каталога, использующих данный набор в "empty"
        $sql = "UPDATE " . $this->cms->tables['catalog'] . " SET fieldset='empty' WHERE fieldset = '" . $fieldset["id_text"] . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список наборов полей
        $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets', array());
        header('Location: ' . $target_url);

    }

    //........................................................................................ Поля

    // Функция печати списка полей
    function fieldsets_fields($arguments) {

        $output = "";

        // Читаем идентификатор набора полей
        $fieldset_id = (int)$arguments['id'];

        // Читаем информацию о наборе полей
        $fieldset = $this->get_fieldset($fieldset_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '">Наборы полей</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $select_id)) . '">' . htmlspecialchars($fieldset["title"]) . '</a>';
        $this->cms->intSetHierarchy();

        // Печатаем ссылку возврата к наборам полей
        $output .= '<p><a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '"><img src="/admin/img/cat_up.gif" class="ico">К наборам полей</a></p>';

        // Формируем имя таблицы, хранящей элементы, описываемые данным набором полей
        $table_name = PREFIX_TABLE . "_catalog_table_" . $select["id_text"];

        // Выполняем SQL-запрос к базе данных
        $sql = "SELECT * FROM " . $this->cms->tables["catalog_fieldsets_data"] . " WHERE fieldset='" . $fieldset["id_text"] . "' ORDER BY ord DESC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Выводим список полей в наборе
        if ($result->RecordCount() > 0) {

            // Начинаем формирование формы для групповой обработки элементов
            $output .= '<form name="update" method="post">';

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				  <td class="title-h" style="width: 10px;">#</td>
				  <td class="title-h" style="width: 40%">Название поля</td>
				  <td class="act-h" style="width: 10%">ID</td>
				  <td class="act-h" style="width: 10%">Приоритет</td>
				  <td class="act-h" style="width: 10%">Список</td>
				  <td class="act-h" style="width: 10%">Подробнее</td>
				  <td class="act-h" style="width: 20%">Тип поля</td>
				  <td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $fieldset_id;
                $arguments['eid'] = $item['id'];

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item['title']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_delete', $arguments) . '">удалить</a>',
                );

                // Формируем название элемента
                $title = htmlspecialchars($item['title']);
                if ($item['type'] == "select" || $item['type'] == "multiple" || $item['type'] == "multipleprice") {

                    // Подсвечиваем поле список для которого не определен список выбора
                    if (empty($item["options"])) {
                        $title = ' <span style="color:red">' . $title . '</span>';
                    }
                }

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 40%"><a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_edit', $arguments) . '">' . $title . '</a></td>
					<td class="act" style="width: 10%">' . htmlspecialchars($item["id_text"]) . '</td>
					<td class="act" style="width: 10%"><input type="text" name="items[' . $item['id'] . '][ord]" value="' . $item['ord'] . '" size="5"></td>
					<td class="act" style="width: 10%"><input type="checkbox" name="items[' . $item['id'] . '][show_list_flag]" value="1"' . ($item['show_list_flag'] ? ' checked' : '') . '></td>
					<td class="act" style="width: 10%"><input type="checkbox" name="items[' . $item['id'] . '][show_view_flag]" value="1"' . ($item['show_view_flag'] ? ' checked' : '') . '></td>
					<td class="act" style="width: 20%">' . $this->fields_types[ $item["type"] ] . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

            // Печатаем форму групповых операций над элементами
            $output .= '<table style="float:right">';
            $output .= '<tr>';
            $output .= '<td>';
            $output .= '<input type="button" class="button" value="Сохранить изменения" onClick="document.forms[\'update\'].action=\'' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id)) . '\';document.forms[\'update\'].submit();" style="align:right">';
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '</table>';

            // Заканчиваем формирование формы для групповых операций
            $output .= '</form>';

            // Обновляем параметры полей
            if (!empty($_POST["items"]) && is_array($_POST["items"])) {

                // Читаем список полей, которые нужно обновить
                $fields = $_POST["items"];

                // Обрабатываем каждое поле по очереди
                foreach ($fields as $field_id => $field) {

                    // Формируем SQL-запрос на обновление
                    $update = array(
                        'ord'            => (int)$field['ord'],
                        'show_list_flag' => (int)0 + !empty($field['show_list_flag']),
                        'show_view_flag' => (int)0 + !empty($field['show_view_flag'])
                    );

                    // Выполняем SQL-запрос
                    $update_sql = array();
                    foreach ($update as $key => $val) {
                        $update_sql[] = $key . ' = ' . $val;
                    }
                    $sql = 'UPDATE ' . $this->cms->tables['catalog_fieldsets_data'] . ' SET ' . join(', ', $update_sql) . ' WHERE id=' . $field_id;
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
                }

                // Делаем редирект на список полей
                $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets', array());
                header('Location: ' . $target_url);
            }

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список полей пуст</p>';
        }

        // Печатаем кнопку добавления нового поля
        $output .= '<input type="button" class="button" value="Добавить поле" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_add', array("id" => $fieldset_id)) . "'" . '">';

        return $output;
    }

    // Функция добавления нового поля
    function fieldsets_fields_add($arguments) {

        $output = "";

        // Читаем идентификатор набора полей
        $fieldset_id = (int)$arguments['id'];

        // Читаем информацию о наборе полей
        $fieldset = $this->get_fieldset($fieldset_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '">Наборы полей</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id)) . '">' . htmlspecialchars($fieldset["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_add', array("id" => $fieldset_id)) . '">Добавление поля</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления элемента списка
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_html(false, '<div style="font-weight: bold; font-size: 12px; padding: 10px;">Внимание!!! Идентификатор должен быть уникальным!</div>');
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'fieldsets_fields_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $fieldset_id);
        $fob->add_text(true, 'title', '', 'Название поля', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(true, 'id_text', '', 'Идентификатор', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', '0', 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_select(true, 'type', $this->fields_types, 'Тип поля', '', '', '');
        $fob->add_checkbox(false, 'show_list_flag', 1, 'Показывать в списке', '', '', 0, 'fob-check', '');
        $fob->add_checkbox(false, 'show_view_flag', 1, 'Показывать в подробной информации', '', '', 0, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем элемент в список
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Рассчитываем идентификатор поля
            $id_text = $this->calculate_ascii_string($values['id_text']);

            // Читаем список уже существующих полей в базе данных
            $fieldsets_fields = $this->get_fieldsets_fields($fieldset["id_text"]);

            // Проверяем идентификатор на уникальность и корректируем его в случае необходимости
            $counter = 1;
            $id_text_original = $id_text;
            while (isset($fieldsets_fields[ $id_text ])) {
                $id_text = $id_text_original . $counter;
                $counter++;
            }

            // Проверяем, что указан корректный тип поля
            if (!empty($values['type']) && isset($this->fields_types[ $values['type'] ])) {
                $type = $values['type'];
            } else {
                $type = "string";
            }

            // Формируем SQL-запрос
            $insert_sql = array(
                'fieldset'       => "'" . $fieldset["id_text"] . "'",
                'id_text'        => "'" . $id_text . "'",
                'title'          => "'" . addslashes(trim($values['title'])) . "'",
                'type'           => "'" . $type . "'",
                'ord'            => (int)$values['ord'],
                'show_list_flag' => (0 + !empty($values['show_list_flag'])),
                'show_view_flag' => (0 + !empty($values['show_view_flag']))
            );

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_fieldsets_data'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Изменяем структуру таблицу, хранящую записи каталога, в соответствии с обновленным набором полей
            $this->alter_table($fieldset["id_text"]);

            // Делаем редирект на текущий набор полей
            $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования поля
    function fieldsets_fields_edit($arguments) {

        $output = "";

        // Читаем идентификатор набора полей и идентификатор записи
        $fieldset_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о наборе полей
        $fieldset = $this->get_fieldset($fieldset_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets', array()) . '">Наборы полей</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id)) . '">' . htmlspecialchars($fieldset["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'fieldsets_fields_edit', array("id" => $fieldset_id)) . '">Редактирование поля</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенном поле
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets_data'] . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенное поле не найдено' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();

        // Формируем массив доступных списков выбора
        $selects = $this->get_selects();

        // Формируем список для выбора только текстовых типов полей
        $fields_types_text = $this->fields_types;
        foreach ($fields_types_text as $type_id => $type_value) {
            if ($type_id != "string" && $type_id != "text" && $type_id != "html") {
                unset($fields_types_text[ $type_id ]);
            }
        }

        // Формируем список для выбора только списковых типов полей
        $fields_types_list = $this->fields_types;
        foreach ($fields_types_list as $type_id => $type_value) {
            if ($type_id != "select" && $type_id != "multiple" && $type_id != "multipleprice") {
                unset($fields_types_list[ $type_id ]);
            }
        }

        // Читаем опции для поля изображения
        if ($item["type"] == "image") {

            $options = explode("|", $item["options"]);

            // Формируем текущие значения опций для поля изображения
            $image_options = array();
            $counter = 0;
            foreach ($this->options as $option_id => $option_value) {
                if (substr($option_id, 0, 4) == "img_") {
                    if (isset($options[ $counter ])) {
                        $image_options[ $option_id ] = (int)$options[ $counter ];
                    } else {
                        $image_options[ $option_id ] = 0;
                    }
                }
                $counter++;
            }
        }

        // Создаем форму редактирования поля
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'fieldsets_fields_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $fieldset_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[eid]', $id);

        // Печатаем название поля
        $fob->add_html('sep', $this->cms->int_add_h1($item["title"]));

        $fob->add_text(true, 'title', $item['title'], 'Название поля', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', $item['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');

        // Разрешаем изменение типа для текстовых полей
        if ($item['type'] == "string" || $item['type'] == "text" || $item['type'] == "html") {
            $fob->add_select(true, 'type', $fields_types_text, 'Тип поля', '', $item['type'], '');
        }

        // Разрешаем изменение типа для списков
        if ($item['type'] == "select" || $item['type'] == "multiple" || $item['type'] == "multipleprice") {
            $fob->add_select(true, 'type', $fields_types_list, 'Тип поля', '', $item['type'], '');
        }

        // Для поля списка показываем список привязки к конкретному списку (да, я знаю, что это звучит очень странно :)
        if ($item["type"] == "select" || $item["type"] == "multiple" || $item['type'] == "multipleprice") {
            $fob->add_select(true, 'options', $selects, 'Список выбора', '', $item['options'], '');
        }

        // Для изображений указываем дополнительные поля выбора размеров изображения для масштабирования
        if ($item["type"] == "image") {
            foreach ($image_options as $option_id => $option_value) {
                $fob->add_text(false, 'options[' . $option_id . ']', $option_value, $this->options_titles[ $option_id ], '', 'fob-text', '', 'text', '', '');
            }
        }

        $fob->add_checkbox(false, 'show_list_flag', 1, 'Показывать в списке', '', '', (bool)$item['show_list_flag'], 'fob-check', '');
        $fob->add_checkbox(false, 'show_view_flag', 1, 'Показывать в подробной информации', '', '', (bool)$item['show_view_flag'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию о поле
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Проверяем, что указан корректный тип поля
            if (!empty($values['type']) && isset($this->fields_types[ $values['type'] ])) {
                $type = $values['type'];
            } else {
                $type = $item['type'];
            }

            // Разрешаем изменение типа только для текстовых и списковых полей
            if (!isset($fields_types_text[ $type ]) && !isset($fields_types_list[ $type ])) {
                $type = $item['type'];
            }

            // Список опций по умолчанию
            $options = "";

            // Для списков формируем дополнительные параметры
            if ($type == "select" || $type == "multiple" || $type == "multipleprice") {
                $options = addslashes(trim($values['options']));
            }

            // Для изображений формируем дополнительные параметры
            if ($type == "image") {

                if (!empty($values["options"])) {

                    // Формируем список опций в виде строки "value1|value2|value3|..."
                    $options = array();
                    foreach ($values['options'] as $option_id => $option_value) {
                        $options[ $option_id ] = (int)$option_value;
                    }
                    $options = implode("|", $options);
                }
            }

            // Формируем SQL-запрос на обновление
            $update = array(
                'title'          => "'" . addslashes(trim($values['title'])) . "'",
                'type'           => "'" . $type . "'",
                'options'        => "'" . $options . "'",
                'ord'            => (int)$values['ord'],
                'show_list_flag' => (0 + !empty($values['show_list_flag'])),
                'show_view_flag' => (0 + !empty($values['show_view_flag']))
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_fieldsets_data'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Изменяем структуру таблицу, хранящую записи каталога, в соответствии с обновленным набором полей
            $this->alter_table($fieldset["id_text"]);

            // Делаем редирект на текущий набор полей
            $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления поля
    function fieldsets_fields_delete($arguments) {

        // Читаем идентификатор набора полей и идентификатор записи
        $fieldset_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о наборе полей
        $fieldset = $this->get_fieldset($fieldset_id);

        // Удаляем поле из базы данных
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_fieldsets_data'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Изменяем структуру таблицу, хранящую записи каталога, в соответствии с обновленным набором полей
        $this->alter_table($fieldset["id_text"]);

        // Делаем редирект на текущий набор полей
        $target_url = $this->cms->format_url($this->plugin['name'], 'fieldsets_fields', array("id" => $fieldset_id));
        header('Location: ' . $target_url);
    }

    //...................................................................................... Заказы

    // Функция печати списка заказов
    function orders($arguments) {

        $output = "";

        $page = 0;

        // Читаем номер текущей страницы
        if (!empty($arguments['page'])) {
            $page = (int)$arguments['page'];
        }

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders', array()) . '">Заказы</a>';
        $this->cms->intSetHierarchy();

        // Рассчитываем общее количество элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog_orders'];
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $record = $result->FetchRow();
        $num_records = $record["num_records"];

        // Выводим доступные списки заказов
        if ($num_records > 0) {

            // Рассчитываем количество записей на страничку
            $records_per_page = (int)$this->options["records_per_page_admin"];

            // Корректируем номер страницы
            if ($page < 1) {
                $page = 1;
            }
            if ($page > ceil($num_records / $records_per_page)) {
                $page = ceil($num_records / $records_per_page);
            }

            // Формируем строку навигации по записям
            $navigation_string = $this->get_navigation_string($page, $num_records, $records_per_page, 'orders', array());

            // Читаем список заказов
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_orders'] . ' ORDER BY created DESC LIMIT ' . ($page - 1) * $records_per_page . ', ' . $records_per_page;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

            // Печатаем заголовок таблицы
            $output .= '
				<style> .ttl_basket { font-size: 120%; color: #2F466D;} </style>
				<style> .ttl_basket2 { font-size: 120%; color: #1629da;} </style>
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				  <td class="title-h" style="width: 10px;">#</td>
				  <td class="title-h">ФИО / Дата заказа</td>
				  <td class="title-h">Адрес</td>
				  <td class="title-h">Контакты</td>
				  <td class="title-h">Доставка / Тип оплаты</td>
				  <td class="title-h">Заказ</td>
				  <td class="title-h">Сумма / Кол-во<br>Акционный код</td>
				  <!--<td class="act-h">Состояние</td>-->
				  <td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $item['id'];
                $arguments_print['id'] = $item['id'];
                $arguments_print['act'] = "print";

                // Формируем список доступных действий
                $actions = array(
                    'view'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders_view', $arguments) . '">посмотреть</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить заказ покупателя &quot;' . htmlspecialchars($item['name']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'orders_delete', $arguments) . '">удалить</a>',
                );

                // Формируем переключатель текущего состояния заказа
                if ($item['active']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders_switch_state', $arguments) . '" class="on">Активен</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders_switch_state', $arguments) . '" class="off">Обработан</a>';
                }

                $currencyInfo = $this->getCurrency($item["currency"]);

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $item["id"] . '<br><a href="' . $this->cms->format_url($this->plugin['name'], 'orders_view', $arguments_print) . '" onclick="pop(this, \'pr\', 800, 600); return false;"><img src="/admin/img/printer.gif"></a></td>
					<td class="title"><a href="' . $this->cms->format_url($this->plugin['name'], 'orders_view', $arguments) . '">' . $item['last_name'] . ' ' . $item['name'] . ' ' . $item['patr'] . '</a><br><br>' . Date('d.m.Y H:i', strtotime($item["created"])) . '</td>
					<td class="title">
						Страна: ' . $item["country"] . '<br>
						Город: ' . $item["city"] . '<br>
						' . $item["address"] . '<br>
					</td>
					<td class="title">
						Тел: ' . $item['phone'] . '<br>
						E-mail: <a href="mailto:' . htmlspecialchars($item['email']) . '">' . htmlspecialchars($item['email']) . '</a><br>
					</td>
					<td class="title">';
                $output .= $this->delivery[ $item["delivery"] ]["title"] . "<br>";
                if ($item["type_pay"] == 1) {//webmoney
                    $output .= "webmoney: " . ($item["webmoney"] == "u" ? "Кошелек U" : "Кошелек Z");
                } elseif ($item["type_pay"] == 2) {//Наличными по факту получения
                    $output .= "Наличными";
                } else {//Безналичный рассчет
                    $output .= "Безналичный рассчет: " . ($item["type_face"] == "1" ? "юридическое лицо" : "физическое лицо");
                    if (!empty($item["link_file_order"])) {
                        $output .= "<br>Счет: <a href='" . $item["link_file_order"] . "'>Счет</a>";
                    }
                }
//				$output .= '<br>Сумма доставки: '.$item["price_delivery"].' '.$currencyInfo["title"];
                $output .= '</td>
					<td class="title" nowrap>' . $item["items"] . '</td>
					<td class="title" nowrap>' . $item["price"] . ' ' . $currencyInfo["title"] . ' / ' . $item["total_quantity"] . '<br>' . $item["action_code"] . '</td>
					<td class="act">' . $switch . '</td>
					<td class="act">' . $actions['view'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список заказов пуст</p>';
        }

        return $output;
    }

    // Функция просмотре заказа
    function orders_view($arguments) {

        $output = "";
        $page = 0;

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        if (!empty($arguments['page'])) {
            $page = (int)$arguments['page'];
        }
        $act = '';
        if (!empty($arguments['act'])) {
            $act = (int)$arguments['act'];
        }

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders', array()) . '">Заказы</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'orders_view', array($arguments)) . '">Просмотр заказа</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенном заказе
        $order = $this->get_order($id);

        if (!empty($order)) {

            $currencyInfo = $this->getCurrency($order["currency"]);

            // Печатаем общую информацию о заказе
            $output .= "<h1>Заказ №" . $order["id"] . "</h1>\n";
            $output .= "<table>";
            $output .= "<tr><td style=\"padding-right:15px;\">Дата заказа:</td><td>" . Date('d.m.Y H:i', strtotime($order["created"])) . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">ФИО:</td><td>" . $order['last_name'] . ' ' . $order['name'] . ' ' . $order['patr'] . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Страна:</td><td>" . $order["country"] . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Город:</td><td>" . $order["city"] . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Адрес:</td><td>" . $order["address"] . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Телефон:</td><td>" . $order["phone"] . "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">E-mail:</td><td><a href=\"mailto:" . $order["email"] . "\">" . $order["email"] . "</a></td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Доставка:</td><td>" . $this->delivery[ $order["delivery"] ]["title"] . "</td></tr>";
//			$output .= "<tr><td style=\"padding-right:15px;\">Сумма доставки:</td><td>".$order["price_delivery"]." ".$currencyInfo["title"]."</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Тип оплаты:</td><td>";
            if ($order["type_pay"] == 1) {//webmoney
                $output .= "webmoney: " . ($order["webmoney"] == "u" ? "Кошелек U" : "Кошелек Z");
            } elseif ($order["type_pay"] == 2) {//Наличными по факту получения
                $output .= "Наличными";
            } else {//Безналичный рассчет
                $output .= "Безналичный рассчет: " . ($order["type_face"] == "1" ? "юридическое лицо" : "физическое лицо");
            }

            $output .= "</td></tr>";
            $output .= "<tr><td style=\"padding-right:15px;\">Акционный код:</td><td>" . $order["action_code"] . "</td></tr>";
            $output .= "</table><br>";

            // Печатаем комментарии к заказу
            if (!empty($order["comments"])) {
                $output .= "<p><strong>Комментарии к заказу</strong></p>\n";
                $output .= "<p>" . nl2br(htmlspecialchars($order["comments"])) . "</p><br>\n";
            }
            $output .= "<b>Заказ</b>: <br>" . $order["items_str"] . "<br>";
            $output .= "<b>Сумма заказа</b>: " . $order["price"] . " " . $currencyInfo["title"] . "";

            // Печатаем список товаров
            if (!empty($order["items"]) && 0) {

                // Печатаем заголовок таблицы
                $output .= '
					<div class="admin-table2"><div class="width-fix"><table>
					<tr>
					  <td class="title-h" style="width: 10px;">#</td>
					  <td class="title-h" style="width: 70%">Название товара</td>
					  <td class="title-h" style="width: 10%">Количество</td>
					  <td class="title-h" style="width: 10%">Цена</td>
					  <td class="act-h" style="width: 10%">Стоимость</td>
					</tr>
				';

                // Печатаем строки таблицы
                $counter = 1;
                $total_quantity = 0;
                $total_cost = 0;
                reset($order["items"]);
                while (list($item_id, $item) = each($order["items"])) {

                    // Рассчитываем стоимость с учетом количества товаров
                    $cost = $item["price"] * $item["quantity"];

                    // Формируем название товара
                    $title = htmlspecialchars($item['title']);
//					if (!empty($item["code"])) {
//						$title .= ", код товара: (".htmlspecialchars($item["code"]).")";
//					}

                    // Печатаем строку таблицы
                    $output .= '
						<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
						<td class="title" style="width: 10px;">' . $counter . '</td>
						<td class="title" style="width: 70%">' . $title . '</td>
						<td class="title" style="width: 10%">' . $item['quantity'] . '</td>
						<td class="title" style="width: 10%">' . number_format($item['price'], 2, ".", "") . ' грн.</td>
						<td class="act" style="width: 10%">' . number_format($cost, 2, ".", "") . ' грн.</td>
						</tr>
					';
                    $total_cost += $cost;
                    $total_quantity += $item["quantity"];
                    $counter++;
                }

                // Печатаем итоговую сумму заказа
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="act-h" style="width: 10px;">&nbsp;</td>
					<td class="act-h" style="width: 70%">Итого:</td>
					<td class="act-h" style="width: 10%">' . $total_quantity . '</td>
					<td class="act-h" style="width: 10%">-</td>
					<td class="act-h" style="width: 10%">' . number_format($total_cost, 2, ".", "") . ' грн.</td>
					</tr>
				';
                $output .= '</table></div></div>';

            } else {
//				$output .= "<p><strong>Список товаров</strong></p>\n";
//				$output .= "<p>Список заказанных товаров пуст</p><br>\n";
            }
        } else {
            $output .= "<p>Запрошенный заказ не найден</p>";
        }

        // Печатаем ссылку возврата к списку заказов
        if ($act != "print") {
            $output .= '<br><br><p><a href="' . $this->cms->format_url($this->plugin['name'], 'orders_view', array("id" => $id, "act" => "print")) . '" onclick="pop(this, \'pr\', 800, 600); return false;"><img src="/admin/img/printer.gif"> печать</a></p>';
            $output .= '<br><br><p><a href="' . $this->cms->format_url($this->plugin['name'], 'orders', array("page" => $page)) . '"><img src="/admin/img/cat_up.gif" class="ico">Вернуться к списку заказов</a></p>';
        }

        if ($act == "print") {
            echo $this->cms->_print($output);
            die();
        }

        return $output;
    }

    // Функция удаления заказа
    function orders_delete($arguments) {

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Удаляем описание заказа
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_orders'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Удаляем товары заказа
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_orders_items'] . ' WHERE order_id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список заказов
        $target_url = $this->cms->format_url($this->plugin['name'], 'orders', array("page" => $page));
        header('Location: ' . $target_url);

    }

    // Функция переключения состояния заказа
    function orders_switch_state($arguments) {

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Читаем информацию об элементе
        $record = $this->get_order($id);

        // Переключаем состояние заказа
        $sql = 'UPDATE ' . $this->cms->tables['catalog_orders'] . ' SET active = abs(active - 1) WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список заказов
        $target_url = $this->cms->format_url($this->plugin['name'], 'orders', array("page" => $page));
        header('Location: ' . $target_url);
    }

    // Функция печати списка заказов
    function preorders($arguments) {

        $output = "";

        $page = 0;

        // Читаем номер текущей страницы
        if (!empty($arguments['page'])) {
            $page = (int)$arguments['page'];
        }

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'preorders', array()) . '">Предзаказы</a>';
        $this->cms->intSetHierarchy();

        // Рассчитываем общее количество элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog_preorders'];
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        $record = $result->FetchRow();
        $num_records = $record["num_records"];

        // Выводим доступные списки заказов
        if ($num_records > 0) {

            // Рассчитываем количество записей на страничку
            $records_per_page = (int)$this->options["records_per_page_admin"];

            // Корректируем номер страницы
            if ($page < 1) {
                $page = 1;
            }
            if ($page > ceil($num_records / $records_per_page)) {
                $page = ceil($num_records / $records_per_page);
            }

            // Формируем строку навигации по записям
            $navigation_string = $this->get_navigation_string($page, $num_records, $records_per_page, 'orders', array());

            // Читаем список заказов
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_preorders'] . ' ORDER BY created DESC LIMIT ' . ($page - 1) * $records_per_page . ', ' . $records_per_page;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

            // Печатаем заголовок таблицы
            $output .= '
				<style> .ttl_basket { font-size: 120%; color: #2F466D;} </style>
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				  <td class="title-h" style="width: 10px;">#</td>
				  <td class="title-h">Название</td>
				  <td class="title-h">ФИО / Дата заказа</td>
				  <td class="title-h">Контакты</td>
				  <td class="title-h">Заказ</td>
				  <td class="act-h last">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $item['id'];

                $item_c = $this->get_record($item['record_id']);

                // Формируем список доступных действий
                $actions = array(
                    'delete' => '<a onclick="return confirm(\'Удалить предзаказ покупателя &quot;' . htmlspecialchars($item['name']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'preorders_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $item["id"] . '</td>
					<td class="title" nowrap>' . $item_c["title_ru"] . '</td>
					<td class="title">' . $item['last_name'] . ' ' . $item['name'] . ' ' . $item['patr'] . '<br><br>' . Date('d.m.Y H:i', strtotime($item["created"])) . '</td>
					<td class="title">
						Страна: ' . $item["country"] . '<br>
						Город: ' . $item["city"] . '<br>
					</td>
					<td class="title">
						Тел: ' . $item['phone'] . '<br>
						E-mail: <a href="mailto:' . htmlspecialchars($item['email']) . '">' . htmlspecialchars($item['email']) . '</a><br>
					</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список заказов пуст</p>';
        }

        return $output;
    }

    function preorders_delete($arguments) {

        // Читаем идентификатор записи и номер текущей страницы
        $id = (int)$arguments['id'];
        $page = (int)$arguments['page'];

        // Удаляем описание заказа
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_preorders'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список заказов
        $target_url = $this->cms->format_url($this->plugin['name'], 'preorders', array("page" => $page));
        header('Location: ' . $target_url);

    }

    //............................................................................... Списки выбора

    // Функция печати списков выбора
    function selects($arguments) {

        $output = "";

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->intSetHierarchy();

        // Читаем доступные списки выбора
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' ORDER BY title';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Выводим доступные списки выбора
        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				  <td class="title-h" style="width: 10px;">#</td>
				  <td class="title-h" style="width: 100%">Название</td>
				  <td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $item['id'];

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item['title']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'selects_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 100%"><a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', $arguments) . '" title="id_text:' . htmlspecialchars($item["id_text"]) . '">' . $item['title'] . '</a></td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Списки выборов отсутствуют</p>';
        }

        // Печатаем кнопку добавления нового списка выбора
        $output .= '<input type="button" class="button" value="Добавить список выбора" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'selects_add', array()) . "'" . '">';

        return $output;
    }

    // Функция добавления нового списка выбора
    function selects_add($arguments) {

        $output = "";

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_add', array()) . '">Добавление списка выбора</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления списка выбора
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_html(false, '<div style="font-weight: bold; font-size: 12px; padding: 10px;">Внимание!!! Идентификатор должен быть уникальным!</div>');
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_add');
        //$fob->add_html(false, '<div style="font-weight: bold; font-size: 12px; padding: 10px;">Внимание!!! Идентификатор должен быть уникальным!</div>');
        $fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(true, 'id_text', '', 'Идентификатор', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'menu_flag', 1, 'Показать в меню', '', '', 0, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем каталог в базу данных
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Рассчитываем идентификатор списка
            $id_text = $this->calculate_ascii_string($values['id_text']);

            // Формируем SQL-запрос
            $insert_sql = array(
                'id_text'   => "'" . $id_text . "'",
                'title'     => "'" . addslashes(trim($values['title'])) . "'",
                'menu_flag' => (0 + !empty($values['menu_flag'])),
            );

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_selects'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Формируем и создаем в базе данных таблицу для хранения элементов списка
            $table["name"] = PREFIX_TABLE . "_catalog_select_" . $id_text;
            $table["columns"]["id"] = "int(11) NOT NULL auto_increment";
            foreach ($this->langs as $lang) {
                $table["columns"][ "title_" . $lang ] = "varchar(255) NOT NULL default ''";
            }
            if (isset($this->options_img_enabled[ $id_text ])) {
                $table["columns"]["img"] = "varchar(255) NOT NULL default ''";
            }
            $table["columns"]["def"] = "varchar(255) NOT NULL default ''";
            $table["columns"]["ord"] = "int(4) NOT NULL default '0'";
            $table["columns"]["enabled"] = "int(1) NOT NULL default '0'";
            $table["primary_key"] = "id";
            $this->create_table($table);

            // Делаем редирект на списки выбора
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects', array());
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования списка выбора
    function selects_edit($arguments) {

        $output = "";

        // Читаем идентификатор записи
        $id = (int)$arguments['id'];

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_edit', array($arguments)) . '">Редактирование списка выбора</a>';
        $this->cms->intSetHierarchy();

        // Читаем информацию о запрошенном списке выбора
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный список выбора не найден' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();

        // Создаем форму редактирования города
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_text(true, 'title', $item['title'], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'menu_flag', 1, 'Показать в меню', '', '', (bool)$item['menu_flag'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию о списке выбора
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Формируем SQL-запрос на обновление
            $update = array(
                'title'     => "'" . addslashes(trim($values['title'])) . "'",
                'menu_flag' => (0 + !empty($values['menu_flag']))
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_selects'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Делаем редирект на списки выборов
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects', array());
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления списка выбора
    function selects_delete($arguments) {

        // Читаем идентификатор записи
        $id = (int)$arguments['id'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($id);

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Удаляем таблицу в которой хранятся элементы списка
        if ($table_name) {
            $sql = "DROP TABLE IF EXISTS " . $table_name;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
        }

        // Удаляем описание списка
        $sql = 'DELETE FROM ' . $this->cms->tables['catalog_selects'] . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список каталогов
        $target_url = $this->cms->format_url($this->plugin['name'], 'selects', array());
        header('Location: ' . $target_url);

    }

    //..................................................................... Элементы списков выбора

    // Функция печати доступных значений в выбранном списке
    function selects_options($arguments) {

        $output = "";

        // Читаем идентификатор списка выбора
        $select_id = (int)$arguments['id'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id)) . '">' . htmlspecialchars($select["title"]) . '</a>';
        $this->cms->intSetHierarchy();

        // Печатаем ссылку возврата к спискам выбора
        $output .= '<p><a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '"><img src="/admin/img/cat_up.gif" class="ico">К спискам выбора</a></p>';

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Читаем элементы списка выбора из базы данных
        $sql = 'SELECT * FROM ' . $table_name . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Печатаем элементы
        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				<td class="title-h" style="width: 10px;">#</td>
				<td class="title-h" style="width: 100%">Название элемента</td>
				<td class="act-h" nowrap>Знач. по умолч.</td>
				<td class="act-h">Приор.</td>
				<td class="act-h">Вкл./Выкл.</td>
				<td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $select_id;
                $arguments['eid'] = $item['id'];

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                // Формируем список доступных действий
                $actions = array(
                    'edit'   => '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item[ "title_" . $this->lang ]) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 10px;">' . $counter . '</td>
					<td class="title" style="width: 100%"><a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_edit', $arguments) . '">' . $item[ "title_" . $this->lang ] . '</a></td>
					<td class="act">' . $item["def"] . '</td>
					<td class="act">' . $item["ord"] . '</td>
					<td class="act">' . $switch . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;
            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список элементов пуст</p>';
        }

        // Печатаем кнопку добавления нового элемента списка
        $output .= '<input type="button" class="button" value="Добавить элемент списка" onClick="location.href=' . "'" . $this->cms->format_url($this->plugin['name'], 'selects_options_add', array("id" => $select_id)) . "'" . '">';

        return $output;
    }

    // Функция добавления нового значения в список выбора
    function selects_options_add($arguments) {

        $output = "";

        // Читаем идентификатор списка выбора
        $select_id = (int)$arguments['id'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id)) . '">' . htmlspecialchars($select["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_add', array("id" => $select_id)) . '">Добавление элемента</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления элемента списка
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_options_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $select_id);
        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, '', 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
        $fob->add_text(false, 'def', '', 'Значение по умолчанию', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', '0', 'Приоритет', '', 'fob-text', '', 'text', '', '');

        // Добавляем форму загрузки изображения
        if (isset($this->options_img_enabled[ $select["id_text"] ])) {
            $fob->add_file("img", 'Изображение');
        }

        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 1, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем элемент в список
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос
            $insert_sql = array(
                'ord'     => (int)$values['ord'],
                'def'     => addslashes(trim($values['def'])),
                'enabled' => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
            }

            // Формируем имя таблицы, хранящей элементы списка
            $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $table_name . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Читаем идентификатор только что добавленной записи
            $record_id = $this->dbc->insert_id();

            // Копируем изображение в папку каталога
            if (!empty($files)) {
                $this->upload_images_selects($select["id_text"], $record_id, $files);
            }

            // Делаем редирект на элементы списка выборов
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования значения в списке выбора
    function selects_options_edit($arguments) {

        $output = "";

        // Читаем идентификатор списка выбора и идентификатор записи
        $select_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id)) . '">' . htmlspecialchars($select["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_edit', array("id" => $select_id)) . '">Редактирование элемента</a>';
        $this->cms->intSetHierarchy();

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Читаем информацию о запрошенном элементе списка выбора
        $sql = 'SELECT * FROM ' . $table_name . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент списка выбора не найден' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();

        // Создаем форму добавления элемента списка
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_options_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $select_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[eid]', $id);
        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, $item[ 'title_' . $lang ], 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
        $fob->add_text(false, 'def', $item['def'], 'Значение по умолчанию', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', $item['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');

        // Добавляем форму загрузки изображения
        if (isset($this->options_img_enabled[ $select["id_text"] ])) {

            if (!empty($item["img"])) {
                $image = '<img src="' . $item['img'] . '?rnd=' . md5(time()) . '" border="0" />';
                $fob->add_html('img', $image, 'input');
            }
            $fob->add_file("img", 'Изображение');
        }

        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)$item['enabled'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию об элементе списка выбора
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Читаем массив загруженных файлов
            $files = $fob->get_submited_files();

            // Формируем SQL-запрос на обновление
            $update = array(
                'ord'     => (int)$values['ord'],
                'def'     => addslashes(trim($values['def'])),
                'enabled' => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $update[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $table_name . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Загружаем изображения из формы
            if (isset($this->options_img_enabled[ $select["id_text"] ]) && $files["img"]["error"] == 0) {

                // Удаляем выбранные пользователем и загружаемые заново изображения
                $this->delete_images_selects($select["id_text"], $id);
                $this->upload_images_selects($select["id_text"], $id, $files);
            }

            // Делаем редирект на элементы списка выборов
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления значения из списка выбора
    function selects_options_delete($arguments) {

        // Читаем идентификатор списка выбора и идентификатор записи
        $select_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Удаляем изображение элемента списка
        if (isset($this->options_img_enabled[ $select["id_text"] ])) {
            $this->delete_images_selects($select["id_text"], $id);
        }

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Удаляем описание списка
        $sql = 'DELETE FROM ' . $table_name . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список каталогов
        $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
        header('Location: ' . $target_url);

    }

    // Функция включения и выключения значения в списке выбора
    function selects_options_switch_state($arguments) {

        // Читаем идентификатор списка выбора и идентификатор записи
        $select_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Переключаем состояние элемента
        $sql = 'UPDATE ' . $table_name . ' SET enabled = abs(enabled - 1) WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список значений
        $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
        header('Location: ' . $target_url);
    }

    //.............................................. Элементы списков выбора (работа с изображениями)

    // Функция добавления нового значения в список выбора
    function selects_img_options_add($arguments) {

        $output = "";

        // Читаем идентификатор списка выбора
        $select_id = (int)$arguments['id'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id)) . '">' . htmlspecialchars($select["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_add', array("id" => $select_id)) . '">Добавление элемента</a>';
        $this->cms->intSetHierarchy();

        // Создаем форму добавления элемента списка
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_options_add');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $select_id);
        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, '', 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
        $fob->add_text(false, 'ord', '0', 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', 1, 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Добавляем элемент в список
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Формируем SQL-запрос
            $insert_sql = array(
                'ord'     => (int)$values['ord'],
                'enabled' => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $insert_sql[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
            }

            // Формируем имя таблицы, хранящей элементы списка
            $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

            // Выполняем SQL-запрос
            $sql = 'INSERT INTO ' . $table_name . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Делаем редирект на элементы списка выборов
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция редактирования значения в списке выбора
    function selects_img_options_edit($arguments) {

        $output = "";

        // Читаем идентификатор списка выбора и идентификатор записи
        $select_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects', array()) . '">Списки выбора</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id)) . '">' . htmlspecialchars($select["title"]) . '</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'selects_options_edit', array("id" => $select_id)) . '">Редактирование элемента</a>';
        $this->cms->intSetHierarchy();

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Читаем информацию о запрошенном элементе списка выбора
        $sql = 'SELECT * FROM ' . $table_name . ' WHERE id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент списка выбора не найден' . $sql, $this->plugin['name'], 'error');

            return false;
        }
        $item = $result->FetchRow();

        // Создаем форму добавления элемента списка
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'selects_options_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $select_id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[eid]', $id);
        foreach ($this->langs as $lang) {
            $fob->add_text(true, 'title_' . $lang, $item[ 'title_' . $lang ], 'Название элемента', $lang, 'fob-text', '', 'text', '', '');
        }
        $fob->add_text(false, 'ord', $item['ord'], 'Приоритет', '', 'fob-text', '', 'text', '', '');
        $fob->add_checkbox(false, 'enabled', 1, 'Включено', '', '', (bool)$item['enabled'], 'fob-check', '');
        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию об элементе списка выбора
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();

            // Формируем SQL-запрос на обновление
            $update = array(
                'ord'     => (int)$values['ord'],
                'enabled' => (0 + !empty($values['enabled']))
            );
            foreach ($this->langs as $lang) {
                $update[ "title_" . $lang ] = "'" . addslashes(trim($values[ 'title_' . $lang ])) . "'";
            }

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $table_name . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }

            // Делаем редирект на элементы списка выборов
            $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
            header('Location: ' . $target_url);
        }

        return $output;
    }

    // Функция удаления значения из списка выбора
    function selects_img_options_delete($arguments) {

        // Читаем идентификатор списка выбора и идентификатор записи
        $select_id = (int)$arguments['id'];
        $id = (int)$arguments['eid'];

        // Читаем информацию о списке выбора
        $select = $this->get_select($select_id);

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select["id_text"];

        // Удаляем описание списка
        $sql = 'DELETE FROM ' . $table_name . ' WHERE id = ' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

            return false;
        }

        // Делаем редирект на список каталогов
        $target_url = $this->cms->format_url($this->plugin['name'], 'selects_options', array("id" => $select_id));
        header('Location: ' . $target_url);

    }

    //................................................................................... Настройки

    // Функция редактирования настроек модуля
    function options() {

        $output = "";

        // Печатаем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'options', array()) . '">Настройки</a>';
        $this->cms->intSetHierarchy();

        // Выводим список опций
        if (!empty($this->options)) {

            // Создаем форму редактирования опций
            $fob = $this->init_fob('fob', $this->cms->admin_url);
            $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
            $fob->add_hidden('$' . $this->cms->request_vars['command'], 'options');
            foreach ($this->options as $name => $value) {

                // Если опция начинается с префикса "date_", то формируем поле выбора даты
                if (substr($name, 0, strpos($name, "_")) == "date") {
                    $fob->add_date("options[" . $name . "]", $value, $this->options_titles[ $name ], '', 'fob-date');
                } else {
                    $fob->add_text(true, "options[" . $name . "]", $value, $this->options_titles[ $name ], '', 'fob-text', '', 'text', '', '');
                }
            }
            $fob->add_button('submit', 'submit', 'Сохранить', '');
            $output .= $fob->display('return');

            // Обновляем опции модуля
            if ($fob->is_submited() && $fob->is_valid_submit()) {

                // Читаем данные из формы
                $values = $fob->get_submited_values();

                // Выполняем SQL-запросы на обновление для каждой опции
                foreach ($values["options"] as $name => $value) {

                    // Обновляем значении опции в базе данных
                    $sql = "UPDATE " . $this->cms->tables["catalog_options"] . " SET value='" . $value . "' WHERE name='" . $name . "'";
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
                }
            }

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список опций пуст</p>';
        }

        return $output;
    }

    //................................................. Функции работы с загруженными изображениями

    // Функция загрузки изображения для элемента каталога
    function upload_images($record_id, $fieldset, $files) {

        // Загружаем основное изображение элемента каталога
        if (!empty($files["img"]) && $files["img"]["error"] == 0) {

            // Находим расширение файла
            $pathinfo = pathinfo($files["img"]["name"]);
            $extension = strtolower($pathinfo["extension"]);

            // Формируем имя файла для основного изображения
            $filename = "item_" . $record_id . "." . $extension;

            // Копируем загруженный файл в папку изображений каталога
            $target = ROOT_HTML_PATH . $this->path_upload . $filename;
            if (!move_uploaded_file($files["img"]['tmp_name'], $target)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при закачивании' . $files["img"]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');

                return $output;
            }

            // Читаем глобальные опции каталога для масштабирования изображений
            $options = $this->options;

            // Формируем первую миниатюру из изображения
            $filename_sm = "item_" . $record_id . "_sm." . $extension;
            $target_sm = ROOT_HTML_PATH . $this->path_upload . $filename_sm;
            if (!copy($target, $target_sm)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img"]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');

                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm, $options["img_sm_width"], $options["img_sm_height"], 1);
            @chmod($target_sm, 0777);

            // Формируем вторую миниатюру из изображения
            $filename_sm1 = "item_" . $record_id . "_sm1." . $extension;
            $target_sm1 = ROOT_HTML_PATH . $this->path_upload . $filename_sm1;
            if (!copy($target, $target_sm1)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img"]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');

                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm1, $options["img_sm1_width"], $options["img_sm1_height"], 1);
            @chmod($target_sm1, 0777);

            // Формируем третью миниатюру из изображения
            $filename_sm2 = "item_" . $record_id . "_sm2." . $extension;
            $target_sm2 = ROOT_HTML_PATH . $this->path_upload . $filename_sm2;
            if (!copy($target, $target_sm2)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img"]['tmp_name'] . ' -> ' . $target_sm2, $this->plugin['name'], 'error');

                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm2, $options["img_sm2_width"], $options["img_sm2_height"], 1);
            @chmod($target_sm2, 0777);

            // Масштабируем исходное изображение
            $this->cms->files->resizeImageMode($target, $options["img_width"], $options["img_height"], 0);
            @chmod($target, 0777);
            $this->insertToImageLogo($target);

            // Формируем SQL-запрос для сохранения информации об изображении в базе данных
            $update = array(
                "img"     => "'/" . $this->path_upload . $filename . "'",
                "img_sm"  => "'/" . $this->path_upload . $filename_sm . "'",
                "img_sm1" => "'/" . $this->path_upload . $filename_sm1 . "'",
                "img_sm2" => "'/" . $this->path_upload . $filename_sm2 . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $record_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                return false;
            }
        }

        // Загружаем дополнительные изображения элемента каталога
        if (!empty($files["img_additional"])) {

            // По очереди обрабатываем каждое дополнительное изображение
            if (!empty($fieldset) && $fieldset != "empty") {

                // Формируем название таблицы, которая хранит дополнительную информацию
                $table = PREFIX_TABLE . "_catalog_table_" . $fieldset;

                // Читаем информацию о наборе полей, которые содержат изображения
                $fieldset = $this->get_fieldsets_fields($fieldset);
                foreach ($fieldset as $field_id => $field) {
                    if ($field["type"] != "image") {
                        unset($fieldset[ $field_id ]);
                    }
                }

                $update = array();
                foreach ($fieldset as $field_id => $field) {

                    if ($files["img_additional"]["error"][ $field_id ] == 0) {

                        // Находим расширение файла
                        $pathinfo = pathinfo($files["img_additional"]["name"][ $field_id ]);
                        $extension = strtolower($pathinfo["extension"]);

                        // Формируем имя файла для основного изображения
                        $filename = "item_" . $record_id . "_" . $field_id . "." . $extension;

                        // Копируем загруженный файл в папку изображений каталога
                        $target = ROOT_HTML_PATH . $this->path_upload . $filename;
                        if (!move_uploaded_file($files["img_additional"]['tmp_name'][ $field_id ], $target)) {
                            $this->cms_ai->message_box->add('top', 'Ошибка при закачивании' . $files["img_additional"]['tmp_name'][ $field_id ] . ' -> ' . $target, $this->plugin['name'], 'error');

                            return $output;
                        }

                        // Формируем текущие значения опций масштабирования для поля изображения
                        // Если локальное значение равно нулю, то он заменяется идентичным глобальным значением
                        $options = explode("|", $field["options"]);
                        $image_options = array();
                        $counter = 0;
                        foreach ($this->options as $option_id => $option_value) {
                            if (substr($option_id, 0, 4) == "img_") {
                                if (isset($options[ $counter ]) && (int)$options[ $counter ] != 0) {
                                    $image_options[ $option_id ] = (int)$options[ $counter ];
                                } else {
                                    $image_options[ $option_id ] = $option_value;
                                }
                            }
                            $counter++;
                        }
                        $options = $image_options;

                        // Формируем первую миниатюру из изображения
                        $filename_sm = "item_" . $record_id . "_" . $field_id . "_sm." . $extension;
                        $target_sm = ROOT_HTML_PATH . $this->path_upload . $filename_sm;
                        if (!copy($target, $target_sm)) {
                            $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img_additional"]['tmp_name'][ $field_id ] . ' -> ' . $target, $this->plugin['name'], 'error');

                            return $output;
                        }
                        $this->cms->files->resizeImageMode($target_sm, $options["img_sm_width"], $options["img_sm_height"], 1);
                        @chmod($target_sm, 0777);

                        // Формируем вторую миниатюру из изображения
                        $filename_sm1 = "item_" . $record_id . "_" . $field_id . "_sm1." . $extension;
                        $target_sm1 = ROOT_HTML_PATH . $this->path_upload . $filename_sm1;
                        if (!copy($target, $target_sm1)) {
                            $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img_additional"]['tmp_name'][ $field_id ] . ' -> ' . $target, $this->plugin['name'], 'error');

                            return $output;
                        }
                        $this->cms->files->resizeImageMode($target_sm1, $options["img_sm1_width"], $options["img_sm1_height"], 1);
                        @chmod($target_sm1, 0777);

                        // Формируем третью миниатюру из изображения
                        $filename_sm2 = "item_" . $record_id . "_" . $field_id . "_sm2." . $extension;
                        $target_sm2 = ROOT_HTML_PATH . $this->path_upload . $filename_sm2;
                        if (!copy($target, $target_sm2)) {
                            $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $files["img_additional"]['tmp_name'][ $field_id ] . ' -> ' . $target_sm2, $this->plugin['name'], 'error');

                            return $output;
                        }
                        $this->cms->files->resizeImageMode($target_sm2, $options["img_sm2_width"], $options["img_sm2_height"], 1);
                        @chmod($target_sm2, 0777);

                        // Масштабируем исходное изображение
                        $this->cms->files->resizeImageMode($target, $options["img_width"], $options["img_height"], 0);
                        @chmod($target, 0777);

                        // Формируем SQL-запрос для сохранения информации об изображении в базе данных
                        $update[ $field_id ] = "'/" . $this->path_upload . $filename . "'";
                        $update[ $field_id . "_sm" ] = "'/" . $this->path_upload . $filename_sm . "'";
                        $update[ $field_id . "_sm1" ] = "'/" . $this->path_upload . $filename_sm1 . "'";
                        $update[ $field_id . "_sm2" ] = "'/" . $this->path_upload . $filename_sm2 . "'";
                    }

                    // Выполняем SQL-запрос
                    $update_sql = array();
                    foreach ($update as $key => $val) {
                        $update_sql[] = $key . ' = ' . $val;
                    }
                    $sql = 'UPDATE ' . $table . ' SET ' . join(', ', $update_sql) . ' WHERE record_id = ' . $record_id;
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                        return false;
                    }
                }
            }
        }
    }

    // Функция удаления изображений элемента каталога
    function delete_images($record_id, $delete_images = "") {

        // Читаем информацию о записи
        $record = $this->get_record($record_id);

        // Формируем список файлов для удаления
        $images = array(
            "img"     => ROOT_HTML_PATH . substr($record["img"], 1),
            "img_sm"  => ROOT_HTML_PATH . substr($record["img_sm"], 1),
            "img_sm1" => ROOT_HTML_PATH . substr($record["img_sm1"], 1),
            "img_sm2" => ROOT_HTML_PATH . substr($record["img_sm2"], 1)
        );

        // Проверяем необходимость удаления основного изображения
        if (empty($delete_images) || $delete_images["img"] == 1) {

            // Удаляем набор изображений основного элемента каталога
            $this->delete_images_set($images);

            // Если явно указаны изображения для удаления, 
            // то обновляем информацию об изображении в базе данных
            if (!empty($delete_images) && !empty($record["img"])) {

                // Формируем SQL-запрос на обновление
                $update = array(
                    'img'     => "''",
                    'img_sm'  => "''",
                    'img_sm1' => "''",
                    'img_sm2' => "''"
                );

                // Выполняем SQL-запрос
                $update_sql = array();
                foreach ($update as $key => $val) {
                    $update_sql[] = $key . ' = ' . $val;
                }
                $sql = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET ' . join(', ', $update_sql) . ' WHERE id=' . $record_id;
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');

                    return false;
                }
            }
        }

        // Если для элемента определены дополнительные поля
        if ($record["fieldset"] != "empty") {

            // Читаем информацию о наборе полей, которые содержат изображения
            $fieldset = $this->get_fieldsets_fields($record["fieldset"]);
            foreach ($fieldset as $field_id => $field) {
                if ($field["type"] != "image") {
                    unset($fieldset[ $field_id ]);
                }
            }

            // Читаем дополнительную информацию о записи
            $additional = $this->get_record_additional($record_id, $record["fieldset"]);

            // По очереди удаляем все дополнительные изображения
            $update = array();
            foreach ($fieldset as $field_id => $field) {

                // Формируем список файлов для удаления
                $images = array(
                    "img"     => ROOT_HTML_PATH . substr($additional[ $field_id ], 1),
                    "img_sm" => ROOT_HTML_PATH . substr($additional[$field_id . "_sm"], 1),
                    "img_sm1" => ROOT_HTML_PATH . substr($additional[$field_id . "_sm1"], 1),
                    "img_sm2" => ROOT_HTML_PATH . substr($additional[$field_id . "_sm2"], 1)
                );

                // Проверяем необходимость удаления дополнительных изображений
                if (empty($delete_images) || $delete_images["additional"][$field_id] == 1) {

                    // Удаляем набор дополнительных изображений
                    $this->delete_images_set($images);

                    // Если явно указаны изображения для удаления, 
                    // то обновляем информацию об изображении в базе данных
                    if (!empty($delete_images) && !empty($additional[$field_id])) {

                        // Формируем SQL-запрос на обновление
                        $update[$field_id] = "''";
                        $update[$field_id . "_sm"] = "''";
                        $update[$field_id . "_sm1"] = "''";
                        $update[$field_id . "_sm2"] = "''";
                    }
                }
            }

            // Если были изменены некоторые изображения
            if (!empty($update)) {

                // Формируем название таблицы, которая хранит дополнительную информацию
                $table = PREFIX_TABLE . "_catalog_table_" . $record["fieldset"];

                // Формируем SQL-запрос на обновление
                $update_sql = array();
                foreach ($update as $key => $val) {
                    $update_sql[] = $key . ' = ' . $val;
                }

                // Выполняем SQL-запрос
                $sql = 'UPDATE ' . $table . ' SET ' . join(', ', $update_sql) . ' WHERE record_id=' . $record_id;
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                    return false;
                }
            }
        }
    }

    // Функция удаления набора изображений
    function delete_images_set($images)
    {

        // По очереди удаляем все изображения в наборе
        foreach ($images as $image_id => $image) {

            // Проверяем, что изображение существует
            if (!empty($image) && $image != ROOT_HTML_PATH && file_exists($image)) {

                // Удаляем изображение
                $result = unlink($image);
                if (!$result) {
                    $this->cms->int_add_message('top', 'Возникла ошибка при удалении файла.', $this->plugin['name'], 'error');
                    return $output;
                }
            }
        }
    }

    //..................................... Функции работы с прикрепленными изображениями и файлами

    // Функция загрузки прикрепленного изображения в галерею
    function upload_images_gallery($record_id, $image_id, $files, $var_name = "img")
    {

        if (!empty($files[$var_name]) && $files[$var_name]["error"] == 0) {

            // Находим расширение файла
            $pathinfo = pathinfo($files[$var_name]["name"]);
            $extension = strtolower($pathinfo["extension"]);

            // Формируем имя файла для основного изображения
            $filename = "item_" . $record_id . "_" . $image_id . "." . $extension;

            // Копируем загруженный файл в папку прикрепленных изображений каталога
            $target = ROOT_HTML_PATH . $this->path_upload . "gallery/" . $filename;
            if (!move_uploaded_file($files[$var_name]['tmp_name'], $target)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при закачивании' . $files[$var_name]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');
                return $output;
            }

            // Читаем глобальные опции каталога для масштабирования изображений
            $options = $this->options;

            // Формируем первую миниатюру из изображения
            $filename_sm = "item_" . $record_id . "_" . $image_id . "_sm." . $extension;
            $target_sm = ROOT_HTML_PATH . $this->path_upload . "gallery/" . $filename_sm;
            if (!copy($target, $target_sm)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $target . ' -> ' . $target_sm, $this->plugin['name'], 'error');
                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm, $options["img_sm_width"], $options["img_sm_height"], 1);
            @chmod($target_sm, 0777);

            // Формируем вторую миниатюру из изображения
            $filename_sm1 = "item_" . $record_id . "_" . $image_id . "_sm1." . $extension;
            $target_sm1 = ROOT_HTML_PATH . $this->path_upload . "gallery/" . $filename_sm1;
            if (!copy($target, $target_sm1)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $target . ' -> ' . $target_sm1, $this->plugin['name'], 'error');
                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm1, $options["img_sm1_width"], $options["img_sm1_height"], 1);
            @chmod($target_sm1, 0777);

            // Формируем вторую миниатюру из изображения
            $filename_sm2 = "item_" . $record_id . "_" . $image_id . "_sm2." . $extension;
            $target_sm2 = ROOT_HTML_PATH . $this->path_upload . "gallery/" . $filename_sm2;
            if (!copy($target, $target_sm2)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при создании миниатюры' . $target . ' -> ' . $target_sm2, $this->plugin['name'], 'error');
                return $output;
            }
            $this->cms->files->resizeImageMode($target_sm2, $options["img_sm2_width"], $options["img_sm2_height"], 1);
            @chmod($target_sm2, 0777);

            // Масштабируем исходное изображение
            $this->cms->files->resizeImageMode($target, $options["img_width"], $options["img_height"], 0);
            @chmod($target, 0777);

            $this->insertToImageLogo($target);

            // Формируем SQL-запрос для сохранения информации об изображении в базе данных
            $update = array(
                "img" => "'/" . $this->path_upload . "gallery/" . $filename . "'",
                "img_sm" => "'/" . $this->path_upload . "gallery/" . $filename_sm . "'",
                "img_sm1" => "'/" . $this->path_upload . "gallery/" . $filename_sm1 . "'",
                "img_sm2" => "'/" . $this->path_upload . "gallery/" . $filename_sm2 . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_gallery'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $image_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
        } else {
            $sql = 'DELETE FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE id = ' . $image_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
        }
    }

    // Функция загрузки прикрепленного файла в каталог
    function upload_file($record_id, $file_id, $files)
    {

        if (!empty($files["file"]) && $files["file"]["error"] == 0) {

            // Находим расширение файла
            $pathinfo = pathinfo($files["file"]["name"]);
            $extension = strtolower($pathinfo["extension"]);

            // Формируем имя файла
            $filename = "item_" . $record_id . "_" . $file_id . "." . $extension;

            // Копируем загруженный файл в папку прикрепленных файлов
            $target = ROOT_HTML_PATH . $this->path_upload . "files/" . $filename;
            if (!move_uploaded_file($files["file"]['tmp_name'], $target)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при закачивании' . $files["file"]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');
                return $output;
            }

            // Формируем SQL-запрос для сохранения информации о загруженном файле
            $update = array(
                "url" => "'/" . $this->path_upload . "files/" . $filename . "'",
                "filename" => "'" . addslashes($files["file"]["name"]) . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $this->cms->tables['catalog_files'] . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $file_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
        }
    }

    //.................................................. Функции работы с изображениями для списков

    // Функция загрузки изображения для списка
    function upload_images_selects($select, $select_id, $files)
    {

        if (!empty($files["img"]) && $files["img"]["error"] == 0) {

            // Находим расширение файла
            $pathinfo = pathinfo($files["img"]["name"]);
            $extension = strtolower($pathinfo["extension"]);

            // Формируем имя файла изображения
            $filename = $select . "_" . $select_id . "." . $extension;

            // Копируем загруженный файл в папку изображений списков каталога
            $target = ROOT_HTML_PATH . $this->path_upload . "selects/" . $filename;
            if (!move_uploaded_file($files["img"]['tmp_name'], $target)) {
                $this->cms_ai->message_box->add('top', 'Ошибка при закачивании' . $files["img"]['tmp_name'] . ' -> ' . $target, $this->plugin['name'], 'error');
                return $output;
            }

            // Читаем опции каталога для масштабирования изображений списка
            $options = $this->options_img_selects;

            // Масштабируем исходное изображение
            $this->cms->files->resizeImageMode($target, $options["img_width"], $options["img_height"], 0);
            @chmod($target, 0777);

            // Формируем имя таблицы, хранящей элементы списка
            $table_name = PREFIX_TABLE . "_catalog_select_" . $select;

            // Формируем SQL-запрос для сохранения информации об изображении в базе данных
            $update = array(
                "img" => "'/" . $this->path_upload . "selects/" . $filename . "'"
            );

            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }
            $sql = 'UPDATE ' . $table_name . ' SET ' . join(', ', $update_sql) . ' WHERE id = ' . $select_id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
        }
    }

    // Функция удаления изображения для списка
    function delete_images_selects($select, $select_id)
    {

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select;

        // Читаем информацию о записи
        $record = $this->get_select_option($select, $select_id);

        // Формируем список файлов для удаления
        $images = array(
            "img" => ROOT_HTML_PATH . substr($record["img"], 1)
        );

        // Удаляем изображение для списка
        if (!empty($images)) {
            $this->delete_images_set($images);
        }
    }

    //.................................................... Функции работы со структурой базы данных

    // Функция создания таблицы по ее описанию
    function create_table($table)
    {

        // Создаем таблицу только в случае, если она не  была создана раньше
        $sql = "CREATE TABLE IF NOT EXISTS " . $table["name"] . " (";

        // Формируем поля таблицы
        foreach ($table["columns"] as $column => $type) {
            $sql .= "`" . $column . "` " . $type . ", ";
        }

        // Формируем первичный ключ
        $sql .= "PRIMARY KEY (`" . $table["primary_key"] . "`))";

        // Указываем кодировку таблицы по умолчанию
        $sql .= " DEFAULT CHARACTER SET cp1251";

        // Выполняем SQL-запрос
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
    }

    // Функция изменения структуры таблицы согласно обновленному набору полей
    function alter_table($fieldset)
    {

        // Обновляем структуру таблицы, только если набор полей не является пустым
        if ($fieldset != "empty") {

            // Формируем имя таблицы, хранящей элементы, описываемые данным набором полей
            $table_fieldset = PREFIX_TABLE . "_catalog_table_" . $fieldset;

            // Читаем информацию о текущей структуре таблицы
            $sql = "SHOW COLUMNS FROM " . $table_fieldset;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
            if ($result->RecordCount() > 0) {

                // Формируем массив с названиями и типами существующих полей
                $table_fields = array();
                while ($record = $result->FetchRow()) {
                    $table_fields[$record["Field"]] = $record["Type"];
                }
            }

            // Удаляем из списка полей для сравнения обязательные поля
            unset($table_fields["id"]);
            unset($table_fields["record_id"]);

            // Читаем информацию о дополнительных полях
            $sql = "SELECT * FROM " . $this->cms->tables["catalog_fieldsets_data"] . " WHERE fieldset='" . $fieldset . "' ORDER BY ord DESC";
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
            if ($result->RecordCount() > 0) {

                $alter_sql = array();

                // По очереди проверяем каждое из полей на предмет изменения
                while ($record = $result->FetchRow()) {

                    // Обрабатываем поля с поддержкой нескольких языков
                    if ($record["type"] == "string" || $record["type"] == "text" || $record["type"] == "html") {

                        foreach ($this->langs as $lang) {

                            // Создаем новое поле для каждого поддерживаемого языка
                            if (!isset($table_fields[$record["id_text"] . "_" . $this->lang])) {

                                $alter_sql[] = "ADD " . $record["id_text"] . "_" . $lang . " " . $this->fields_types_mysql[$record["type"]];

                                // Изменяем тип поля
                            } else {

                                // Находим текущий формат поля в существуюещей таблице
                                $current_field_type = "";
                                if (strstr($table_fields[$record["id_text"] . "_" . $this->lang], "varchar")) {
                                    $current_field_type = "varchar";
                                } elseif (strstr($table_fields[$record["id_text"] . "_" . $this->lang], "text")) {
                                    $current_field_type = "text";
                                }

                                // Изменяем тип поля со строчного на текстовое
                                if ($current_field_type == "varchar" && ($record["type"] == "text" || $record["type"] == "html")) {
                                    $alter_sql[] = "CHANGE " . $record["id_text"] . "_" . $lang . " " . $record["id_text"] . "_" . $lang . " " . $this->fields_types_mysql[$record["type"]];
                                }

                                // Изменяем тип поля с текстового на строчное
                                if ($current_field_type == "text" && ($record["type"] == "string")) {
                                    $alter_sql[] = "CHANGE " . $record["id_text"] . "_" . $lang . " " . $record["id_text"] . "_" . $lang . " " . $this->fields_types_mysql[$record["type"]];
                                }

                                // Удаляем из таблицы существующих полей обработанное поле
                                unset($table_fields[$record["id_text"] . "_" . $this->lang]);
                            }
                        }

                        // Обрабатываем изображения
                    } elseif ($record["type"] == "image") {

                        if (!isset($table_fields[$record["id_text"]])) {

                            // Создаем три поля - для самого изображения и двух его миниатюр
                            $alter_sql[] = "ADD " . $record["id_text"] . " " . $this->fields_types_mysql[$record["type"]];
                            $alter_sql[] = "ADD " . $record["id_text"] . "_sm " . $this->fields_types_mysql[$record["type"]];
                            $alter_sql[] = "ADD " . $record["id_text"] . "_sm1 " . $this->fields_types_mysql[$record["type"]];
                            $alter_sql[] = "ADD " . $record["id_text"] . "_sm2 " . $this->fields_types_mysql[$record["type"]];

                        } else {

                            // Удаляем из таблицы существующих полей обработанное поле
                            unset($table_fields[$record["id_text"]]);
                            unset($table_fields[$record["id_text"] . "_sm"]);
                            unset($table_fields[$record["id_text"] . "_sm1"]);
                            unset($table_fields[$record["id_text"] . "_sm2"]);
                        }

                        // Обрабатываем обычные поля
                    } else {

                        if (!isset($table_fields[$record["id_text"]])) {

                            // Создаем обычное поле
                            $alter_sql[] = "ADD " . $record["id_text"] . " " . $this->fields_types_mysql[$record["type"]];

                        } else {

                            // Удаляем из таблицы существующих полей обработанное поле
                            unset($table_fields[$record["id_text"]]);
                        }
                    }
                }

                // Проверяем, требуется ли удалить из таблицы некоторые поля
                if (!empty($table_fields)) {

                    // Удаляем из таблицы все отсутствующие в описании поля
                    foreach ($table_fields as $field_id => $field_value) {
                        $alter_sql[] = "DROP " . $field_id;
                    }
                }

                // Если требуется изменение таблицы
                if (!empty($alter_sql)) {

                    // Формируем и выполняем SQL-запрос для изменения структуры таблицы
                    $alter_sql = implode(", ", $alter_sql);
                    $sql = "ALTER TABLE " . $table_fieldset . " " . $alter_sql;

                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                        return false;
                    }
                }
            }
        }
    }

    //......................................................................... Рекурсивные функции

    // Рекурсивная функция чтения дерева для запрошенного элемента
    function get_tree($id = 0, $type = "folder", $tree = array(), $counter = 0)
    {

        // Увеличиваем счетчик вложенности
        $counter++;

        // Формируем запрос к базе данных
        if ($type == "folder") {
            $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE parent_id=" . $id . " AND type='folder' ORDER BY ord DESC";
        } else {
            $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE parent_id=" . $id . " ORDER BY ord DESC";
        }

        // Читаем информацию о запрашиваемом элементе
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {

            while ($record = $result->FetchRow()) {

                // Формируем описание элемента
                $tree[$record["id"]] = str_repeat("&nbsp;&nbsp;&nbsp;", $counter) . $record["title_" . $this->lang];

                // Рекурсивно вызываем функцию для нахождения всех дочерних элементов
                $tree = $this->get_tree($record["id"], $type, $tree, $counter);
            }
        }

        // Уменьшаем счетчик вложенности
        $counter--;

        return $tree;
    }

    // Рекурсивная функция чтения пути к текущему элементу
    function get_path($id, $path = array())
    {

        // Читаем информацию о запрашиваемом элементе
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE id=' . $id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {

            // Читаем информацию об элементе
            $record = $result->FetchRow();

            if ($record["parent_id"] > 0) {

                // Рекурсивно вызываем функцию для нахождения всех родителей
                $path = $this->get_path($record["parent_id"], $path);
            }

            // Добавляем элемент в путь
            $path[$record["id"]] = $record["title_" . $this->lang];

        }

        return $path;
    }

    // Рекурсивная функция чтения количества разделов и элементов с учетом вложенных элементов
    function get_num_elements($id)
    {

        // Читаем количество общее количество вложенных элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog'] . ' WHERE parent_id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $record = $result->FetchRow();
        $num_elements["total"] = $record["num_records"];

        // Читаем общее количество вложенных разделов
        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id=" . $id . " AND type='folder'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $num_elements["folders"] = $result->RecordCount();

        // Рассчитываем количество обычных элементов
        $num_elements["items"] = $num_elements["total"] - $num_elements["folders"];

        if ($num_elements["folders"] > 0) {

            // Подсчитываем количество разделов и элементов во вложенных элементах
            while ($record = $result->FetchRow()) {

                // Рекурсивно вызываем функцию для подсчета количества вложенных элементов
                $child_elements = $this->get_num_elements($record["id"]);

                // Обновляем количество вложенных элементов с учетом дочерних
                $num_elements["total"] += $child_elements["total"];
                $num_elements["folders"] += $child_elements["folders"];
                $num_elements["items"] += $child_elements["items"];

            }
        }

        return $num_elements;
    }

    //....................................................................... Общие функции плагина

    // Функция чтения информации об элементе
    function get_record($record_id)
    {

        $record = "";

        // Читаем информацию об элементе
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE id=' . $record_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию об элементе
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения дополнительной информации об элементе
    function get_record_additional($record_id, $fieldset)
    {

        $additional = "";

        // Формируем имя таблицы, хранящей элементы, описываемые данным набором полей
        $table_name = PREFIX_TABLE . "_catalog_table_" . $fieldset;

        // Читаем информацию об элементе
        $sql = 'SELECT * FROM ' . $table_name . ' WHERE record_id=' . $record_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент не найден ' . $sql, $this->plugin['name'], 'error');
            $sql = 'INSERT INTO ' . $table_name . ' SET record_id=' . $record_id;
            $result = $this->dbc->Execute($sql);
            return array();
        }

        // Читаем дополнительную информацию об элементе
        $additional = $result->FetchRow();

        return $additional;
    }

    // Функция чтения информации о прикрепленной к элементу фотографии
    function get_record_gallery($image_id)
    {

        $record = "";

        // Читаем информацию об элементе
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE id=' . $image_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию об элементе
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения информации о прикрепленном к элементу файле
    function get_record_files($file_id)
    {

        $record = "";

        // Читаем информацию об элементе
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_files'] . ' WHERE id=' . $file_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемент не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию об элементе
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения информации о списке выбора
    function get_select($select_id)
    {

        $record = "";

        // Читаем информацию о списке выбора
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' WHERE id=' . $select_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный список выбора не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию о списке выбора
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения доступных списков выбора
    function get_selects()
    {

        $selects = array();

        // Читаем доступные списки выбора
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' ORDER BY title';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $selects[$record["id_text"]] = $record["title"];
            }
        }

        return $selects;
    }

    // Функция чтения опций списка выбора по его текстовому идентификатору
    function get_select_options($select, $withDef = 0)
    {

        $select_options = array();

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select;

        // Читаем доступные списки выбора
        $sql = 'SELECT * FROM ' . $table_name . ' WHERE enabled=1 ORDER BY ord DESC, title_' . $this->lang;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                if ($withDef == 0) {
                    $select_options[$record["id"]] = $record["title_" . $this->lang];
                } else {
                    $select_options[$record["id"]] = array("title" => $record["title_" . $this->lang], "def" => $record["def"]);
                }
            }
        }

        return $select_options;
    }

    // Функция чтения информации об элементе списка выбора
    function get_select_option($select, $select_id)
    {

        $record = "";

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select;

        // Читаем информацию о списке выбора
        $sql = 'SELECT * FROM ' . $table_name . ' WHERE id=' . $select_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный элемента списка выбора не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию об элементе списка выбора
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения информации о наборе полей
    function get_fieldset($fieldset_id)
    {

        $record = "";

        // Читаем информацию о наборе полей
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets'] . ' WHERE id=' . $fieldset_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный набор полей не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию о наборе полей
        $record = $result->FetchRow();

        return $record;
    }

    // Функция чтения доступных наборов полей
    function get_fieldsets()
    {

        $fieldsets = array();

        // Читаем доступные наборы полей
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets'] . ' ORDER BY title';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $fieldsets[$record["id_text"]] = $record["title"];
            }
        }

        return $fieldsets;
    }

    // Функция чтения названия полей в конкретном наборе полей
    function get_fieldsets_fields($fieldset)
    {

        $fieldsets_fields = array();

        // Читаем доступные наборы полей
        $sql = "SELECT * FROM " . $this->cms->tables['catalog_fieldsets_data'] . " WHERE fieldset='" . $fieldset . "' ORDER BY ord DESC";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $fieldsets_fields[$record["id_text"]] = $record;
            }
        }

        return $fieldsets_fields;
    }

    // Функция чтения информации о заказе
    function get_order($order_id)
    {

        $order = "";

        // Читаем информацию о заказе
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_orders'] . ' WHERE id=' . $order_id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() == 0) {
            $this->cms->int_set_message('top', 'Запрошенный заказ не найден' . $sql, $this->plugin['name'], 'error');
            return false;
        }

        // Читаем информацию о товарах в заказе
        $order = $result->FetchRow();

        if (!empty($order)) {

            $order["items_str"] = $order["items"];

            // Читаем список товаров в заказе
            $items = "";
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_orders_items'] . ' WHERE order_id=' . $order_id . ' ORDER BY title';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }
            if ($result->RecordCount() > 0) {
                while ($record = $result->FetchRow()) {
                    unset($record["order_id"]);
                    $items[] = $record;
                }
            }

            // Добавляем список товаров в описание заказа
            $order["items"] = $items;
        }

        return $order;
    }

    //............................................................................... Функции алиасов (начало)
    // проверка на уникальность алиаса
    function check_exist_alias($parent_id, $alias, $record_id = 0)
    {
        $ret = true;
        $sql = "SELECT COUNT(*) as counts FROM " . $this->cms->tables["catalog"] . " WHERE parent_id='" . $parent_id . "' AND alias='" . addslashes(trim($alias)) . "'";
        if (!empty($record_id)) {
            $sql .= " AND id<>'" . $record_id . "'";
        }
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return $ret;
        }
        $item = $result->FetchRow();
        if ($item["counts"] == 0) {
            $ret = false;
        }
        return $ret;
    }

    // проверка на валидность алиаса
    function is_valid_alias($alias)
    {
        if (preg_match('/^[a-zA-Z0-9_\-]{1,32}$/', $alias)) {
            return true;
        } else {
            return false;
        }
    }

    // возврат полного пути к элементу
    function get_relative_url($parent_id, $alias)
    {
        $ret = array(
            "relative_url" => "",
            "full_relative_url" => "",
        );

        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE id='" . $parent_id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return $ret;
        }
        if ($result->RecordCount() > 0) {
            $item = $result->FetchRow();
            $ret["relative_url"] = $item["relative_url"] . $alias . "/";
            $ret["full_relative_url"] = $item["full_relative_url"] . $alias . "/";
        } else {
            $ret["relative_url"] = "/";
            $ret["full_relative_url"] = "/" . $alias . "/";
        }

        return $ret;
    }

    // обновление полных путей для всех детей элемента
    function update_child_relative($item, $relative)
    {
        $sql = "SELECT id, relative_url, full_relative_url FROM " . $this->cms->tables["catalog"] . " WHERE full_relative_url LIKE '" . addslashes($item["full_relative_url"]) . "%' AND id<>'" . $item["id"] . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
//			echo "<pre>"; print_r($relative); echo "</pre>";
//			echo "<pre>"; print_r($item); echo "</pre>";
            while ($row = $result->FetchRow()) {
                $new_relative_url = substr($relative["relative_url"], 0, -1) . substr($row["relative_url"], strlen($item["relative_url"]) - 1);
                $new_full_relative_url = substr($relative["full_relative_url"], 0, -1) . substr($row["full_relative_url"], strlen($item["full_relative_url"]) - 1);
//				echo "В строке ".$row["relative_url"]." заменяю ".$item["relative_url"]." на ".$relative["relative_url"]." получил ".$new_relative_url."<br>";
//				echo "В строке ".$row["full_relative_url"]." заменяю ".$item["full_relative_url"]." на ".$relative["full_relative_url"]." получил ".$new_full_relative_url."<br>";
                $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET relative_url='" . $new_relative_url . "', full_relative_url='" . $new_full_relative_url . "' WHERE id='" . $row["id"] . "'";
                $resultUpdate = $this->dbc->Execute($sql);
                if (!$resultUpdate) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                    return false;
                }
//				echo "<pre>"; print_r($row); echo "</pre>";
//				echo $sql."<hr>";
            }
        }
//		die();
    }
    //............................................................................... Функции алиасов (конец)

    //................................................................................. Валюты

    function currency($arguments)
    {

        $output = "";

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'currency', array()) . '">Валюта</a>';
        $this->cms->intSetHierarchy();

        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_currency'] . ' ORDER BY ord DESC, title ASC';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }

        if ($result->RecordCount() > 0) {

            // Печатаем заголовок таблицы
            $output .= '
				<div class="admin-table2"><div class="width-fix"><table>
				<tr>
				<td class="title-h">Название фотографии</td>
				<td class="act-h">Курс</td>
				<td class="act-h">Приор.</td>
				<td class="act-h last" colspan="2">Операции</td>
				</tr>
			';

            while ($item = $result->FetchRow()) {
                $arguments['id'] = $item['currency_id'];

                $actions = array(
                    'edit' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'currency_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item['title']) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'currency_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title"><a href="' . $this->cms->format_url($this->plugin['name'], 'currency_edit', $arguments) . '">' . htmlspecialchars($item['title']) . '</a></td>
					<td class="act">' . $item["ratio"] . '</td>
					<td class="act">' . $item["ord"] . '</td>
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Валюты отсутствуют</p>';
        }

        // Создаем форму добавления записи
        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'currency');

        $fob->add_text(true, 'title', '', 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ratio', '', 'Курс', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', 0, 'Приоритет', '', 'fob-text', '', 'text', '', '');

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();

            $insert_sql = array(
                'title' => "'" . addslashes(trim($values['title'])) . "'",
                'ratio' => "'" . (float)str_replace(",", ".", $values['ratio']) . "'",
                'ord' => (int)$values['ord'],
            );
            $sql = 'INSERT INTO ' . $this->cms->tables['catalog_currency'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }

            $target_url = $this->cms->format_url($this->plugin['name'], 'currency', array());
            header('Location: ' . $target_url);
        }
        return $output;
    }

    function getCurrency($id)
    {
        $ret = array("title" => "");
        $sql = "SELECT * FROM " . $this->cms->tables['catalog_currency'] . " WHERE currency_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            $ret = $result->FetchRow();
        }
        return $ret;
    }

    function currency_edit($arguments)
    {
        $output = "";
        $id = (int)$arguments['id'];
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'currency', array()) . '">Валюта</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', array("id" => $id)) . '">Редактирование элемента</a>';
        $this->cms->intSetHierarchy();

        $sql = "SELECT * FROM " . $this->cms->tables['catalog_currency'] . " WHERE currency_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $item = $result->FetchRow();


        $fob = $this->init_fob('fob', $this->cms->admin_url);
        $fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'currency_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);

        $fob->add_text(true, 'title', $item["title"], 'Название', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ratio', $item["ratio"], 'Курс', '', 'fob-text', '', 'text', '', '');
        $fob->add_text(false, 'ord', $item["ord"], 'Приоритет', '', 'fob-text', '', 'text', '', '');

        $fob->add_button('submit', 'save', 'Сохранить', '');
        $output .= $fob->display('return');

        // Обновляем информацию об элементе
        if ($fob->is_submited() && $fob->is_valid_submit()) {

            // Читаем данные из формы
            $values = $fob->get_submited_values();


            // Формируем SQL-запрос на обновление
            $update = array(
                'title' => "'" . addslashes(trim($values['title'])) . "'",
                'ratio' => "'" . (float)str_replace(",", ".", $values['ratio']) . "'",
                'ord' => (int)$values['ord'],
            );
            // Выполняем SQL-запрос
            $update_sql = array();
            foreach ($update as $key => $val) {
                $update_sql[] = $key . ' = ' . $val;
            }

            $sql = 'UPDATE ' . $this->cms->tables['catalog_currency'] . ' SET ' . join(', ', $update_sql) . ' WHERE currency_id = ' . $id;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }

            $target_url = $this->cms->form&at_url($this->plugin['name'], 'currency', array());
            header('Location: ' . $target_url);
        }

        return $output;
    }

    function currency_delete($arguments)
    {
        $id = (int)$arguments['id'];

        $sql = "DELETE FROM " . $this->cms->tables['catalog_currency'] . " WHERE currency_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }

        $target_url = $this->cms->format_url($this->plugin['name'], 'currency', array());
        header('Location: ' . $target_url);
    }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	похожие товары
    function get_likes($id, &$likes, $parent = 0, $nbsp = '')
    {
        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE parent_id='" . $parent . "' AND id<>'" . $id . "' ORDER BY type, ord DESC, title_" . $this->lang . ", id";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {
                $likes[$item["id"]] = $nbsp . $item["title_" . $this->lang];
                if ($item["type"] == "folder") {
                    $this->get_likes($id, $likes, $item["id"], str_repeat("&nbsp;", 4) . $nbsp);
                }
            }
        }
    }

    function add_one_item_like($id, $like_id)
    {
        $sql = "SELECT `type` FROM " . $this->cms->tables["catalog"] . " WHERE id='" . $like_id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            $item = $result->FetchRow();
            if ($item["type"] == "item") {
                $sql = "SELECT * FROM " . $this->cms->tables["catalog_like"] . " WHERE from_id='" . $id . "' AND to_id='" . $like_id . "'";
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                    return false;
                }
                if ($result->RecordCount() == 0) {
                    $sql = "INSERT INTO " . $this->cms->tables["catalog_like"] . " SET from_id='" . $id . "', to_id='" . $like_id . "'";
                    $result = $this->dbc->Execute($sql);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                        return false;
                    }
                }
            }
        }
    }

    function delete_one_item_like($id, $like_id)
    {
        $sql = "DELETE FROM " . $this->cms->tables["catalog_like"] . " WHERE from_id='" . $id . "' AND to_id='" . $like_id . "'";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    //............................................................................... Общие функции

    // Функция формирования строки навигации по страницам
    function get_navigation_string($page, $num_records, $records_per_page, $command, $arguments)
    {

        $string = "";

        // Рассчитываем общее число страничек
        $num_pages = ceil($num_records / $records_per_page);

        // формируем навигацию только в случае, если количество страниц больше одной
        if ($num_pages > 1) {

            // Формируем ссылки на странички
            $string .= "<p>";
            for ($i = 1; $i <= $num_pages; $i++) {
                if ($i == $page) {
                    $string .= "&nbsp;<strong>" . $i . "</strong>&nbsp; ";
                } else {
                    $string .= "&nbsp;<a href=\"" . $this->cms->format_url($this->plugin['name'], $command, $arguments) . "&arg[page]=" . $i . "\">" . $i . "</a>&nbsp; ";
                }
            }
            $string .= "</p>";
        }

        return $string;
    }

    // Функция печати списка табов
    function print_tabs($active_tab = 'index', $arguments)
    {

        // Формируем основной список табов
        $tabs = array();
        foreach ($this->tabs as $tab_id => $title) {
            $tabs[$tab_id] = "<a href=\"" . $this->cms->format_url($this->plugin["name"], $tab_id, array()) . "\">" . $title . "</a>";
        }

        // Формируем список табов из отмеченных для показа в меню списков выбора
        $selects_tabs = array();
        $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' WHERE menu_flag=1';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {

            // Печатаем визуальный разделитель
            $tabs["separator"] = "<span style=\"color:#ffffff;\">|</span>";

            // Идентификатор текущего активного списка выбора
            $selects_active_id = $arguments["id"];

            // Печатаем табы списков выбора
            $selects_active_tab = "";
            while ($record = $result->FetchRow()) {

                // Формируем собственнно таб
                $tabs[$record["id_text"]] = "<a href=\"" . $this->cms->format_url($this->plugin["name"], "selects_options", array("id" => $record["id"])) . "\">" . htmlspecialchars($record["title"]) . "</a>";

                // Находим какой именнт таб списка выбора активен
                if ($selects_active_id == $record["id"]) {
                    $selects_active_tab = $record["id_text"];
                }
            }
        }

        // Проверяем  активный таб
        if (strstr($active_tab, "_")) {
            $active_tab = substr($active_tab, 0, strpos($active_tab, "_"));
        }
        if (!isset($tabs[$active_tab])) {
            reset($tabs);
            $active_tab = key($tabs);
        }

        // Коректируем активный таб, в случае, если выбран элемент список выбора
        if ($active_tab == "selects" && !empty($selects_active_tab)) {
            $active_tab = $selects_active_tab;
        }

        // Сохраняем идентификатор активного таба в cookies
        setcookie('pm_simtab', $active_tab, time() + 3600 * 24, '');

        // Выводим табы на печать
        $output = $this->cms->int_add_tabs($tabs, $active_tab);

        return $output;
    }

    // Функция транслитерации строки текста
    function calculate_ascii_string($string)
    {

        // Таблица преобразования
        $translate_table = array(
            "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "yo", "ж" => "j", "з" => "z",
            "и" => "i", "й" => "i", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
            "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch",
            "ъ" => "", "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya", "і" => "i", "ґ" => "g", "ї" => "i"
        );

        // Преобразуем строку в нижний регистр
        $string = strtolower(trim($string));

        // Преобразуем русские буквы в латинские
        $string = strtr($string, $translate_table);

        // Убираем из строки все спецсимволы
        $string = preg_replace("/[^a-z0-9]/", "", $string);

        // Ограничиваем длину строки 30 символами
        $string = substr($string, 0, 100);

        return $string;
    }

    // Функция инициализации поддерживаемых на сайте языков
    function init_languages()
    {

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
                $languages[$record["language_id"]] = $record["alias"];

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

    // Функция инициализации опций модуля
    function init_options()
    {

        $options = array();

        // Читаем список опций из базы данных
        $sql = "SELECT * FROM " . $this->cms->tables["catalog_options"] . "";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $options[$record["name"]] = $record["value"];
            }
        }

        // Обходим список текущих опций и обновляем их
        foreach ($this->options as $name => $value) {

            // Если опция задана в базе данных
            if (isset($options[$name])) {
                $this->options[$name] = $options[$name];

                // Если опция отсутствует
            } else {

                // Формируем данные для SQL-запроса
                $insert_sql = array(
                    "name" => "'" . $name . "'",
                    "value" => "'" . $value . "'"
                );

                // Сохраняем в базе данных значение по умолчанию
                $sql = 'INSERT INTO ' . $this->cms->tables['catalog_options'] . ' (' . join(', ', array_keys($insert_sql)) . ') VALUES (' . join(', ', $insert_sql) . ')';
                $result = $this->dbc->Execute($sql);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                    return false;
                }
            }
        }
    }


    function insertToImageLogo($filename, $pathToLogo = '')
    {
        return;
//		if(!$pathToLogo) $pathToLogo = ROOT_HTML_PATH."content/water_logo.png";
        if (!$pathToLogo) $pathToLogo = ROOT_HTML_PATH . "content/water_logo.gif";

        $logoSize = getimagesize($pathToLogo);

        $imgSize = getimagesize($filename);
        $file_info = pathinfo($filename);
        $ext = $file_info["extension"];

        if (function_exists('imagecreatefrompng')) {
            $imgLogo = imagecreatefromgif($pathToLogo);
//			$imgLogo = imagecreatefrompng($pathToLogo);
//			imagesavealpha($imgLogo, TRUE);
        }
        if (!$imgLogo) return false;
        if (($ext == 'jpg' || $ext == 'jpeg') && function_exists('imagecreatefromjpeg')) {
            $img = imagecreatefromjpeg($filename);
        } elseif ($ext == 'png' && function_exists('imagecreatefrompng')) {
            $img = imagecreatefrompng($filename);
        } elseif ($ext == 'gif' && function_exists('imagecreatefromgif')) {
            $img = imagecreatefromgif($filename);
        } else {
            return false;
        }

        if (!$img) return false;


//		header('Content-Type: image/gif');
//		imagepng($dst_im);
//		die();

        if (function_exists('imagecopy')) {
//			$xDst = $imgSize[0]-$logoSize[0]-10;
//			$yDst = $imgSize[1]-$logoSize[1]-10;
            $xDst = round($imgSize[0] / 2);
            $yDst = $imgSize[1] - $logoSize[1] - 20;
//			echo $filename."<br>";
//			echo "IMG: ".$imgSize[0]." ".$imgSize[1]." logoSize: ".$logoSize[0]." ".$logoSize[1]." "." x: ".$xDst." y: ".$yDst."<Br>";
            imagecopymerge($img, $imgLogo, $xDst, $yDst, 0, 0, $logoSize[0], $logoSize[1], 10);
        } else return false;

//		header('Content-Type: image/gif');
//		imagepng($img);
//		die();

        if (($ext == 'jpg' || $ext == 'jpeg') && function_exists('imagejpeg')) {
            $img = imagejpeg($img, $filename, 90);
        } elseif ($ext == 'png' && function_exists('imagepng')) {
            $img = imagepng($img, $filename);
        } elseif ($ext == 'gif' && function_exists('imagegif')) {
            $img = imagegif($img, $filename);
        } else {
            return false;
        }
    }

    // Функция инициализации таблиц
    function init_tables()
    {
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


    //.............................................................................................
    function getWords()
    {
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

    function getState()
    {
        $ret[0] = "Набор открыт";
        $ret[1] = "Набор закрыт";
//		$ret[2] = "ожидается";
        return $ret;
    }

    // Функция печати списка элементов каталога
    function actions($arguments)
    {

        $output = "";

        // Формируем путь к странице
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', array()) . '">Список каталогов</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'actions', array()) . '">Акционные товары</a>';
        $this->cms->intSetHierarchy();

        // Рассчитываем общее количество элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog'] . ' WHERE action_for_user>0';
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $record = $result->FetchRow();
        $num_records = $record["num_records"];

        // Выводим список элементов
        if ($num_records > 0) {


            // Читаем записи из каталога
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE action_for_user>0 ORDER BY ord DESC, title_' . $this->lang . ', id';
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }

            // Начинаем формирование формы для групповой обработки элементов
            $output .= '<form name="update" method="post" action="' . $this->cms->format_url($this->plugin['name'], 'update_price', array()) . '">';

            // Печатаем заголовок таблицы
            $output .= '
			<div class="admin-table2"><div class="width-fix"><table>
				<tr>
					<td class="title-h" style="width: 50%">Название<br>Алиас</td>
					<td class="act-h">Цена</td>
					<td class="act-h">Акция N-му юзеру</td>
					<td class="act-h">Состояние</td>
					<td class="act-h" style="width: 10%">Вкл./Выкл.</td>
					<td class="act-h" colspan="2">Операции</td>
				</tr>
			';

            // Печатаем строки таблицы
            $counter = 1;
            while ($item = $result->FetchRow()) {

                // Формируем список аргументов
                $arguments['id'] = $item['id'];

                // Находим название набора полей
                if (!empty($fieldsets[$item["fieldset"]])) {
                    $fieldset = htmlspecialchars($fieldsets[$item["fieldset"]]);
                } else {
                    $fieldset = "Пустой";
                }

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                $title_link = $this->cms->format_url($this->plugin['name'], "records_edit", $arguments);

                // Формируем список доступных действий
                $actions = array(
                    'edit' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'records_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить &quot;' . htmlspecialchars($item['title_' . $this->lang]) . '&quot;?\')" href="' . $this->cms->format_url($this->plugin['name'], 'records_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title" style="width: 50%"><a href="' . $title_link . '" title="id: ' . $item["id"] . '">' . htmlspecialchars($item['title_' . $this->lang]) . '</a><br>' . htmlspecialchars($item['alias']) . '</td>
					<td class="act">' . (($item["type"] == 'item') ? '<input type="text" name="price[' . $item["id"] . ']" style="width: 54px;" class="fob-text" value="' . $item["price"] . '">' : '') . '</td>
					<td class="act">' . (($item["type"] == 'item') ? '<input type="text" name="action_for_user[' . $item["id"] . ']" style="width: 54px;" class="fob-text" value="' . $item["action_for_user"] . '">' : '') . '</td>';

//                if ($item["type"] == 'item') {
//                    $output .= '<td class="act"><select name="state[' . $item["id"] . ']">';
//                    $output .= '<option value="0"' . (($item["state"] == 0) ? " selected" : "") . '>Набор открыт</option>';
//                    $output .= '<option value="1"' . (($item["state"] == 1) ? " selected" : "") . '>Набор закрыт</option>';
//					  $output .= '<option value="2"'.(($item["state"]==2)?" selected":"").'>ожидается</option>';
//                    $output .= '</select></td>';
//                }

//                $output .= '
//					<td class="act" style="width: 10%">' . $switch . '</td>
//					<td class="act">' . $actions['edit'] . '</td>
//					<td class="act last">' . $actions['delete'] . '</td>
//					</tr>
//				';
                $counter++;

            }

            $output .= '</table></div></div>';

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список элементов пуст</p>';
        }

        // Заканчиваем формирование формы для групповых операций
        $output .= '</form>';

        $output .= '&nbsp;&nbsp;<input type="button" class="button" value="Сохранить изменения" onClick="document.forms[\'update\'].action=\'' . $this->cms->format_url($this->plugin['name'], 'update_price', array()) . '\';document.forms[\'update\'].submit();">';
        $output .= '</form>';

        return $output;
    }

    function createExcelFile()
    {
        $currency = $this->getCurrency(1);
        $states = $this->getState();
        $url_start = ROOT_URL;

        $parents_id = array();
        $parents_relative = array();
        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE type='folder' AND enabled=1";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        while ($item = $result->FetchRow()) {
            $parents_id[$item["id"]] = $item;
            $parents_relative[$item["relative_url"]] = $item;
        }

        $src = '';
        $src .= '<table border="1" cellspacing="0" cellpadding="2">';
        $src .= '<tr>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Тип товара</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Производитель</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Название  товара</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Описиние товара</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Цена, $</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Цена, грн</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Гарантия</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Наличие</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Ссылка на товар</td>';
        $src .= '<td style="font-weight: bold; text-align: center; font-size: 11px; background: #ccffcc">Изображение</td>';
        $src .= '</tr>';

        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE type='item' AND enabled=1 ORDER BY relative_url ASC, parent_id ASC, ord DESC, title_" . $this->lang . ", id";
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        while ($item = $result->FetchRow()) {
            if (!isset($parents_id[$item["parent_id"]])) continue;

            $title_type = '&nbsp;';
            $title_brand = '&nbsp;';
            $arrPath = explode("/", substr($item["relative_url"], 1, -1));
            $firstPath = "/" . $arrPath[0] . "/";
            if (isset($parents_relative[$firstPath])) {
                $title_type = $parents_relative[$firstPath]["title_ru"];
            }
            if ($title_type != $parents_id[$item["parent_id"]]["title_ru"]) {
                $title_brand = $parents_id[$item["parent_id"]]["title_ru"];
            }

            $src .= '<tr>';
            $src .= '<td style="font-size: 11px;">' . $title_type . '</td>';
            $src .= '<td style="font-size: 11px;">' . $title_brand . '</td>';
            $src .= '<td style="font-size: 11px;">' . $item["title_ru"] . '</td>';
            $src .= '<td style="font-size: 11px;">' . ((strip_tags($item["short_description_ru"])) ? strip_tags($item["short_description_ru"]) : "&nbsp;") . '</td>';
            $src .= '<td style="font-size: 11px;">' . number_format($item["price"], 2, ",", "") . '</td>';
            $src .= '<td style="font-size: 11px;">' . number_format($item["price"] * $currency["ratio"], 2, ",", "") . '</td>';

            $sql = "SELECT * FROM pm_catalog_table_" . $item["fieldset"] . " WHERE record_id='" . $item["id"] . "'";
            $resultGuearantee = $this->dbc->Execute($sql);
            $titleGuarantee = '';
            if ($resultGuearantee && $resultGuearantee->RecordCount() > 0) {
                $itemGuearantee = $resultGuearantee->FetchRow();
                if (isset($itemGuearantee["guarantee_ru"])) {
                    $titleGuarantee = $itemGuearantee["guarantee_ru"];
                }
            }
            $src .= '<td style="font-size: 11px;">' . (($titleGuarantee) ? $titleGuarantee : "&nbsp;") . '</td>';
            $src .= '<td style="font-size: 11px;">' . $states[$item["state"]] . '</td>';
            $src .= '<td style="font-size: 11px;">' . $url_start . 'catalogue' . $item["relative_url"] . '</td>';
            $src .= '<td style="font-size: 11px;">' . $url_start . substr($item["img_sm"], 1) . '</td>';
            $src .= '</tr>';
        }
        $src .= '</table>';

        $src = '<html><head><title>Прайс</title><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"></head><body>' . $src . '';
        $src .= '</body></html>';

//		$fl = fopen(ROOT_HTML_PATH."content/files/price.xls", "w");
//		fputs($fl, $src);
//		fclose($fl);

//		echo $src;
//		die();
    }

    function fullResize()
    {
        $sql = "ALTER TABLE " . $this->cms->tables["catalog"] . " ADD COLUMN img_resized int(1) NOT NULL default '0'";
        $result = $this->dbc->Execute($sql);
        $sql = "ALTER TABLE " . $this->cms->tables["catalog_gallery"] . " ADD COLUMN img_resized int(1) NOT NULL default '0'";
        $result = $this->dbc->Execute($sql);

        $options = $this->options;

        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE img<>'' AND img_resized=0 ORDER BY id ASC";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {

                $target = ROOT_HTML_PATH . substr($item["img"], 1);
                $pathinfo = pathinfo($target);
                $extension = strtolower($pathinfo["extension"]);

                if (file_exists($target)) {
                    if ($item["img_sm"] == '') {
                        $path_file = $this->path_upload . "item_" . $item["id"] . "_sm." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img_sm='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm_width"], $options["img_sm_height"], 1);
                    @chmod($target_sm, 0777);

                    if ($item["img_sm1"] == '') {
                        $path_file = $this->path_upload . "item_" . $item["id"] . "_sm1." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img_sm1='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm1"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm1_width"], $options["img_sm1_height"], 1);
                    @chmod($target_sm, 0777);

                    if ($item["img_sm2"] == '') {
                        $path_file = $this->path_upload . "item_" . $item["id"] . "_sm2." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img_sm2='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm2"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm2_width"], $options["img_sm2_height"], 1);
                    @chmod($target_sm, 0777);

                    $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img_resized=1 WHERE id='" . $item["id"] . "'";
                    $this->dbc->Execute($sql);
                } else {
                    $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img='', img_sm='', img_sm1='', img_sm2='' WHERE id='" . $item["id"] . "'";
                    $this->dbc->Execute($sql);
                }

            }
        }

        $sql = "SELECT * FROM " . $this->cms->tables["catalog_gallery"] . " WHERE img<>'' AND img_resized=0 ORDER BY id ASC";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {

                $target = ROOT_HTML_PATH . substr($item["img"], 1);
                $pathinfo = pathinfo($target);
                $extension = strtolower($pathinfo["extension"]);

                if (file_exists($target)) {
                    if ($item["img_sm"] == '') {
                        $path_file = $this->path_upload . "gallery/" . "item_" . $item["record_id"] . "_" . $item["id"] . "_sm." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog_gallery"] . " SET img_sm='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm_width"], $options["img_sm_height"], 1);
                    @chmod($target_sm, 0777);

                    if ($item["img_sm1"] == '') {
                        $path_file = $this->path_upload . "gallery/" . "item_" . $item["record_id"] . "_" . $item["id"] . "_sm1." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog_gallery"] . " SET img_sm1='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm1"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm1_width"], $options["img_sm1_height"], 1);
                    @chmod($target_sm, 0777);

                    if ($item["img_sm2"] == '') {
                        $path_file = $this->path_upload . "gallery/" . "item_" . $item["record_id"] . "_" . $item["id"] . "_sm2." . $extension;
                        $target_sm = ROOT_HTML_PATH . $path_file;
                        $sql = "UPDATE " . $this->cms->tables["catalog_gallery"] . " SET img_sm2='/" . $path_file . "' WHERE id='" . $item["id"] . "'";
                        $this->dbc->Execute($sql);
                    } else {
                        $target_sm = ROOT_HTML_PATH . substr($item["img_sm2"], 1);
                    }
                    $this->cms->files->copyFile($target, $target_sm);
                    $this->cms->files->resizeImageMode($target_sm, $options["img_sm2_width"], $options["img_sm2_height"], 1);
                    @chmod($target_sm, 0777);

                    $sql = "UPDATE " . $this->cms->tables["catalog_gallery"] . " SET img_resized=1 WHERE id='" . $item["id"] . "'";
                    $this->dbc->Execute($sql);
                } else {
                    $sql = "UPDATE " . $this->cms->tables["catalog_gallery"] . " SET img='', img_sm='', img_sm1='', img_sm2='' WHERE id='" . $item["id"] . "'";
                    $this->dbc->Execute($sql);
                }

            }
        }

    }

    function fullSetImgTemp()
    {
        $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE img='' AND img_resized=0 ORDER BY id ASC";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {

                $sql = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE img<>'' ORDER BY RAND() LIMIT 0, 1";
                $result2 = $this->dbc->Execute($sql);
                $item2 = $result2->FetchRow();

                $sql = "UPDATE " . $this->cms->tables["catalog"] . " SET img='" . $item2["img"] . "', img_sm='" . $item2["img_sm"] . "', img_sm1='" . $item2["img_sm1"] . "', img_sm2='" . $item2["img_sm2"] . "' WHERE id='" . $item["id"] . "'";
                $this->dbc->Execute($sql);

            }
        }

    }


//	Комментарии. Начало
    function comments($arguments)
    {

        $output = "";

        $id = (int)$arguments['id'];
        $page = (int)$arguments['page_comments'];

        // Читаем список определенных наборов полей
        $fieldsets = $this->get_fieldsets();

        // Формируем путь к странице
        $arg = $arguments;
        unset($arg["id"]);
        unset($arg["page_comments"]);
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', $arg) . '">Список товаров</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments', $arguments) . '">Комментарии</a>';
        $this->cms->intSetHierarchy();

        // Рассчитываем общее количество элементов
        $sql = 'SELECT count(*) as num_records FROM ' . $this->cms->tables['catalog_comments'] . ' WHERE catalog_id=' . $id;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $record = $result->FetchRow();
        $num_records = $record["num_records"];

        // Выводим список элементов
        if ($num_records > 0) {

            // Рассчитываем количество записей на страничку
            $records_per_page = (int)$this->options["records_per_page_admin"];

            // Корректируем номер страницы
            if ($page < 1) {
                $page = 1;
            }
            if ($page > ceil($num_records / $records_per_page)) {
                $page = ceil($num_records / $records_per_page);
            }

            // Формируем строку навигации по записям
            $arg = $arguments;
            unset($arg["page"]);
            $navigation_string = $this->get_navigation_string($page, "page_comments", $num_records, $records_per_page, 'comments', $arguments);

            // Читаем записи из каталога
            $sql = 'SELECT * FROM ' . $this->cms->tables['catalog_comments'] . ' WHERE catalog_id=' . $id . ' ORDER BY dates DESC LIMIT ' . ($page - 1) * $records_per_page . ', ' . $records_per_page;
            $result = $this->dbc->Execute($sql);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
                return false;
            }

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

            // Начинаем формирование формы для групповой обработки элементов
//			$output .= '<form name="update" method="post">';

            // Печатаем заголовок таблицы
/*//            $output .= '
//			<div class="admin-table2"><div class="width-fix"><table>
//				<tr>
//					<td class="title-h">Комментарий</td>
//					<td class="act-h">Автор</td>
//					<td class="act-h" style="width: 10%">Дата</td>
//					<td class="act-h" style="width: 10%">Вкл./Выкл.</td>
//					<td class="act-h last" colspan="2">Операции</td>
//				</tr>
//			';*/

            // Печатаем строки таблицы
            $counter = 1;
            $findItem = false;
            while ($item = $result->FetchRow()) {
                // Формируем список аргументов
                $arguments['id'] = $id;
                $arguments['cid'] = $item['catalog_comments_id'];
                $arguments['page'] = $page;

                // Формируем переключатель состояния записи
                if ($item['enabled']) {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments_switch_state', $arguments) . '" class="on">вкл</a>';
                } else {
                    $switch = '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments_switch_state', $arguments) . '" class="off">выкл</a>';
                }

                $title_link = $this->cms->format_url($this->plugin['name'], "comments_edit", $arguments);

                // Формируем список доступных действий
                $actions = array(
                    'edit' => '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments_edit', $arguments) . '">изменить</a>',
                    'delete' => '<a onclick="return confirm(\'Удалить?\')" href="' . $this->cms->format_url($this->plugin['name'], 'comments_delete', $arguments) . '">удалить</a>',
                );

                // Печатаем строку таблицы
                $output .= '
					<tr onmouseover="row_ovr(this)" onmouseout="row_out(this)">
					<td class="title"><a href="' . $title_link . '">' . substr(strip_tags($item["comments"]), 0, 100) . '</a></td>
					<td class="act">' . htmlspecialchars($item['fio']) . '</td>';

                $output .= '<td class="act" nowrap>' . date("d.m.Y H:i:s", strtotime($item['dates'])) . '</td>';
                $output .= '<td class="act">' . $switch . '</td>';
                $output .= '
					<td class="act">' . $actions['edit'] . '</td>
					<td class="act last">' . $actions['delete'] . '</td>
					</tr>
				';
                $counter++;

            }

            $output .= '</table></div></div>';

            // Печатаем строку навигации по страницам
            $output .= $navigation_string;

        } else {

            // Печатаем сообщение об отсутствии записей
            $output .= '<p>Список элементов пуст</p>';
        }

        return $output;
    }

    function comments_edit($arguments)
    {
        $output = '';
        $id = (int)$arguments['id'];
        $page_comments = (int)$arguments['page_comments'];
        $page = (int)$arguments['page_comments'];
        $cid = (int)$arguments['cid'];

        $arg = $arguments;
        unset($arg["id"]);
        unset($arg["cid"]);
        unset($arg["page_comments"]);
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'records', $arg) . '">Список товаров</a>';
        $arg = $arguments;
        unset($arg["cid"]);
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments', $arg) . '">Комментарии</a>';
        $this->cms->pathModule[] = '<a href="' . $this->cms->format_url($this->plugin['name'], 'comments_edit', $arguments) . '">Редактирование</a>';
        $this->cms->intSetHierarchy();

        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_comments'] . ' WHERE catalog_comments_id=' . $cid;
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
        $fob->add_hidden('$' . $this->cms->request_vars['command'], 'comments_edit');
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[page]', $page);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[page_comments]', $page_comments);
        $fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[cid]', $cid);

        $fob->add_text(true, 'fio', $item['fio'], 'Автор', '', 'fob-text', '', 'text', '', '');
        $fob->add_wysiwyg(false, 'comments', $item['comments'], 'Комментарий', '', 30, 20, 'fob-textarea');
        $fob->add_checkbox(false, 'enabled', '1', 'Включена', '', '', $item['enabled'], 'fob-check', '');

        $fob->add_button('submit', 'submit', 'Сохранить', '');
        $fob->add_button('submit', 'submit', 'Сохранить и остаться', '');
        $output .= $fob->display('return');

        if ($fob->is_submited() && $fob->is_valid_submit()) {
            $values = $fob->get_submited_values();

            $update = array(
                'fio' => "'" . addslashes(trim($values['fio'])) . "'",
                'comments' => "'" . addslashes(trim($values['comments'])) . "'",
                'enabled' => "'" . (int)$values['enabled'] . "'",
            );

            $update_sql = '';
            $update_sep = '';
            while (list($field, $value) = each($update)) {
                $update_sql .= $update_sep . $field . '=' . $value . ' ';
                $update_sep = ', ';
            }
            $query = 'UPDATE ' . $this->cms->tables['catalog_comments'] . ' SET ' . $update_sql . ' WHERE catalog_comments_id=' . $cid;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return false;
            }
            if ($values["submit"] == "Сохранить и остаться") {
                $target_url = $this->cms->format_url($this->plugin['name'], 'comments_edit', $arguments);
            } else {
                $target_url = $this->cms->format_url($this->plugin['name'], 'comments', $arg);
            }
            header('Location: ' . $target_url);
            return $output;
        }
        return $output;
    }

    function comments_switch_state($arguments)
    {
        $cid = (int)$arguments['cid'];

        $sql = 'SELECT enabled FROM ' . $this->cms->tables['catalog_comments'] . ' WHERE catalog_comments_id=' . $cid;
        $result = $this->dbc->Execute($sql);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        $enabled = 0;
        $record = $result->FetchRow();
        if ($record["enabled"] == 0) {
            $enabled = 1;
        }

        $query = 'UPDATE ' . $this->cms->tables['catalog_comments'] . ' SET enabled="' . $enabled . '" WHERE catalog_comments_id=' . $cid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        unset($arguments["cid"]);
        $target_url = $this->cms->format_url($this->plugin['name'], 'comments', $arguments);
        header('Location: ' . $target_url);
        return '';
    }

    function comments_delete($arguments)
    {
        $cid = (int)$arguments['cid'];
        $query = 'DELETE FROM ' . $this->cms->tables['catalog_comments'] . ' WHERE catalog_comments_id=' . $cid;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
            return false;
        }
        unset($arguments["cid"]);
        $target_url = $this->cms->format_url($this->plugin['name'], 'comments', $arguments);
        header('Location: ' . $target_url);
    }

//	Комментарии. Конец

}

?>