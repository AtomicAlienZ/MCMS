<?php

class catalog_handler {

    var $plugin = array();
    var $config = array();
    var $dbc;
    var $mai    = '';

    //Флаг (Работать корзине впаре с плагином зависимим товаром)
    var $depended_products = 1;

    // Флаг работы каталога в режиме интернет-магазина
    var $shop_flag = 1;

    // Список переменных, которые могут быть переданы методом GET
    var $possibleGETVars = array("cid", "cpage");
    var $GETVar          = array();

    // Регулярное выражение для проверки электронного адреса
    var $regexp_email = '/^[a-z0-9\.\-_]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is';

    // Электронный адрес администратора сайта
//	var $admin_email = "andrey@kai-studio.su";
    //var $admin_email = "vladimir@ukr-contact.com";
    // Текущий номер страницы
    var $page = 1;

    // Параметры страницы для функции печати навигации
    var $countItemInPage = 1;
    var $totalItem       = 0;

    // Идентификатор текущего каталога
    var $catalog_id = 0;

    // Путь к текущему каталогу
    var $catalog_path = "";

    // Список поддерживаемых языков
    var $langs = array();

    // Текущий язык сайта
    var $lang = "ru";

    // Текущий шаблон
    var $template = "default";

    // Список наборов полей
    var $fieldsets = array();

    // Массив списков выбора
    var $selects = array();

    // Опции модуля
    var $options = array();

    // Список команд, определенных в плагине
    var $commands = array(
        "default"     => "catalog",
        "maps"        => "maps",
        "index"       => "index",
        "lenta"       => "lenta",
        "left"        => "side",
        "action"      => "actions",
        //		"you_win"		=> "you_win",
        "basket"      => "basket",
        "basket_info" => "basket_info",
        "basket_add"  => "basket_add",
        "basket_edit" => "basket_edit",
        "order"       => "order_add",
        "preorder"    => "preorder",
        "compare"     => "compare",
        "price"       => "price"
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

    var $webmoney = array("wmz" => "Z945555243190", "wmu" => "U022593270787");

    var $min_price_from_action            = 1000;
    var $catalog_id_price_more_min_action = 0; //id товара, стоимость которого более 1000 грн

    var $its_root = false;

    var $SotrType = 3;
    var $PriceMin = 0;
    var $PriceMax = 0;

    //.......................................................... Основная функция обработки плагина

    // Функция инициализации плагина
    function exec($arguments) {

        //Фильтр
        if (!isset($_SESSION["sort_type"])) {
            $_SESSION["sort_type"] = 3;
        }
        $this->SotrType = $_SESSION["sort_type"];

        if (!isset($_SESSION["price_min"])) {
            $_SESSION["price_min"] = 0;
        }
        $this->PriceMin = $_SESSION["price_min"];

        if (!isset($_SESSION["price_max"])) {
            $_SESSION["price_max"] = 0;
        }
        $this->PriceMax = $_SESSION["price_max"];

        //	print_r($arguments); die();
        $this->cms->vars_falseget['catalog'] = 'false';
        // Читаем идентификатор раздела и идентификатор блока в структуре сайта
        $s_id = (int)$arguments['s_id'];
        $toc_id = (int)$arguments['toc_id'];

        // Инициализируем таблицы
        $this->dbc = $this->cms->dbc;
        $this->init_tables();

        $this->deleteOldInfo();

        // Инициализируем опции модуля
        $this->init_options();

        // Инициализируем список поддерживаемых языков
        $this->init_languages();

        // Производим парсинг переменных переданных в адресной строке
        $this->parseGetVar();

        // Если каталог работает в обычном режиме, а не как интернет магазин, то удаляем ненужные команды
        if (!$this->shop_flag) {
            unset($this->commands["basket"]);
            unset($this->commands["basket_info"]);
            unset($this->commands["basket_add"]);
            unset($this->commands["basket_edit"]);
            unset($this->commands["order"]);
        }

        // Инициализируем идентификатор каталога
        $this->catalog_id = $this->get_catalog_id($s_id, $toc_id);

        // Инициализируем путь к текущему каталогу
        $this->catalog_path = $this->get_catalog_path($this->catalog_id);

        // Инициализируем номер текущей страницы

        //MOD
        //print_r($this->GETVar["page"]); die();

        if (isset($this->GETVar["cpage"]) && $this->GETVar["cpage"] > 1) {
            $page = (int)$this->GETVar["cpage"];
        } else {
            $page = 1;
        }
        $this->page = $page;

        //Предзаказ
        if (isset($this->GETVar["cid"])) {
            $this->cms->vars_404['structure'] = true;
            //die($this->GETVar["cid"]);
        }

        // Инициализируем список наборов полей
        $this->fieldsets = $this->get_fieldsets();

        // Инициализируем списки выборов
        $this->selects = $this->get_selects();

        // Определяем функцию для запуска на основании имени шаблона
        $template = $arguments["tpl_alias"];
        if (empty($template)) {
            $template = "default";
        }
        $this->template = $template;

        // Корректируем шаблон на основании команды переданной в адресной строке
        if (!empty($_GET["action"])) {
            $template .= "_" . $_GET["action"];
            $this->cms->vars_falseget['catalog'] = 'true';
        }

        // Вызываем запрашиваемую функцию и получаем массив с рассчитанными переменными
        if (isset($this->commands[ $template ]) && method_exists($this, $this->commands[ $template ])) {
            $command = $this->commands[ $template ];
            $temp = $this->basket('');
            if ($temp['state'] != 'error') {
                $this->page_info['total_quantity'] = $temp['total_quantity'];
                $this->page_info['total_cost'] = $temp['total_cost'];
            }

            $block = $this->$command($arguments);
        } else {
            $block = array();
        }
        $block['protect_img'] = ROOT_URL . 'protect_image.php';

        $block["compare"] = array();
        $block["isset_this_compare"] = 0;
        $block["count_compare"] = 0;

        if (isset($_SESSION["compare"])) {
            $block["compare"] = $_SESSION["compare"];
            $block["count_compare"] = count($block["compare"]);
            $block["isset_this_compare"] = (isset($block["id"]) && isset($_SESSION["compare"][ $block["id"] ])) ? 1 : 0;
        }

        // Добавляем в блок путь к папке с шаблонами
        $block["pathTemplate"] = $tpl_file = $this->plugin['path'] . 'tpls/';

        if (isset($_SESSION["currency"]) && isset($_SESSION["all_currency"][ $_SESSION["currency"] ])) {
            $block["currency"] = $_SESSION["all_currency"][ $_SESSION["currency"] ];
        }

        $block["delivery"] = $this->delivery;
        foreach ($block["delivery"] as $key => $val) {
            $block["delivery"][ $key ]["price"] = $this->get_price_currency($block["delivery"][ $key ]["price"]);
        }

        $block["webmoney"] = $this->webmoney;

        $block["catalog_id_price_more_min_action"] = $this->catalog_id_price_more_min_action;

        //		echo "<pre>"; print_r($_SESSION["all_currency"]); echo "</pre>";
        //		if($arguments["tpl_alias"]=="maps") {
        //			echo "<pre style='text-align:left;'>"; print_r($block); echo "</pre>";
        //			die();
        //		}

        //Проверяем на единичное изображение
        $block['singlePhoto'] = 0;
        //		echo '<pre>'; print_r($block); die();
        if (isset($_GET['i'])) {
            $this->cms->vars_falseget['catalog'] = 'true';
            $sql = "SELECT * FROM " . $this->cms->tables['catalog_gallery'] . " WHERE id = " . (int)$_GET['i'];
            $result = $result = $this->dbc->Execute($sql);
            //			$this->cms->vars_404['catalog'] = 'false';
            if ($result && $result->RecordCount() > 0) {
            } else {
                $this->cms->vars_falseget['catalog'] = 'false';
            }
            $block['singleimg'] = $result->FetchRow();
            $block['singlePhoto'] = 1;
            if (!empty($block['singleimg']["meta_title"])) {
                $this->page_info["meta_title"] = $block['singleimg']["meta_title"];
            } else {
            }
            if ($block['singleimg']["meta_keywords"] != '')
                $this->page_info["structure"]["keywords"] = $block['singleimg']["meta_keywords"];
            if ($block['singleimg']["meta_description"] != '')
                $this->page_info["structure"]["description"] = $block['singleimg']["meta_description"];
            $block['lastTab'] = 'gallery';
        } elseif (isset($_GET['c'])) {
            $this->cms->vars_falseget['catalog'] = 'true';
            $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE id = " . (int)$_GET['c'];
            $result = $result = $this->dbc->Execute($sql);
            $block['singleimg'] = $result->FetchRow();
            if (!isset($block['singleimg']["id"]))
                $this->cms->vars_falseget['catalog'] = 'false';
            $block['singlePhoto'] = 2;
            $block['lastTab'] = 'gallery';
        }

        //echo '<pre>'; print_r($block); echo '</pre>'; die();

        //$this->cms->vars_404['catalog'] = 'false';
        //echo '<pre>'; print_r($this->cms->vars_404['structure']); echo '</pre>'; die();

        // Производим обработку шаблона и возвращаем результат
        return $this->parse($template, $block);
    }

    // Функция печати каталога списка оборудования
    function catalog($parameters) {

        //        die('print catalog');

        $block = array();
        // Находим идентификатор элемента
        if ($this->page_info["virtuals"][0] == "") {
            $id = $this->catalog_id;
        } else {
            $id = $this->get_id_element_by_virtuals(); //marker
        }

        $this->its_root = (bool)($id == $this->catalog_id);

        // Читаем информацию об элементе
        $record = $this->get_record($id);
        if (($record["type"] == "item") && $this->page_info["structure"]["alias"] == "catalogs") {
            //$this->page_info['show_top'] = 0;
        }

        $this->basket_add();

        if ($record) {

            $this->cms->updateWords($record["words"]);

            // Начинаем формирование данных для шаблона
            $block = $record;

            $this->addComments($block);

            //  Рассчитываем подшаблон для отображения страницы
            if ($record["type"] == "folder") {
                $block["tpl"] = "items";
            } else {
                $block["tpl"] = "item";
            }

            // Добавляем флаг работы каталога в режиме интернет-магазина
            $block["shop_flag"] = $this->shop_flag;

            // Формируем ссылку на корзину покупок
            if ($this->shop_flag) {
                //$block["basket_url"] = $this->catalog_path."basket/";
                $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
            }

            // Формируем ссылку на текущий элемент
            //			$block["url"] = $this->catalog_path."cid_".$id."/";
            $block["url"] = $this->get_url_element($id, $record);

            // Формируем ссылку на родительский элемент
            if ($id == $this->catalog_id) {
                $block["parent_url"] = $this->catalog_path;
            } else {
                //				$block["parent_url"] = $this->catalog_path."cid_".$record["parent_id"]."/";
                $block["parent_url"] = $this->get_url_element($record["parent_id"]);
            }

            // Устанавливаем статус выполнения запроса
            $block["state"] = "done";

            // Читаем список дополнительных полей элемента каталога
            $additional = $this->get_additional($id, $record["fieldset"], $record["type"]);

            $sql1 = "SELECT options, id_text, title, type FROM pm_catalog_fieldsets_data WHERE fieldset='" . $record["fieldset"] . "';";
            $sql1 = $this->dbc->Execute($sql1);
            while ($nameofdb = $sql1->FetchRow()) {
                $addit[ $nameofdb['options'] ]['field'] = $nameofdb['title'];
                $addit[ $nameofdb['options'] ]['type'] = $nameofdb['type'];
                $sql2 = "SELECT title_ru FROM pm_catalog_select_" . $nameofdb['options'] . " WHERE enabled=1";
                $sql2 = $this->dbc->Execute($sql2);

                $sql3 = "SELECT" . $nameofdb['id_text'] . "_ru FROM pm_catalog_table_tour";
                $sql3 = $this->dbc->Execute($sql3);

                if ($sql2) {
                    while ($selval = $sql2->FetchRow()) {
                        $addit[ $nameofdb['options'] ]['value'][] = $selval['title_ru'];
                    }
                }
            }

            $block['for_filters'] = $addit;
            $block["additional"] = $additional;
            $block["count_additional"] = count($additional);

            // Читаем список вложенных папок
            $folders = $this->get_folders($id);
            $block["folders"] = $folders;
            $block["count_folders"] = count($folders);

            // Читаем список вложенных элементов
            $items = $this->get_items($id, $record);
            $block["items"] = $items;
            $block["count_items"] = count($items);

            // Читаем список прикрепленных к элементу изображений
            $gallery = $this->get_gallery($id);
            $block["gallery"] = $gallery;
            $block["count_gallery"] = count($gallery);

            if ($record["type"] == "item") {
                $likes = $this->get_likes($id);
                $block["likes"] = $likes;
                $block["count_likes"] = count($likes);
            }

            // Читаем список прикрепленных к элементу файлов
            //			$files = $this->get_files($id);
            //			$block["files"] = $files;
            //			$block["count_files"] = count($files);

            // Расскомментировать, если нужно выводить с список братьев
            // $brothers = $this->get_items($record["parent_id"]);
            // $block["brothers"] = $brothers;
            // $block["count_brothers"] = count($brothers);

            // Добавляем путь к текущему элементу
            $path = $this->get_path($id);
            // $block["path"] = $path;
            // $block["count_path"] = count($path);

            // Добавляем новые элементы в основной путь сайта
            foreach ($path as $path_id => $path_element) {
                if ($path_id != 0) {
                    $this->cms->page_info["path"][] = $path_element;
                }
            }

            // Добавляем ссылки навигации
            $this->setNavi($block);

            // Увеличиваем число просмотров элемента каталога
            $this->increase_num_views($id);

            if ($record["parent_id"] != 0) {
                // Начинаем формирование данных для шаблона
                $this->page_info["title"] = $record["title"];
                if (!empty($record["meta_title"])) {
                    $this->page_info["meta_title"] = $record["meta_title"];
                    if ($this->page >= 2) {
                        $this->page_info["meta_title"] .= ' - Страница ' . $this->page;
                    }
                }
                if ($record["meta_keywords"] != '')
                    $this->page_info["structure"]["keywords"] = $record["meta_keywords"];
                if ($record["meta_description"] != '')
                    $this->page_info["structure"]["description"] = $record["meta_description"];
                if ($this->page >= 2) {
                    $this->page_info["structure"]["description"] .= ' - Страница ' . $this->page;
                }
            }

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "item"
            );
        }

        //Находимся ли мы на главной странице каталога или нет?
        $tmp = $_SERVER['REQUEST_URI'];
        $tmp = explode('/', $tmp);
        $tmp2 = Array();
        foreach ($tmp as $a) {
            if (!((trim($a) == '/') or (trim($a) == ''))) {
                $tmp2[] = trim($a);
            }
        }
        Unset($tmp);
        $MainCatalogPage = (count($tmp2) == 1) ? true : false;
        if ($MainCatalogPage) {
            if ($tmp2[0] == 'catalog') {
                $MainCatalogPage = true;
            } else {
                $MainCatalogPage = false;
            }
        }
        $block['MainCatalogPage'] = $MainCatalogPage;
        if ($block['MainCatalogPage'] === true) {
            $block['MainCatalogPageFolders'] = $this->side($parameters);
        }

        //Это второй уровень каталога подкатегории?
        if (!$block['MainCatalogPage']) {
            $tmp = $_SERVER['REQUEST_URI'];
            $tmp = explode('/', $tmp);
            $tmp2 = Array();
            foreach ($tmp as $a) {
                if (!((trim($a) == '/') or (trim($a) == ''))) {
                    $tmp2[] = trim($a);
                }
            }
            Unset($tmp);
            $CatalogLevel2 = (count($tmp2) == 2) ? true : false;
            if ($CatalogLevel2) {
                if ($tmp2[1] != '') {
                    $CatalogLevel2 = true;
                } else {
                    $CatalogLevel2 = false;
                }
            }
            $block['CatalogLevel2'] = $CatalogLevel2;
            if ($block['CatalogLevel2'] === true) {
                //Узнаем айди текущей подкатегории...
                $subcategory = "SELECT * FROM `pm_catalog` WHERE `type` = 'folder' and `alias` = '" . addslashes(trim($tmp2[1])) . "' LIMIT 1;";
                $subcategory = $this->dbc->Execute($subcategory);
                if ($subcategory && $subcategory->RecordCount() > 0) {
                    $Result = Array();
                    while ($row = $subcategory->FetchRow()) {
                        $Result[] = $row;
                    }
                    if (count($Result) >= 1) {
                        $block['CatalogLevelCategory'] = $Result[0];
                        $subcategory = $Result[0]['id'];
                    } else {
                        $subcategory = false;
                    }
                } else {
                    $subcategory = false;
                }
                //Если есть такой подкаталог то делаем выборку с двумя вложениями
                if ($subcategory) {
                    //...

                    //Первое вхождение
                    $CatalogFolderOne = Array();
                    $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $subcategory . "' and type='folder';";
                    $sql = $this->dbc->Execute($sql);
                    if ($sql && $sql->RecordCount() > 0) {
                        while ($a = $sql->FetchRow()) {
                            $CatalogFolderOne[] = $a;
                        }
                    }

                    //Второе вхождение
                    //$CatalogFolderOne['NextFolders']
                    for ($i = 0; $i < count($CatalogFolderOne); $i++) {
                        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $CatalogFolderOne[ $i ]['id'] . "' and type='item' ORDER BY num_views DESC;";
                        $sql = $this->dbc->Execute($sql);
                        if ($sql && $sql->RecordCount() > 0) {
                            $CatalogFolderOne[ $i ]['NextFolders'] = Array();
                            $CatalogFolderOne[ $i ]['NextFoldersCount'] = 0;
                            while ($a = $sql->FetchRow()) {
                                $a['NextFolders'] = Array();
                                $CatalogFolderOne[ $i ]['NextFolders'][] = $a;
                            }
                            $CatalogFolderOne[ $i ]['NextFoldersCount'] = count($CatalogFolderOne[ $i ]['NextFolders']);
                        }
                    }
                    $block['CatalogLevelCategoryItems'] = $CatalogFolderOne;
                    $block["CatalogLevelCategoryCount"] = count($CatalogFolderOne);

                    //...
                }
            }
        }

        //echo '<pre>'; print_r($block['MainCatalogPageFolders']); echo '</pre>'; die();

        // Возвращаем результат
        return $block;
    }

    // Функция печати каталога списка оборудования
    function price($parameters) {
        $block = array();
        if ($this->page_info["virtuals"][0] == "") {
            $id = $this->catalog_id;
        } else {
            $id = $this->get_id_element_by_virtuals();
        }

        $this->its_root = (bool)($id == $this->catalog_id);

        $record = $this->get_record($id);

        if ($record) {
            $this->cms->updateWords($record["words"]);
            $block = $record;

            $block["url"] = $this->get_url_element($id, $record);

            $block["state"] = "done";

            $block["items"] = array();
            $this->setItemsPrice($block, $id);
            $block["count_items"] = count($block["items"]);

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "item"
            );
        }

        // Возвращаем результат
        return $block;
    }

    function setItemsPrice(&$block, $id, $level = 0) {
        $folders = $this->get_folders_for_price($id);
        $level = $level + 1;
        foreach ($folders as $folder) {
            $folder["level"] = $level;
            $block["items"][] = $folder;
            $this->setItemsPrice($block, $folder["id"], $level);
        }

        $folders = $this->get_items_for_price($id);
        foreach ($folders as $folder) {
            $folder["level"] = $level;
            $block["items"][] = $folder;
        }

        // Читаем список вложенных элементов
        //			$items = $this->get_items($id, $record);
    }

    function maps($parameters) {
        $block = array();
        if ($this->page_info["virtuals"][0] == "") {
            $id = $this->catalog_id;
        } else {
            $id = $this->get_id_element_by_virtuals();
        }

        $this->its_root = (bool)($id == $this->catalog_id);

        $record = $this->get_record($id);

        if ($record) {
            $this->cms->updateWords($record["words"]);
            $block = $record;

            if ($record["type"] == "folder") {
                $block["tpl"] = "items";
            } else {
                $block["tpl"] = "item";
            }

            $block["url"] = $this->get_url_element($id, $record);

            if ($id == $this->catalog_id) {
                $block["parent_url"] = $this->catalog_path;
            } else {
                $block["parent_url"] = $this->get_url_element($record["parent_id"]);
            }
            $block["state"] = "done";

            $additional = $this->get_additional($id, $record["fieldset"], $record["type"]);
            $block["additional"] = $additional;
            $block["count_additional"] = count($additional);

            // Читаем список вложенных элементов
            $items = $this->get_items($id, $record);
            $block["items"] = $items;
            $block["count_items"] = count($items);

            // Читаем список прикрепленных к элементу изображений
            $gallery = $this->get_gallery($id);
            $block["gallery"] = $gallery;
            $block["count_gallery"] = count($gallery);

            $path = $this->get_path($id);
            foreach ($path as $path_id => $path_element) {
                if ($path_id != 0) {
                    $this->cms->page_info["path"][] = $path_element;
                }
            }

            // Добавляем ссылки навигации
            $this->setNavi($block);

            $this->increase_num_views($id);

            if ($record["parent_id"] != 0) {
                // Начинаем формирование данных для шаблона
                $this->page_info["title"] = $record["title"];
                if (!empty($record["meta_title"])) {
                    $this->page_info["meta_title"] = $record["meta_title"];
                }
                if ($record["meta_keywords"] != '')
                    $this->page_info["structure"]["keywords"] = $record["meta_keywords"];
                if ($record["meta_description"] != '')
                    $this->page_info["structure"]["description"] = $record["meta_description"];
            }

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "item"
            );
        }
        //		die($this->catalog_id);

        // Возвращаем результат
        return $block;
    }

    // сравнение
    function compare($parameters) {

        $block = array();

        if (isset($_GET["del"]) && isset($_SESSION["compare"][ (int)$_GET["del"] ])) {
            $this->cms->vars_falseget['catalog'] = 'true';
            unset($_SESSION["compare"][ (int)$_GET["del"] ]);
            header("Location: " . $this->page_info["request_url"]);
            die();
        }

        $block["compare"] = array();
        $block["count_compare"] = 0;

        if (isset($_SESSION["compare"])) {
            $block["compare"] = $_SESSION["compare"];
            $block["count_compare"] = count($block["compare"]);
        }

        if ($block["count_compare"] > 0) {
            $block["fields"] = array();
            $sql = "SELECT * FROM pm_catalog_fieldsets_data WHERE fieldset='azbuka' ORDER BY ord DESC";
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                while ($fields = $result->FetchRow()) {
                    $block["fields"][ $fields["id_text"] ] = '';
                }
            }

            // Добавляем флаг работы каталога в режиме интернет-магазина
            $block["shop_flag"] = $this->shop_flag;
            // Формируем ссылку на корзину покупок
            if ($this->shop_flag) {
                //$block["basket_url"] = $this->catalog_path."basket/";
                $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
            }
            // Устанавливаем статус выполнения запроса
            $block["state"] = "done";

            foreach ($block["compare"] as $id => $v) {
                // Читаем информацию об элементе
                $record = $this->get_record($id);
                $record["url"] = $this->get_url_element($id, $record);

                // Читаем список дополнительных полей элемента каталога
                $additional = $this->get_additional($id, $record["fieldset"], $record["type"]);

                foreach ($additional as $key_field => $vl) {
                    $block["fields"][ $key_field ] = array("title" => $vl["field"], "type" => $vl["type"]);
                }

                $record["additional"] = $additional;
                $record["count_additional"] = count($additional);

                $block["items"][] = $record;
            }
            $block["count_items"] = count($block["items"]);

            foreach ($block["fields"] as $key_field => $ttl) {
                if (empty($ttl)) {
                    unset($block["fields"][ $key_field ]);
                }
            }

            $block["count_fields"] = count($block["fields"]);

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "item"
            );
        }

        // Возвращаем результат
        return $block;
    }

    function index($parameters) {

        $block = array();
        //		die($this->catalog_path);
        $block["shop_flag"] = $this->shop_flag;
        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
            $block["catalog_path"] = $this->catalog_path;

        }
        //		$this->basket_add();
        $block["state"] = "done";
        $block["items"] = $this->get_items_for_filter(8);
        $block["count_items"] = count($block["items"]);

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //Get The Main Catalog Id
        $MainCatId = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='0' and type='folder' LIMIT 1;";
        $MainCatId = $this->dbc->Execute($MainCatId);
        if ($MainCatId && $MainCatId->RecordCount() > 0) {
            $MainCatId = $MainCatId->FetchRow();
            $MainCatId = $MainCatId['id'];
        } else {
            $MainCatId = false;
        }
        $CatalogFolders = Array();
        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $MainCatId . "' and type='folder';";
        $sql = $this->dbc->Execute($sql);
        if ($sql && $sql->RecordCount() > 0) {
            while ($a = $sql->FetchRow()) {

                //$a["fieldset"];


//
//                    foreach ($additional as $key => $value) {
//
//                        $sql34 = "SELECT options FROM pm_catalog_fieldsets_data WHERE title='" . $value['field'] . "' and fieldset='" . $a['fieldset'] . "';";
//                        $sql34 = $this->dbc->Execute($sql34);
//                        $namedb = $sql34->FetchRow();
//                        $sql66 = "SELECT title_ru FROM pm_catalog_select_" . $namedb['options'] . " WHERE enabled=1;";
//                        $sql66 = $this->dbc->Execute($sql66);
//                        while ($model = $sql66->FetchRow()) {
//                            $additional[$key]['list'][] = $model['title_ru'];
//                        }
//
//                    }
//
//                    $a["additional"] = $additional;
//                    $a["count_additional"] = count($additional);
//
//                    $items = $this->get_items($a['id'], $a, 4);

//                $a['for_filters'] = $addit;

                $CatalogFolders[] = $a;
            }
        }

        for ($i = 0; $i < count($CatalogFolders); $i++) {
            $SubCats = Array();
            $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $CatalogFolders[ $i ]['id'] . "' and type='folder';";
            $sql = $this->dbc->Execute($sql);
            if ($sql && $sql->RecordCount() > 0) {
                while ($a = $sql->FetchRow()) {
                    $SubCats[] = $a;
                }
            }
            $CatalogFolders[ $i ]['SubCategories'] = $SubCats;
            $CatalogFolders[ $i ]['SubCategoriesCount'] = count($SubCats);
        }

        $block["CatalogFolders"] = $CatalogFolders;
        $block["CatalogFoldersCount"] = count($CatalogFolders);

        //echo '<pre>'; print_r($CatalogFolders); echo '</pre>'; die();

        return $block;
    }

    function lenta($parameters) {
        $block = array();
        $NewsItems = Array();
        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE enabled='1' and news='1' and type='item';";
        $sql = $this->dbc->Execute($sql);
        if ($sql && $sql->RecordCount() > 0) {
            while ($a = $sql->FetchRow()) {
                $a["url"] = $this->get_url_element($a["id"], $a);
                $a["additional"] = $this->get_additional($a["id"], $a["fieldset"], "item");
                $a = $this->apply_current_language($a);
                $a["price"] = $this->get_price_currency($a["price"]);
                $this->check_isset_image($a);
                $NewsItems[] = $a;
            }
        }
        $block['NewsItems'] = $NewsItems;
        $block['NewsItemsCount'] = count($NewsItems);

        //echo '<pre>'; print_r($block); echo '</pre>'; die();
        return $block;
    }

    function side($parameters) {
        $block = array();
        //		die($this->catalog_path);
        $block["shop_flag"] = $this->shop_flag;
        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
        }
        //		$this->basket_add();
        $block["state"] = "done";
        $block["items"] = $this->get_items_for_filter(2);
        $block["count_items"] = count($block["items"]);

        Unset($block["items"]);

        //Get The Main Catalog Id
        $MainCatId = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='0' and type='folder' LIMIT 1;";
        $MainCatId = $this->dbc->Execute($MainCatId);
        if ($MainCatId && $MainCatId->RecordCount() > 0) {
            $MainCatId = $MainCatId->FetchRow();
            $MainCatId = $MainCatId['id'];
        } else {
            $MainCatId = false;
        }

        //Первое вхождение
        $CatalogFolderOne = Array();
        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $MainCatId . "' and type='folder';";
        $sql = $this->dbc->Execute($sql);
        if ($sql && $sql->RecordCount() > 0) {
            while ($a = $sql->FetchRow()) {
                $CatalogFolderOne[] = $a;
            }
        }

        //Второе вхождение
        //$CatalogFolderOne['NextFolders']
        for ($i = 0; $i < count($CatalogFolderOne); $i++) {
            $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $CatalogFolderOne[ $i ]['id'] . "' and type='folder';";
            $sql = $this->dbc->Execute($sql);
            if ($sql && $sql->RecordCount() > 0) {
                $CatalogFolderOne[ $i ]['NextFolders'] = Array();
                $CatalogFolderOne[ $i ]['NextFoldersCount'] = 0;
                while ($a = $sql->FetchRow()) {
                    $a['NextFolders'] = Array();
                    //Третье вхождение
                    //----------------------------------------------------------------------------
                    $sql2 = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE parent_id='" . $a['id'] . "' and type='folder';";
                    $sql2 = $this->dbc->Execute($sql2);
                    if ($sql2 && $sql2->RecordCount() > 0) {
                        while ($b = $sql2->FetchRow()) {
                            $a['NextFolders'][] = $b;
                        }
                        $a['NextFoldersCount'] = count($a['NextFolders']);
                    } else {
                        $a['NextFoldersCount'] = 0;
                    }
                    //----------------------------------------------------------------------------
                    $CatalogFolderOne[ $i ]['NextFolders'][] = $a;
                }
                $CatalogFolderOne[ $i ]['NextFoldersCount'] = count($CatalogFolderOne[ $i ]['NextFolders']);
            }
        }
        $block["items_new"] = $CatalogFolderOne;
        $block["items_new_count"] = count($CatalogFolderOne);

        //$block["test"] = 'VOVAN';
        //echo '<pre>'; print_r($block); echo '</pre>'; die();

        return $block;
    }

    function left($parameters) {
        $block = array();
        $block["shop_flag"] = $this->shop_flag;
        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
        }
        $this->basket_add();
        $block["state"] = "done";
        $block["items"] = $this->get_items_for_filter(2);
        $block["count_items"] = count($block["items"]);

        return $block;
    }

    function get_items_for_filter($limit = 6) {
        $items = array();

        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type='item' AND enabled=1 AND fieldset<>'pointtomap' ORDER BY RAND() LIMIT 0, " . $limit;
        //		die($sql);
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $record["url"] = $this->get_url_element($record["id"], $record);
                $record["additional"] = $this->get_additional($record["id"], $record["fieldset"], "item");
                $record = $this->apply_current_language($record);
                //$record["price"] = number_format($this->get_price_currency($record["price"]), 2);
                $record["price"] = $this->get_price_currency($record["price"]);
                $this->check_isset_image($record);
                $items[] = $record;
            }
        }

        // Возвращаем результат
        return $items;
    }

    function check_isset_image(&$item) {
        if (!empty($item["img_sm1"]) && !file_exists(ROOT_HTML_PATH . substr($item["img_sm1"], 1)))
            $item["img_sm1"] = '';
        if (!empty($item["img_sm"]) && !file_exists(ROOT_HTML_PATH . substr($item["img_sm"], 1)))
            $item["img_sm"] = '';
        if (!empty($item["img"]) && !file_exists(ROOT_HTML_PATH . substr($item["img"], 1)))
            $item["img"] = '';
    }

    function get_actions_items($limit = 9) {
        $items = array();

        $sql = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type='item' AND enabled=1 AND action_for_user>0 ORDER BY RAND() LIMIT 0, " . $limit;
        //		die($sql);
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $record["url"] = $this->get_url_element($record["id"], $record);
                $record["additional"] = $this->get_additional($record["id"], $record["fieldset"], "item");
                $record = $this->apply_current_language($record);
                $record["price"] = $this->get_price_currency($record["price"]);
                $items[] = $record;
            }
        }

        // Возвращаем результат
        return $items;
    }

    function actions($parameters) {

        $block = array();

        // Добавляем флаг работы каталога в режиме интернет-магазина
        $block["shop_flag"] = $this->shop_flag;

        // Формируем ссылку на корзину покупок
        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
        }

        $this->basket_add();

        // Устанавливаем статус выполнения запроса
        $block["state"] = "done";

        // Читаем список акций по коэффициенту
        $block["action_items"] = $this->get_actions_items(100);
        $block["count_action_items"] = count($block["action_items"]);

        // Возвращаем результат
        return $block;
    }

    function you_win($parameters) {

        $block = array();

        // Добавляем флаг работы каталога в режиме интернет-магазина
        $block["shop_flag"] = $this->shop_flag;

        // Формируем ссылку на корзину покупок
        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
        }

        $this->basket_add();

        // Устанавливаем статус выполнения запроса
        $block["state"] = "done";

        $block["you_win_action"] = 0;
        if ($this->page_info["user_data"]["unique"]["catalog_id"] > 0) {
            $record = $this->get_record($this->page_info["user_data"]["unique"]["catalog_id"]);
            if (isset($record["id"])) {
                $record["additional"] = $this->get_additional($record["id"], $record["fieldset"], "item");
                $record["count_additional"] = count($record["additional"]);
                $record["item_id"] = $record["id"];

                $gallery = $this->get_gallery($record["id"]);
                $record["gallery"] = $gallery;
                $record["count_gallery"] = count($gallery);

                $block["you_win"] = $record;
                $block["you_win_action"] = 1;
            }
        }

        // Возвращаем результат
        return $block;
    }

    //............................................................................. Корзина покупок

    // Функция печати содержимого корзины
    function basket($parameters) {
        //die('Test!');
        $block = array();

        // Проверяем, существует ли у посетителя корзина покупок
        if (!empty($_COOKIE["basket"])) {
            $basket_id = (int)$_COOKIE["basket"];
        } else {
            $basket_id = 0;
        }

        // Читаем содержимое корзины
        $basket = $this->get_basket($basket_id);

        // Печатаем содержимое корзины
        if (!empty($basket)) {

            // Рассчитываем ссылку на корзину и на офомление заказа
            //$basket_url = $this->catalog_path."basket/";
            $basket_url = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
            $order_url = $this->catalog_path . "order/";

            // Рассчитываем общее количество товаров в корзине и их общую стоимость
            $total_quantity = 0;
            $total_cost = 0;
            foreach ($basket as $item_id => $item) {
                if (!isset($item["its_action"]) || isset($item["its_action"]) && $item["its_action"] != 1) {
                    $total_quantity += $item["quantity"];
                    $total_cost += (float)$item["cost"];
                }
            };

            /////////////////////////////////////////////////////////////////////////////////////////////////
            // Если модуль зависимые товары включен то делаем перещет цен
            /////////////////////////////////////////////////////////////////////////////////////////////////
            if ($this->depended_products == 1) {
                //Текущая валюта
                $_CURRENCY = (float)$_SESSION["all_currency"][ $_SESSION["currency"] ]['ratio'];

                //Делаем выборку зависимых товаров
                $depend_products_array = Array();
                $sql = "SELECT * FROM pm_dependent_products ORDER BY Id ASC;";
                $depend_products_data = $this->dbc->Execute($sql);
                if ($depend_products_data->RecordCount() > 0) {
                    while ($item = $depend_products_data->FetchRow()) {
                        $depend_products_array[] = $item;
                    }
                }

                $DependProductRules = Array();
                //Проходимся по каждому зависимому товару
                foreach ($depend_products_array as $depend_product) {
                    //Проходимся по каждому элементу корзины
                    $ItemsByRule = Array();
                    //echo '<pre>'; print_r($basket); echo '</pre>'; die();
                    //echo '<pre>'; print_r($_SESSION["all_currency"]); echo '</pre>'; die();
                    //echo '<pre>'; print_r($_SESSION["currency"]); echo '</pre>'; die();
                    //echo '<pre>'; print_r($_SESSION["all_currency"][$_SESSION["currency"]]); echo '</pre>'; die();
                    foreach ($basket as $item) {
                        if (
                            ($depend_product['TovarId1'] == $item['item_id']) or
                            ($depend_product['TovarId2'] == $item['item_id']) or
                            ($depend_product['TovarId3'] == $item['item_id']) or
                            ($depend_product['TovarId4'] == $item['item_id']) or
                            ($depend_product['TovarId5'] == $item['item_id'])
                        ) {
                            if ($depend_product['TovarId1'] == $item['id']) {
                                $item['depend_section'] == 1;
                            } else if ($depend_product['TovarId2'] == $item['item_id']) {
                                $item['depend_section'] == 2;
                            } else if ($depend_product['TovarId3'] == $item['item_id']) {
                                $item['depend_section'] == 3;
                            } else if ($depend_product['TovarId4'] == $item['item_id']) {
                                $item['depend_section'] == 4;
                            } else if ($depend_product['TovarId5'] == $item['item_id']) {
                                $item['depend_section'] == 5;
                            }
                            $item['depend_price'] = ((float)$depend_product['Price'] * $_CURRENCY);
                            $ItemsByRule[] = $item;
                        }
                    }

                    if (count($ItemsByRule) >= 1) {
                        $RuleItems = Array();
                        foreach ($ItemsByRule as $TmpItm) {
                            $RuleItems[] = $TmpItm;
                        }
                        $DependProductRules[] = $RuleItems;
                        Unset($RuleItems);
                    }
                    Unset($ItemsByRule);
                }

                //echo '<pre>'; print_r($DependProductRules); echo '</pre>'; die();

                //Щитаем зависимые товары
                //Отнимаем от текущей сумы перещитаную сумму
                $MinusThis = 0;
                foreach ($DependProductRules as $DependProductRule) {
                    $tmp_dsum = (float)$DependProductRule[0]['depend_price'];
                    $tmp_sum = 0;
                    foreach ($DependProductRule as $aa) {
                        $tmp_sum += (float)$aa['price'];
                    }
                    $total_cost -= $tmp_sum;
                    $total_cost += $tmp_dsum;
                }

            }

            // Формируем данные для шаблона
            $block = array(
                "state"          => "done",
                "items"          => $basket,
                "count_items"    => count($basket),
                "basket_url"     => $basket_url,
                "order_url"      => $order_url,
                "total_quantity" => $total_quantity,
                "total_cost"     => number_format($total_cost, 2, ".", ""),
                "tpl"            => "basket"
            );

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "basket"
            );
        }

        // Возвращаем результат
        return $block;
    }

    // Функция добавления товара в корзину
    function basket_add() {

        if (!empty($_GET["basket"])) {
            $this->cms->vars_falseget['catalog'] = 'true';
            //			echo "<Pre>"; print_r($_GET); echo "</pre>";
            //			die();

            // Проверяем, существует ли у посетителя корзина покупок
            if (!empty($_COOKIE["basket"])) {
                $basket_id = (int)$_COOKIE["basket"];
            } else {

                // Формируем уникальный идентификатор корзины
                $basket_id = $this->get_unique_basket_id();
            }

            // Читаем данные из формы
            $basket = $_GET["basket"];

            // Находим идентификатор добавляемого товара
            if (!empty($basket["item_id"])) {
                $item_id = $basket["item_id"];
            } else {
                $item_id = "";
            }

            // Находим заказанное количество товаров
            if (!empty($basket["quantity"])) {
                $quantity = (int)$basket["quantity"];
            } else {
                $quantity = 1;
            }

            $additional = "''";
            if (isset($basket["additional"])) {
                $additionalArr = array();
                foreach ($basket["additional"] as $key => $val) {
                    $additionalArr[] = $key . ":" . $val;
                }
                $additional = "'" . implode("|", $additionalArr) . "'";
            }
            $sql = "SELECT id FROM " . $this->cms->tables["catalog_baskets"] . " WHERE basket_id='" . $basket_id . "' AND item_id='" . $item_id . "' AND additional=" . $additional . "";
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                $basket_record = $result->FetchRow();
                $sql = "UPDATE " . $this->cms->tables["catalog_baskets"] . " SET quantity=(quantity+" . $quantity . "), created=NOW() WHERE id='" . $basket_record["id"] . "'";
                $result = $this->dbc->Execute($sql);
            } else {
                // Сохраняем выбранный товар в корзину
                $insert = array(
                    "basket_id"  => $basket_id,
                    "item_id"    => $item_id,
                    "quantity"   => $quantity,
                    "additional" => $additional,
                    "created"    => "NOW()"
                );
                $query = "INSERT INTO " . $this->cms->tables["catalog_baskets"] . " (" . implode(", ", array_keys($insert)) . ") VALUES (" . implode(", ", $insert) . ")";
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                    return false;
                }
            }

            // Обновляем временную метку у всех товаров в корзине
            $query = "UPDATE " . $this->cms->tables["catalog_baskets"] . " SET created=now() WHERE basket_id=" . $basket_id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }

            // Сохраняем в cookie уникальный идентификатор пользователя для отслеживания корзины покупок
            if (empty($_COOKIE["basket"])) {
                setcookie("basket", $basket_id, time() + 60 * 60 * 24 * 30, "/", "");
            }

            // Вызываем функцию очистки устаревших корзин
            $this->clear_old_baskets();

        }

        // Осуществляем редирект на просмотр корзины
        //		header("Location: ".$this->catalog_path."basket/");
    }

    // Функция обновления корзины
    function basket_edit($parameters) {

        if (!empty($_POST["basket"])) {

            // Проверяем, существует ли у посетителя корзина покупок
            if (!empty($_COOKIE["basket"])) {
                $basket_id = (int)$_COOKIE["basket"];
            } else {

                // Формируем уникальный идентификатор корзины
                $basket_id = $this->get_unique_basket_id();
            }

            // Читаем данные из формы
            $basket = $_POST["basket"];

            if (!empty($basket) && is_array($basket)) {

                // Обновляем информацию о каждом товаре в корзине
                foreach ($basket as $id => $item) {

                    if ($item["quantity"] == 0 || (isset($item["delete"]) && $item["delete"] == "yes")) {

                        // Удаляем товар из корзины
                        $query = "DELETE FROM " . $this->cms->tables["catalog_baskets"] . " WHERE id=" . $id;

                    } else {

                        // Обновляем количество заказанного товара
                        $query = "UPDATE " . $this->cms->tables["catalog_baskets"] . " SET quantity=" . (int)$item["quantity"] . " WHERE id=" . $id;
                    }

                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                        return false;
                    }
                }
            }
        }

        // Осуществляем редирект на просмотр корзины
        //header("Location: ".$this->catalog_path."basket/");
        header("Location: " . "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/");
    }

    // Функция печати содержимого информации о корзине
    function basket_info($parameters) {
        //die('Test!');
        $block = array();

        // Проверяем, существует ли у посетителя корзина покупок
        if (!empty($_COOKIE["basket"])) {
            $basket_id = (int)$_COOKIE["basket"];
        } else {
            $basket_id = 0;
        }

        if ($this->shop_flag) {
            //$block["basket_url"] = $this->catalog_path."basket/";
            $block["basket_url"] = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";
        }

        // Читаем содержимое корзины
        $basket = $this->get_basket($basket_id);

        // Рассчитываем ссылку на корзину
        //$basket_url = $this->catalog_path."basket/";
        $basket_url = "http://" . $_SERVER['SERVER_NAME'] . "/catalog/basket/";

        // Печатаем информацию о содержимом корзины
        if (!empty($basket)) {

            // Рассчитываем общее количество товаров в корзине и их общую стоимость
            $total_quantity = 0;
            $total_cost = 0;
            foreach ($basket as $item_id => $item) {
                $total_quantity += $item["quantity"];
                $total_cost += $item["cost"];
            };

            // Формируем данные для шаблона
            $block = array(
                "state"          => "done",
                "basket_url"     => $basket_url,
                "total_quantity" => $total_quantity,
                "total_cost"     => number_format($total_cost, 2, ".", ""),
                "tpl"            => "info"
            );

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "info"
            );
        }

        // Возвращаем результат
        return $block;
    }

    //........................................................................... Оформление заказа

    // Функция добавления нового заказа
    function order_add($parameters) {
        $block = array();

        // Проверяем, существует ли у посетителя корзина покупок
        if (!empty($_COOKIE["basket"])) {
            $basket_id = (int)$_COOKIE["basket"];
        } else {
            $basket_id = 0;
        }

        // Читаем содержимое корзины
        $basket = $this->get_basket($basket_id);

        //		echo "<pre>"; print_r($this->page_info["user_data"]["unique"]); echo "</pre>";
        //		echo "<pre>"; print_r($basket); echo "</pre>"; die();

        // Печатаем содержимое корзины
        if (!empty($basket)) {

            // Рассчитываем ссылку на форму заказа
            $order_url = $this->catalog_path . "order/";

            // Начинаем формировать данные для шаблона
            $block["order_url"] = $order_url;
            $block["tpl"] = "order";
            $block["state"] = "progress";
            $block['protect_img'] = ROOT_URL . 'protect_image.php';

            if (!empty($_POST["order"])) {

                // Читаем информацию из формы
                $order = $_POST["order"];
                $this->prepareVar($order);
                $this->checkForm($order, $block);

                if ($block['errors']['countErrors'] == 0) {

                    $total_cost = 0;
                    $total_quantity = 0;
                    foreach ($basket as $item_id => $item) {
                        if (!isset($item["its_action"]) || isset($item["its_action"]) && $item["its_action"] != 1) {
                            $total_quantity += $item["quantity"];
                            $total_cost += (float)$item["cost"];
                        }
                    };

                    // Добавляем контактную информацию в базу данных
                    $insert = array(
                        "last_name"      => "'" . addslashes(trim($order["last_name"])) . "'",
                        "name"           => "'" . addslashes(trim($order["name"])) . "'",
                        "patr"           => "'" . addslashes(trim($order["patr"])) . "'",
                        "country"        => "'" . addslashes(trim($order["country"])) . "'",
                        "city"           => "'" . addslashes(trim($order["city"])) . "'",
                        "address"        => "'" . addslashes(trim($order["address"])) . "'",
                        "phone"          => "'" . addslashes(trim($order["phone"])) . "'",
                        "email"          => "'" . addslashes(trim($order["email"])) . "'",

                        "delivery"       => "'" . (int)$order["delivery"] . "'",
                        "type_pay"       => "'" . (int)$order["type_pay"] . "'",
                        //						"action_code"	=> "'".addslashes(trim($order["action_code"]))."'",

                        "comments"       => "'" . addslashes(trim($order["comments"])) . "'",
                        "created"        => "NOW()",
                        "uid"            => $this->page_info["user_data"]["uid"],

                        "price"          => "'" . (float)$total_cost . "'",
                        "price_delivery" => "'" . (float)$this->get_price_currency($this->delivery[ (int)$order["delivery"] ]["price"]) . "'",
                        "total_quantity" => "'" . (int)$total_quantity . "'",
                        "currency"       => "'" . $_SESSION["currency"] . "'",

                        "active"         => 1
                    );
                    if (!empty($order["type_face"])) {
                        $price_BN = (float)$total_cost;
                        if ((int)$order["type_face"] == 1) {
                            $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                        } else {
                            $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                        }
                    } else {
                        $price_BN = (float)$total_cost;
                    }
                    $insert["price"] = $price_BN;

                    $block["order"]["price_wm"] = (float)$total_cost;

                    if (!empty($order["webmoney"])) {
                        $insert["webmoney"] = "'" . addslashes(trim($order["webmoney"])) . "'";
                        if ($block["order"]["webmoney"] == "u") {
                            $block["order"]["price_wm"] = $block["order"]["price_wm"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                        } else {
                            $block["order"]["price_wm"] = $block["order"]["price_wm"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][3]["ratio"];
                        }
                        $block["order"]["price_wm"] = number_format($block["order"]["price_wm"], 1, ".", "");
                    }
                    if (!empty($order["type_face"])) {
                        $insert["type_face"] = "'" . (int)$order["type_face"] . "'";
                        $block["order"]["price_wm"] = $block["order"]["price_wm"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                        $block["order"]["price_wm"] = number_format($block["order"]["price_wm"], 1, ".", "");
                    }
                    $insert["items"] = "";
                    $counter = 1;
                    foreach ($basket as $item_basket) {
                        $insert["items"] .= "<div class=\"ttl_basket" . (($counter++ % 2) ? "" : "2") . "\">" . $item_basket["title"] . (($item_basket["code"] != '') ? ", код товара: " . $item_basket["code"] : "") . "</div>";
                        if (isset($item_basket["its_action"]) && $item_basket["its_action"] == 1) {
                            $insert["items"] .= "<div>Товар по акции. Код акции: " . $this->page_info["user_data"]["unique"]["code"] . "</div>";
                        }
                        if (isset($item_basket["count_additionalArr"]) && $item_basket["count_additionalArr"] > 0) {
                            foreach ($item_basket["additionalArr"] as $item_additional) {
                                $insert["items"] .= "<b>" . $item_additional["field"] . "</b>: " . $item_additional["title"] . " (" . $item_additional["price"] . ")<br>";
                            }
                        }
                    }
                    $insert["items"] = "'" . $insert["items"] . "'";

                    $xls_number = 0;
                    if ((int)$order["type_pay"] == 3) {
                        $sql = "SELECT xls_number FROM " . $this->cms->tables["catalog_orders"] . " WHERE type_pay='" . (int)$order["type_pay"] . "' AND type_face='" . (int)$order["type_face"] . "' ORDER BY xls_number DESC LIMIT 0,1";
                        $result = $this->dbc->Execute($sql);
                        if ($result && $result->RecordCount() > 0) {
                            $item = $result->FetchRow();
                            $xls_number = $item["xls_number"] + 1;
                        }
                        $insert["xls_number"] = "'" . $xls_number . "'";
                    }

                    $query = "INSERT INTO " . $this->cms->tables["catalog_orders"] . " (" . implode(", ", array_keys($insert)) . ") VALUES (" . implode(", ", $insert) . ")";
                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                        return false;
                    }

                    // Читаем идентификатор последнего добавленного заказа
                    $order_id = $this->dbc->insert_id();
                    $block["order_id"] = $order_id;
                    //					echo "<pre>"; print_r($item); echo "</pre>";

                    // Переносим товары из корзины в заказ пользователя
                    $item_ids = array();
                    foreach ($basket as $item_id => $item) {

                        // Добавляем очередной товар в заказ
                        $insert = array(
                            "order_id"   => $order_id,
                            "item_id"    => $item["item_id"],
                            "title"      => "'" . addslashes(trim($item["title"])) . "'",
                            "quantity"   => $item["quantity"],
                            "price"      => $item["price"],
                            "additional" => "'" . addslashes(trim($item["additional"])) . "'"
                        );
                        $query = "INSERT INTO " . $this->cms->tables["catalog_orders_items"] . " (" . implode(", ", array_keys($insert)) . ") VALUES (" . implode(", ", $insert) . ")";
                        $result = $this->dbc->Execute($query);
                        if (!$result) {
                            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                            return false;
                        }

                        // Обновляем счетчик заказов товара
                        $query = "UPDATE " . $this->cms->tables["catalog"] . " SET num_orders=num_orders+" . $item["quantity"] . " WHERE id=" . $item["item_id"];
                        $result = $this->dbc->Execute($query);
                        if (!$result) {
                            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                            return false;
                        }
                    };

                    if ((int)$order["type_pay"] == 3) {
                        $targetFile = $this->get_order_xls($order, $basket, $order_id, $xls_number);
                        $link_file = str_replace(ROOT_HTML_PATH, "/", $targetFile);
                        $query = "UPDATE " . $this->cms->tables["catalog_orders"] . " SET link_file_order='" . addslashes(trim($link_file)) . "' WHERE id='" . $order_id . "'";
                        $this->dbc->Execute($query);
                    }

                    // Закрываем акционный код
                    $query = "UPDATE " . $this->cms->tables["user_code"] . " SET closed=1 WHERE code='" . $this->page_info["user_data"]["unique"]["code"] . "'";
                    $result = $this->dbc->Execute($query);
                    // Очищаем корзину с товарами
                    $query = "DELETE FROM " . $this->cms->tables["catalog_baskets"] . " WHERE basket_id=" . $basket_id;
                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                        return false;
                    }

                    // Удаляем cookie с компьютера
                    setcookie("basket", "", 0, "/");
                    /**/

                    // Подключаем библиотеки для отправки сообщения по почте
                    require_once(cms_LIB_PATH . 'mail_event.class.php');
                    require_once(cms_LIB_PATH . 'email_message.class.php');
                    require_once(cms_LIB_PATH . 'strcom_parser.class.php');
                    $mail_event = new mail_event($this->plugin['path'] . 'tpls/' . $block["tpl"] . '/mail/', '.tpl');

                    // Формируем таблицу с описанием заказа
                    $basket_table = "<table class=\"basket\">\n";
                    $basket_table .= "<tr>\n";
                    $basket_table .= "<th>#</th>\n";
                    $basket_table .= "<th>Название товара</th>\n";
                    $basket_table .= "<th>Количество</th>\n";
                    $basket_table .= "<th>Цена, " . $_SESSION["all_currency"][ $_SESSION["currency"] ]["title"] . "</th>\n";
                    $basket_table .= "<th>Стоимость, " . $_SESSION["all_currency"][ $_SESSION["currency"] ]["title"] . "</th>\n";
                    $basket_table .= "</tr>\n";

                    // Формируем строки таблицы, попутно считая общее количество и сумму
                    $total_quantity = 0;
                    $total_cost = 0;
                    $counter = 1;
                    foreach ($basket as $item_id => $item) {
                        $basket_table .= "<tr>\n";
                        $basket_table .= "<td>$counter</td>\n";
                        $basket_table .= "<td>";
                        $basket_table .= htmlspecialchars($item["title"]);
                        if (isset($item["count_additionalArr"]) && $item["count_additionalArr"] > 0) {
                            foreach ($item["additionalArr"] as $item_additional) {
                                if (!empty($order["type_face"])) {
                                    $price_BN = $item_additional["price"];
                                    if ((int)$order["type_face"] == 1) {
                                        $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                                    } else {
                                        $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                                    }
                                } else {
                                    $price_BN = $item_additional["price"];
                                }
                                $basket_table .= "<b>" . $item_additional["field"] . "</b>: " . $item_additional["title"] . " (" . $price_BN . ")<br>";
                            }
                        }
                        $basket_table .= "</td>\n";
                        $basket_table .= "<td>" . $item["quantity"] . "</td>\n";
                        if (!empty($order["type_face"])) {
                            $price_BN = $item["price"];
                            if ((int)$order["type_face"] == 1) {
                                $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                            } else {
                                $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                            }
                            $basket_table .= "<td>" . number_format($price_BN, 2, ".", "") . "</td>\n";
                        } else {
                            $basket_table .= "<td>" . number_format($item["price"], 2, ".", "") . "</td>\n";
                        }
                        if (!isset($item["its_action"]) || isset($item["its_action"]) && $item["its_action"] != 1) {
                            if (!empty($order["type_face"])) {
                                $price_BN = $item["cost"];
                                if ((int)$order["type_face"] == 1) {
                                    $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                                } else {
                                    $price_BN = $price_BN / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                                }
                                $basket_table .= "<td>" . number_format($price_BN, 2, ".", "") . "</td>\n";
                            } else {
                                $price_BN = $item["cost"];
                                $basket_table .= "<td>" . number_format($item["cost"], 2, ".", "") . "</td>\n";
                            }
                        } else {
                            $price_BN = 0;
                            $basket_table .= "<td>0</td>\n";
                        }
                        $basket_table .= "</tr>\n";
                        $counter++;

                        if (!isset($item["its_action"]) || isset($item["its_action"]) && $item["its_action"] != 1) {
                            $total_quantity += $item["quantity"];
                            //							$total_cost += $item["cost"];
                            $total_cost += $price_BN;
                        }

                    }
                    $basket_table .= "</table>\n";

                    $form_info = $order;

                    if ((int)$order["type_pay"] == 3) {
                        $form_info["file"] = $targetFile;
                    }

                    $form_info["delivery_title"] = $this->delivery[ $order["delivery"] ]["title"];
                    if ($form_info["type_pay"] == 1) { //webmoney
                        $form_info["type_pay_title"] = "Web money";
                    } elseif ($form_info["type_pay"] == 2) { //Наличными по факту получения
                        $form_info["type_pay_title"] = "Наличными";
                    } else { //Безналичный рассчет
                        $form_info["type_pay_title"] = "Безналичный рассчет";
                    }
                    $form_info['currency_title'] = $_SESSION["all_currency"][ $_SESSION["currency"] ]["title"];

                    // Формируем массив с параметрами заказа для отправки письма по почте
                    $form_info['site'] = str_replace("http://", "", $this->cms->page_info['root_url']);
                    $form_info['site'] = str_replace("/", "", $form_info['site']);
                    $form_info['site_url'] = $this->cms->page_info['root_url'];

                    $form_info['from'] = $this->admin_email;
                    $form_info['order_id'] = $order_id;
                    //					$form_info['name'] = $order["name"];
                    //					$form_info['email'] = $order["email"];
                    //					$form_info['phone'] = $order["phone"];
                    //					$form_info['address'] = $order["address"];
                    //					$form_info['comments'] = nl2br($order["comments"]);
                    $form_info['basket_table'] = $basket_table;
                    $form_info['total_quantity'] = $total_quantity;
                    $form_info['total_cost'] = number_format($total_cost, 2, ".", "");

                    // Отсылаем письмо покупателю
                    $form_info['to'] = $order["email"];
                    $mail_event->event('user', $form_info);

                    // Отсылаем письмо администратору
                    $form_info['to'] = $this->admin_email;
                    $mail_event->event('admin', $form_info);

                    // Устанавливаем флаг того, что заказ обработан успешно
                    $block["state"] = "done";
                }
            } else {
                $block["order"] = $this->page_info["user_data"];
            }

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "order"
            );
        }
        //		die();

        // Возвращаем результат
        return $block;
    }

    // Функция добавления нового заказа
    function preorder($parameters) {
        //die('Test!');

        $block = array();

        $id = 0;
        if (isset($this->GETVar["cid"])) {
            $id = (int)$this->GETVar["cid"];
        }

        if ($id != 0) {
            $ElementExist = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type='item' and id='" . addslashes($id) . "' LIMIT 1;";
            $ElementExist = $this->dbc->Execute($ElementExist);
            if ($ElementExist && $ElementExist->RecordCount() > 0) {
                $ElementExist = $ElementExist->FetchRow();
                $ElementExist = $ElementExist['id'];
            } else {
                $ElementExist = 0;
            }
            $id = $ElementExist;
            //die(print_r($ElementExist, true));
        }

        // Печатаем содержимое корзины
        if ($id != 0) {

            // Читаем информацию об элементе
            $record = $this->get_record($id);
            $this->page_info["title"] = "Предзаказ " . $record["title"];
            $block = $record;
            $block["state"] = "progress";
            $block['protect_img'] = ROOT_URL . 'protect_image.php';

            if (!empty($_POST["order"])) {

                // Читаем информацию из формы
                $order = $_POST["order"];
                $this->prepareVar($order);
                $this->checkFormPre($order, $block);

                if ($block['errors']['countErrors'] == 0) {

                    // Добавляем контактную информацию в базу данных
                    $insert = array(
                        "record_id" => "'" . addslashes(trim($record["id"])) . "'",
                        "last_name" => "'" . addslashes(trim($order["last_name"])) . "'",
                        "name"      => "'" . addslashes(trim($order["name"])) . "'",
                        "patr"      => "'" . addslashes(trim(@$order["patr"])) . "'",
                        "country"   => "'" . addslashes(trim($order["country"])) . "'",
                        "city"      => "'" . addslashes(trim($order["city"])) . "'",
                        "phone"     => "'" . addslashes(trim($order["phone"])) . "'",
                        "email"     => "'" . addslashes(trim($order["email"])) . "'",
                        "comments"  => "'" . addslashes(trim($order["comments"])) . "'",
                        "created"   => "NOW()",
                        "uid"       => $this->page_info["user_data"]["uid"],
                    );

                    $query = "INSERT INTO " . $this->cms->tables["catalog_preorders"] . " (" . implode(", ", array_keys($insert)) . ") VALUES (" . implode(", ", $insert) . ")";
                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                        return false;
                    }

                    // Читаем идентификатор последнего добавленного заказа
                    $order_id = $this->dbc->insert_id();

                    // Подключаем библиотеки для отправки сообщения по почте
                    require_once(cms_LIB_PATH . 'mail_event.class.php');
                    require_once(cms_LIB_PATH . 'email_message.class.php');
                    require_once(cms_LIB_PATH . 'strcom_parser.class.php');

                    if (!isset($block["tpl"])) {
                        $block["tpl"] = 'preorder';
                    }
                    $mail_event = new mail_event($this->plugin['path'] . 'tpls/' . $block["tpl"] . '/mail/', '.tpl');

                    // Формируем таблицу с описанием заказа
                    $basket_table = "Название товара: " . $record["title"];

                    $form_info = $order;

                    $form_info["title"] = $record["title"];

                    $form_info['currency_title'] = $_SESSION["all_currency"][ $_SESSION["currency"] ]["title"];

                    // Формируем массив с параметрами заказа для отправки письма по почте
                    $form_info['site'] = str_replace("http://", "", $this->cms->page_info['root_url']);
                    $form_info['site'] = str_replace("/", "", $form_info['site']);
                    $form_info['site_url'] = $this->cms->page_info['root_url'];

                    $form_info['from'] = $order["email"];
                    $form_info['basket_table'] = $basket_table;
                    $form_info['order_id'] = $order_id;

                    // Отсылаем письмо администратору
                    $EmailToArray = Array(
                        'info@electronom.com.ua',
                        'il@voliacable.com',
                        'vladimir@ukr-contact.com'
                    );

                    foreach ($EmailToArray as $EmailTo) {
                        $form_info['to'] = $EmailTo;
                        $mail_event->event('admin', $form_info);
                    }

                    // Устанавливаем флаг того, что заказ обработан успешно
                    $block["state"] = "done";
                }
            } else {
                $block["order"] = $this->page_info["user_data"];
            }

        } else {

            // Формируем данные для шаблона
            $block = array(
                "state" => "error",
                "error" => "not_found",
                "tpl"   => "order"
            );
        }

        // Возвращаем результат
        return $block;
    }

    //................................................... Функции чтения различных свойств элемента

    // Функция чтения информации об элементе
    function get_record($record_id) {

        $record = "";

        // Проверяем, что для показа разрешены все родительские элементы
        $path_enabled = $this->get_path_enabled($record_id);

        if ($path_enabled) {

            // Читаем информацию об элементе
            $query = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE enabled=1 AND id=' . $record_id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . "<br>Query: " . $query, E_USER_ERROR);

                return false;
            }
            if ($result->RecordCount() > 0) {

                // Читаем информацию об элементе
                $record = $result->FetchRow();

                // Накладываем текущий язык сайта на результаты запроса
                $record = $this->apply_current_language($record);

                $record["states"] = $record["state"];

                $record["price"] = $this->get_price_currency($record["price"]);
            }
        }

        return $record;
    }

    // Функция чтения дополнительной информации об элементе
    function get_additional($record_id, $fieldset, $type = "folder") {

        $additional = array();

        if ($fieldset != "empty") {

            // Формируем имя таблицы, хранящей элементы, описываемые данным набором полей
            $table_name = PREFIX_TABLE . "_catalog_table_" . $fieldset;

            // Читаем информацию об элементе
            $query = 'SELECT * FROM ' . $table_name . ' WHERE record_id=' . $record_id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }
            if ($result->RecordCount() == 0) {
                //$this->cms->int_set_message('top', 'Запрошенный элемент не найден'.$query, $this->plugin['name'], 'error');
                $sql = 'INSERT INTO ' . $table_name . ' SET record_id=' . $record_id;
                $result = $this->dbc->Execute($sql);

                return array();
            }

            // Читаем дополнительную информацию об элементе
            $additional = $result->FetchRow();

            // Удаляем ненужные поля
            unset($additional["id"]);
            unset($additional["record_id"]);

            // Накладываем текущий язык сайта на результаты запроса
            $additional = $this->apply_current_language($additional);

            // Выбираем нужные поля, согласно запрошенному типу
            $additional = $this->apply_type_filter($additional, $fieldset, $type);
            // Обрабатываем списки, заменяя идентификаторы реальными значениями
            $additional = $this->apply_select_values($additional, $fieldset);
            foreach ($additional as $key => $val) {
                if (empty($val["value"])) {
                    unset($additional[ $key ]);
                }
            }

        }

        //die();

        return $additional;
    }

    function get_additional_info($record_id, $fieldset) {
        $additional = array();
        if ($fieldset != "empty") {
            $table_name = PREFIX_TABLE . "_catalog_table_" . $fieldset;
            $query = 'SELECT * FROM ' . $table_name . ' WHERE record_id=' . $record_id;
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }
            if ($result->RecordCount() == 0) {
                //$this->cms->int_set_message('top', 'Запрошенный элемент не найден'.$query, $this->plugin['name'], 'error');
                $sql = 'INSERT INTO ' . $table_name . ' SET record_id=' . $record_id;
                $result = $this->dbc->Execute($sql);

                return array();
            }
            $additional = $result->FetchRow();
            //var_dump($additional); die("");

            // Удаляем ненужные поля
            unset($additional["id"]);
            unset($additional["record_id"]);

            // Накладываем текущий язык сайта на результаты запроса
            //$additional = $this->apply_current_language($additional);

            // Выбираем нужные поля, согласно запрошенному типу
            //$additional = $this->apply_type_filter($additional, $fieldset, $type);
            // Обрабатываем списки, заменяя идентификаторы реальными значениями

            //$additional = $this->apply_select_values($additional, $fieldset);

            $new_additional = Array();
            $additional = $this->fieldsets[ $fieldset ];
            foreach ($additional as $Key => $Value) {

                if ($Value['type'] == 'select') {
                    $new_additional[ $Key ] = $Value;

                    if ($new_additional[ $Key ]['type'] == 'select') {
                        //SELECT
                        $new_additional[ $Key ]['all_list'] = $this->selects[ $new_additional[ $Key ]['options'] ];
                    } elseif ($new_additional[ $Key ]['type'] == 'multiple') {
                        //MULTIPLE
                        $new_additional[ $Key ]['all_list'] = $this->selects[ $new_additional[ $Key ]['options'] ];
                    }
                }

                //Delete Some Fields
                /*Unset($new_additional[$Key]['id']);
    Unset($new_additional[$Key]['id_text']);
    Unset($new_additional[$Key]['options']);
    Unset($new_additional[$Key]['show_list_flag']);
    Unset($new_additional[$Key]['show_view_flag']);
    Unset($new_additional[$Key]['ord']);
    Unset($new_additional[$Key]['fieldset']);*/
            }

            $additional = $new_additional;

            //echo '<pre>'; print_r($additional); echo '</pre>';

            //$additional = $this->selects;

            //foreach($additional as $key => $val) { if(empty($val["value"])) { unset($additional[$key]); } }
        }

        //var_dump($additional); die("");
        return $additional;
    }

    // Функция чтения списка вложенных папок
    function get_folders($record_id) {

        $folders = array();

        // Читаем список вложенных папок
        $query = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type='folder' AND enabled=1 AND parent_id=" . $record_id . " ORDER BY ord DESC, title_" . $this->lang . ", id";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {

                // Формируем ссылку на текущий элемент
                //				$record["url"] = $this->catalog_path."cid_".$record["id"]."/";
                $record["url"] = $this->get_url_element($record["id"], $record);

                // Накладываем текущий язык сайта на результаты запроса
                $record = $this->apply_current_language($record);

                if ($record["type"] == "folder" && 0) {
                    // Читаем список вложенных папок
                    $foldersChild = $this->get_folders($record["id"]);
                    $record["folders"] = $foldersChild;
                    $record["count_folders"] = count($foldersChild);

                }
                // Читаем список вложенных элементов
                if ($this->its_root) {
                    $itemsChild = $this->get_items($record["id"], $record);
                } else {
                    $itemsChild = $this->get_items($record["id"], $record);
                }
                $record["items"] = $itemsChild;
                $record["count_items"] = count($itemsChild);

                // Добавляем запись в общий массив папок
                $folders[] = $record;
            }
        }

        // Возвращаем результат
        return $folders;
    }

    // Функция чтения списка вложенных элементов
    function get_items($record_id, $item_info = array(), $limit = 0) {

        //Максимальная цена
        $max_filter_price = "SELECT price FROM " . $this->cms->tables['catalog'] . " WHERE type = 'item' AND enabled = 1 AND full_relative_url LIKE '" . $item_info["full_relative_url"] . "%' ORDER BY price DESC LIMIT 1;";
        $max_filter_price = $this->dbc->Execute($max_filter_price);
        if ($max_filter_price) {
            if ($max_filter_price->RecordCount() >= 1) {
                $max_filter_price = $max_filter_price->FetchRow();
                $max_filter_price = $this->get_price_currency($max_filter_price['price']);
            } else {
                $max_filter_price = 0;
            }
        } else {
            $max_filter_price = 0;
        }
        if ($this->PriceMax > $max_filter_price) {
            $this->PriceMax = $max_filter_price;
            $_SESSION["price_max"] = $max_filter_price;
        }
        if ($this->PriceMax == 0) {
            $this->PriceMax = $max_filter_price;
            $_SESSION["price_max"] = $max_filter_price;
        }
        $_SESSION["real_price_max"] = 999999999;

        //Условие для цены
        $MySqlPrice = "";
        if (($this->PriceMin > 0) or ($this->PriceMax < $max_filter_price)) {
            $MySqlPrice = " and (price >= " . (int)($this->get_price_currency_back($this->PriceMin) - 1) . " and price <= " . (int)$this->get_price_currency_back($this->PriceMax) . ") ";
            //die($MySqlPrice);
        }
        //Сортировка элементов каталога
        if ($this->SotrType == 1) {
            $MySqlSortStr = " price ASC ";
        } elseif ($this->SotrType == 2) {
            $MySqlSortStr = " price DESC ";
        } elseif ($this->SotrType == 3) {
            $MySqlSortStr = " title_" . $this->lang . " ASC ";
        } elseif ($this->SotrType == 4) {
            $MySqlSortStr = " title_" . $this->lang . " DESC ";
        } else {
            //По умолчанию
            $MySqlSortStr = " title_" . $this->lang . " DESC ";
        }
        $MySqlPrice = "and (price >= 0 and price <= 900000)";

        //Структура элемента и все поля
        //Узнаем структуру элемента
        $CURRENT_FIELDSET = false;
        $_additional = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type = 'item' AND enabled = 1 AND full_relative_url LIKE '" . $item_info["full_relative_url"] . "%' ORDER BY price DESC LIMIT 1;";
        $_additional = $this->dbc->Execute($_additional);
        if ($_additional) {
            if ($_additional->RecordCount() >= 1) {
                $_additional = $_additional->FetchRow();
                $CURRENT_FIELDSET = $_additional["fieldset"];
                $_additional = $this->get_additional_info($_additional["id"], $_additional["fieldset"]);
                //echo '<pre>'; print_r($this->selects); echo '</pre>'; die();
                //echo '<pre>'; print_r($_additional); echo '</pre>'; die();
            } else {
                $_additional = false;
            }
        } else {
            $_additional = false;
        }

        //echo '<pre>'; print_r($_additional); echo '</pre>';

        $_SESSION['FILTER_ADD_FIELDS'] = $_additional;
        if (!isset($_SESSION['FILTER_ADD_FIELDS_BUFFER'])) {
            $_SESSION['FILTER_ADD_FIELDS_BUFFER'] = false;
        }

        //---------------------------------------------------------------------

        /////////////////////////////////////////////////////////////////////////////////////////////////
        // Генерация SQL запроса для фильтра (Начало)
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //Пытаемся сгенерировать шаблон для SQL запроса для фильтра с всеми таблицами
        $sqlt_fieldset_table_name = "pm_catalog_table_" . $CURRENT_FIELDSET;
        $sqlt_from = "`pm_catalog`, `" . $sqlt_fieldset_table_name . "`";
        $sqlt_where = "`pm_catalog`.`type` = 'item' AND `pm_catalog`.`enabled` = 1 AND `pm_catalog`.`full_relative_url` LIKE '" . $item_info["full_relative_url"] . "%' " . $MySqlPrice . " AND `pm_catalog`.`id` = `" . $sqlt_fieldset_table_name . "`.`record_id`";
        //$sqlt_limit = "LIMIT ".($this->page-1) * $this->options["records_per_page"].", ".$this->options["records_per_page"].";";
        //На время отключаем лимитирование
        $sqlt_limit = ";";
        $sqlt_limit = "{{LIMIT}}"; //Подменим потом лимитирование
        //Генерируем условие на основании выбранных данных пользователя
        $sqlt_new_where = "";
        if ((count($_SESSION['FILTER_ADD_FIELDS_BUFFER']) > 0) and ($_SESSION['FILTER_ADD_FIELDS_BUFFER'] !== false)) {
            //if(count($_SESSION['FILTER_ADD_FIELDS_BUFFER']) > 0) {
            $MainCurrent = 0;
            foreach ($_SESSION['FILTER_ADD_FIELDS_BUFFER'] as $_FieldName => $_ArrayOfSelectedValues) {
                if (count($_ArrayOfSelectedValues) > 0) {
                    $SubMainCurrent = 0;
                    $_SqltBuffStr = "";
                    foreach ($_ArrayOfSelectedValues as $_ElemId => $_Null) {
                        $_SqltBuffStr .= "`" . $sqlt_fieldset_table_name . "`.`" . $_FieldName . "` = '" . $_ElemId . "'";
                        $SubMainCurrent += 1;
                        if ($SubMainCurrent < count($_ArrayOfSelectedValues)) {
                            $_SqltBuffStr .= " OR ";
                        }
                    }
                    $_SqltBuffStr = "(" . $_SqltBuffStr . ")";
                    $sqlt_new_where .= $_SqltBuffStr;
                    if (($MainCurrent + 1) < count($_SESSION['FILTER_ADD_FIELDS_BUFFER'])) {
                        $sqlt_new_where .= " AND ";
                    }
                }
                $MainCurrent += 1;
            }

            if ($sqlt_new_where != "") {
                $sqlt_new_where = " AND (" . $sqlt_new_where . ")";
            }
            $SQL_TEMPLATE_COUNT = "SELECT COUNT(`pm_catalog`.`id`) as `num` FROM " . $sqlt_from . " WHERE " . $sqlt_where . $sqlt_new_where . " LIMIT 1;";
            $SQL_TEMPLATE_SELECT = "SELECT `pm_catalog`.* FROM " . $sqlt_from . " WHERE " . $sqlt_where . $sqlt_new_where . " ORDER BY " . $MySqlSortStr . $sqlt_limit;
            $debug = false;
            if ($debug) {
                echo '<b>Отладка фильтра</b><br />SQL запрос:<br /><br />';
                echo $SQL_TEMPLATE_COUNT;
                echo '<br /><br />';
                echo $SQL_TEMPLATE_SELECT;
                echo '<br /><br />';
                echo 'Текущий Fieldset: ' . $CURRENT_FIELDSET . ', а таблица: ' . $sqlt_fieldset_table_name;
                echo '<br /><br />';
                echo '<b>Выбранные данные для отсева фильтра (переменная $_SESSION[\'FILTER_ADD_FIELDS_BUFFER\']):<br /><br /><pre>';
                print_r($_SESSION['FILTER_ADD_FIELDS_BUFFER']);
                echo '</pre>';
                die();
            }

        } else {
            $SQL_TEMPLATE_COUNT = "";
            $SQL_TEMPLATE_SELECT = "";
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////
        // Генерация SQL запроса для фильтра (Конец)
        /////////////////////////////////////////////////////////////////////////////////////////////////

        $items = array();

        $orderAdd = '';
        if ($this->its_root) {
            $orderAdd = 'id DESC, ';
        }

        // Читаем общее количество вложенных элементов
        if (!empty($item_info["relative_url"])) {
            /*$query = "SELECT count(*) as num FROM ".$this->cms->tables['catalog']."
					 WHERE type='item' AND enabled=1 AND full_relative_url LIKE '".$item_info["full_relative_url"]."%' 
					 ORDER BY ".$orderAdd."ord DESC";*/

            $query = "SELECT
							count(*) as num
							FROM
							" . $this->cms->tables['catalog'] . "
									WHERE
							type='item' AND
							enabled=1 AND
							full_relative_url LIKE '" . $item_info["full_relative_url"] . "%'
							" . $MySqlPrice . "
							ORDER BY " . $MySqlSortStr;

            //Фильтр
            if ($SQL_TEMPLATE_COUNT != "") {
                $query = $SQL_TEMPLATE_COUNT;
                //die($query);
            }
        } else {
            /*$query = "SELECT count(*) as num FROM ".$this->cms->tables['catalog']."
					 WHERE type='item' AND enabled=1 AND parent_id=".$record_id." 
					 ORDER BY ".$orderAdd."ord DESC";*/

            $query = "SELECT
    count(*) as num
   FROM
    " . $this->cms->tables['catalog'] . "
			WHERE
    type='item' AND
    enabled=1 AND
    parent_id=" . $record_id . "
			ORDER BY " . $orderAdd . "ord DESC";
        }
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            $record = $result->FetchRow();
            $num_records = $record["num"];
        }

        if ($num_records > 0) {

            // Сохраняем ряд параметров для функции отрисовки навигации по страницам
            $this->totalItem = $num_records;
            $this->countItemInPage = $this->options["records_per_page"];
            if ($limit > 0) {
                $this->countItemInPage = $limit;
                $this->options["records_per_page"] = $limit;
            }

            // Формируем запрос с разбивкой по страницам
            if (!empty($item_info["relative_url"])) {
                /*$query = "SELECT * FROM ".$this->cms->tables['catalog']."
						  WHERE type='item' AND enabled=1 AND full_relative_url LIKE '".$item_info["full_relative_url"]."%' 
						  ORDER BY ord DESC, title_".$this->lang.", id
						  LIMIT ".($this->page-1) * $this->options["records_per_page"].", ".$this->options["records_per_page"];*/
                $query = "
     SELECT
      *
     FROM
      " . $this->cms->tables['catalog'] . "
					WHERE
      type = 'item' AND
      enabled = 1 AND
      full_relative_url LIKE '" . $item_info["full_relative_url"] . "%'
      " . $MySqlPrice . "
					ORDER BY " . $MySqlSortStr . "
     LIMIT " . ($this->page - 1) * $this->options["records_per_page"] . ", " . $this->options["records_per_page"];

                //Фильтр
                if ($SQL_TEMPLATE_SELECT != "") {
                    $SQL_TEMPLATE_SELECT = str_replace('{{LIMIT}}', "LIMIT " . ($this->page - 1) * $this->options["records_per_page"] . ", " . $this->options["records_per_page"], $SQL_TEMPLATE_SELECT);
                    $query = $SQL_TEMPLATE_SELECT;
                    //die($query);
                }
            } else {
                $query = "SELECT * FROM " . $this->cms->tables['catalog'] . "
						  WHERE type='item' AND enabled=1 AND parent_id=" . $record_id . " 
						  ORDER BY ord DESC, title_" . $this->lang . ", id
						  LIMIT " . ($this->page - 1) * $this->options["records_per_page"] . ", " . $this->options["records_per_page"];
            }
            // Читаем список вложенных элементов
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }
            if ($result->RecordCount() > 0) {
                while ($record = $result->FetchRow()) {

                    // Формируем ссылку на текущий элемент
                    //					$record["url"] = $this->catalog_path."cid_".$record["id"]."/";
                    $record["url"] = $this->get_url_element($record["id"], $record);

                    // Накладываем текущий язык сайта на результаты запроса
                    $record = $this->apply_current_language($record);

                    $additional = $this->get_additional($record["id"], $record["fieldset"], "list");
                    $record["additional"] = $additional;

                    $record["price"] = $this->get_price_currency($record["price"]);

                    // Добавляем запись в общий массив элементов
                    $items[] = $record;
                }
            }
        }

        // Возвращаем результат
        return $items;
    }

    function get_folders_for_price($record_id) {
        $folders = array();
        $query = "SELECT * FROM " . $this->cms->tables['catalog'] . " WHERE type='folder' AND enabled=1 AND parent_id=" . $record_id . " ORDER BY ord DESC, title_" . $this->lang . ", id";
        $result = $this->dbc->Execute($query);
        if ($result && $result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $record["url"] = $this->get_url_element($record["id"], $record);
                $record = $this->apply_current_language($record);
                unset($record["description"]);
                unset($record["video"]);
                $folders[] = $record;
            }
        }

        return $folders;
    }

    // Функция чтения списка вложенных элементов
    function get_items_for_price($record_id, $item_info = array(), $limit = 0) {
        $items = array();
        $orderAdd = '';
        if ($this->its_root) {
            $orderAdd = 'id DESC, ';
        }
        if (!empty($item_info["relative_url"])) {
            $query = "SELECT count(*) as num FROM " . $this->cms->tables['catalog'] . "
					 WHERE type='item' AND enabled=1 AND full_relative_url LIKE '" . $item_info["full_relative_url"] . "%' 
					 ORDER BY " . $orderAdd . "ord DESC";
        } else {
            $query = "SELECT count(*) as num FROM " . $this->cms->tables['catalog'] . "
					 WHERE type='item' AND enabled=1 AND parent_id=" . $record_id . " 
					 ORDER BY " . $orderAdd . "ord DESC";
        }
        $result = $this->dbc->Execute($query);
        if ($result && $result->RecordCount() > 0) {
            $record = $result->FetchRow();
            $num_records = $record["num"];
        }

        if ($num_records > 0) {
            // Формируем запрос с разбивкой по страницам
            if (!empty($item_info["relative_url"])) {
                $query = "SELECT * FROM " . $this->cms->tables['catalog'] . "
						  WHERE type='item' AND enabled=1 AND full_relative_url LIKE '" . $item_info["full_relative_url"] . "%' 
						  ORDER BY ord DESC, title_" . $this->lang . ", id";
            } else {
                $query = "SELECT * FROM " . $this->cms->tables['catalog'] . "
						  WHERE type='item' AND enabled=1 AND parent_id=" . $record_id . " 
						  ORDER BY ord DESC, title_" . $this->lang . ", id";
            }
            $result = $this->dbc->Execute($query);
            if ($result && $result->RecordCount() > 0) {
                while ($record = $result->FetchRow()) {
                    $record["url"] = $this->get_url_element($record["id"], $record);
                    $record = $this->apply_current_language($record);
                    $additional = $this->get_additional($record["id"], $record["fieldset"], "list");
                    $record["additional"] = $additional;
                    $record["price"] = $this->get_price_currency($record["price"]);
                    unset($record["description"]);
                    unset($record["video"]);
                    $items[] = $record;
                }
            }
        }

        return $items;
    }

    // Функция чтения прикрепленных к элементу изображений
    function get_gallery($record_id) {

        $gallery = array();

        // Читаем список прикрепленных изображений
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_gallery'] . ' WHERE enabled=1 AND record_id=' . $record_id . ' ORDER BY ord DESC';
        //		echo "<!--".$query."-->";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {

                // Накладываем текущий язык сайта на результаты запроса
                $record = $this->apply_current_language($record);

                // Добавляем запись в общий массив элементов
                $gallery[] = $record;
            }
        }

        // Возвращаем результат
        return $gallery;
    }

    // Функция чтения прикрепленных к элементу изображений
    function get_likes($record_id) {

        $items = array();

        // Читаем список прикрепленных изображений
        $query = "SELECT c.* FROM " . $this->cms->tables["catalog"] . " c LEFT JOIN " . $this->cms->tables["catalog_like"] . " cl ON cl.to_id=c.id WHERE cl.from_id='" . $record_id . "'";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {

                // Накладываем текущий язык сайта на результаты запроса
                $record = $this->apply_current_language($record);

                $record["price"] = $this->get_price_currency($record["price"]);

                $record["url"] = $this->get_url_element($record["id"], $record);

                // Добавляем запись в общий массив элементов
                $items[] = $record;
            }
        }

        // Возвращаем результат
        return $items;
    }

    // Функция чтения прикрепленных к элементу файлов
    function get_files($record_id) {

        $files = array();

        // Читаем список прикрепленных к элементу файлов
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_files'] . ' WHERE enabled=1 AND record_id=' . $record_id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {

                // Накладываем текущий язык сайта на результаты запроса
                $record = $this->apply_current_language($record);

                // Добавляем запись в общий массив элементов
                $files[] = $record;
            }
        }

        // Возвращаем результат
        return $files;
    }

    //....................................................................... Общие функции плагина

    // Функция наложение текущего языка сайта на результаты запроса
    function apply_current_language($data) {

        if (is_array($data)) {

            // По очереди проходим по всем полям
            $record = array();
            foreach ($data as $field_id => $field) {

                // Находим языковую версию языка поля
                $field_lang = substr($field_id, -3);
                if (substr($field_lang, 0, 1) == "_") {
                    $field_lang = substr($field_lang, 1);
                } else {
                    $field_lang = "";
                }

                // Проверяем что поле поддерживает один из определенных на сайте языков
                if (in_array($field_lang, $this->langs)) {

                    // Если это поля текущего языка, то добавляем его в массив
                    if ($field_lang == $this->lang) {

                        // Рассчитываем новый идентификатор поля
                        $new_field_id = substr($field_id, 0, -3);

                        // Добавляем значение поля в массив
                        $record[ $new_field_id ] = $field;
                    }

                } else {

                    // Если поле не зависит от языка, то просто копируем его
                    $record[ $field_id ] = $field;
                }
            }
            $data = $record;
        }

        return $data;
    }

    // Функция наложения фильтра типа при выборе дополнительных полей записи
    function apply_type_filter($data, $fieldset, $type) {

        // Читаем набор полей
        $fieldset = $this->fieldsets[ $fieldset ];

        if (is_array($data)) {

            // По очереди проходим по всем полям
            $record = array();
            foreach ($fieldset as $field_id => $field) {

                // Выбираем поля отмеченные для показа в списке
                if ($type == "folder") {

                    if (!empty($fieldset[ $field_id ]["show_list_flag"])) {

                        if ($fieldset[ $field_id ]["type"] == "image") {

                            // Если поле является изображением, то добавляем все его версии
                            $record[ $field_id ] = array(
                                "img"     => $data[ $field_id ],
                                "img_sm"  => $data[ $field_id . "_sm" ],
                                "img_sm1" => $data[ $field_id . "_sm1" ]
                            );
                        } else {
                            $record[ $field_id ] = $data[ $field_id ];
                        }
                    }

                    // Выбираем поля отмеченные для показа в подробной информации
                } else {

                    if (!empty($fieldset[ $field_id ]["show_view_flag"])) {
                        if ($fieldset[ $field_id ]["type"] == "image") {

                            // Если поле является изображением, то добавляем все его версии
                            $record[ $field_id ] = array(
                                "img"     => $data[ $field_id ],
                                "img_sm"  => $data[ $field_id . "_sm" ],
                                "img_sm1" => $data[ $field_id . "_sm1" ]
                            );
                        } else {
                            $record[ $field_id ] = @$data[ $field_id ];
                        }
                    }
                }
            }
            $data = $record;
        }

        return $data;
    }

    // Функция обработки списков в дополнительных полях записи
    function apply_select_values($data, $fieldset) {

        $select_values = array();

        // Читаем набор полей
        $fieldset = $this->fieldsets[ $fieldset ];

        // Читаем доступные списки выбора
        $selects = $this->selects;

        if (is_array($data)) {

            // По очереди проходим по всем полям
            $record = array();
            foreach ($data as $field_id => $field) {

                // Обрабатываем одиночные списки выбора
                if ($fieldset[ $field_id ]["type"] == "select") {

                    // Читаем название списка выбора
                    $select = $fieldset[ $field_id ]["options"];

                    // Читаем идентификатор выбранной опции
                    $option_id = $data[ $field_id ];

                    // Читаем значение элемента
                    if (!empty($selects[ $select ][ $option_id ])) {
                        $value = $selects[ $select ][ $option_id ];
                    } else {
                        $value = "";
                    }

                    // Обрабатываем множественные списки выбора
                } elseif ($fieldset[ $field_id ]["type"] == "multiple") {

                    // Читаем название списка выбора
                    $select = $fieldset[ $field_id ]["options"];

                    // Читаем идентификаторы выбранных опций
                    $option_ids = explode("|", $data[ $field_id ]);
                    $tmp = array();
                    foreach ($option_ids as $valTmp) {
                        if (!empty($valTmp)) {
                            $tmp[] = $valTmp;
                        }
                    }
                    $option_ids = $tmp;

                    // По порядку добавляем все значения
                    $value = array();
                    foreach ($option_ids as $option_id) {

                        // Читаем значение элемента
                        if (!empty($selects[ $select ][ $option_id ])) {
                            $value[] = $selects[ $select ][ $option_id ];
                        }
                    }

                    // Обрабатываем множественные списки выбора с ценой
                } elseif ($fieldset[ $field_id ]["type"] == "multipleprice") {

                    // Читаем название списка выбора
                    $select = $fieldset[ $field_id ]["options"];

                    // Читаем идентификаторы выбранных опций
                    $option_ids = explode("|", $data[ $field_id ]);
                    $tmp = array();
                    foreach ($option_ids as $valTmp) {
                        if (!empty($valTmp)) {
                            $arrVls = explode(":", $valTmp);
                            $tmp[ $arrVls[0] ] = $arrVls[1];
                        }
                    }
                    $option_ids = $tmp;

                    // По порядку добавляем все значения
                    $value = array();
                    foreach ($option_ids as $option_id => $price) {

                        // Читаем значение элемента
                        if (!empty($selects[ $select ][ $option_id ])) {
                            $value[] = array("title" => $selects[ $select ][ $option_id ], "price" => $this->get_price_currency($price), "id" => $option_id);
                        }
                    }

                    // Обрабатываем изображения
                } elseif ($fieldset[ $field_id ]["type"] == "image") {

                    // По порядку добавляем все доступные изображения
                    $value = $field;

                    // Обрабатываем обычные поля
                } else {

                    // Если это обычное поле, то просто копируем его
                    $value = $field;
                }

                // Формируем запись с информацией о поле
                $record = array(
                    "type"  => $fieldset[ $field_id ]["type"],
                    "field" => $fieldset[ $field_id ]["title"],
                    "value" => $value
                );

                // Добавляем очередной элемент в общий массив
                $select_values[ $field_id ] = $record;
            }
        }

        return $select_values;
    }

    // Функция увеличения числа просмотров элемента каталога
    function increase_num_views($record_id) {

        $query = 'UPDATE ' . $this->cms->tables['catalog'] . ' SET num_views=num_views+1 WHERE id = ' . $record_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . "<br>Query: " . $query, E_USER_ERROR);

            return false;
        }
    }

    // Функция чтения идентификатора элемента каталога для текущего блока
    function get_catalog_id($s_id, $toc_id) {

        $catalog_id = 0;

        // Проверяем, определен ли каталог для данного блока
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_structure'] . ' WHERE s_id=' . $s_id . ' AND toc_id=' . $toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }

        if ($result->RecordCount() > 0) {

            // Читаем идентификатор элемента каталога из базы данных
            $record = $result->FetchRow();
            $catalog_id = $record["record_id"];

        }

        return $catalog_id;
    }

    function deleteOldInfo() {
        if (date("H", time()) > 20) {
            $sql = "DELETE FROM " . $this->cms->tables["catalog_structure"] . " WHERE toc_id NOT IN (SELECT toc_id FROM " . $this->cms->tables["toc"] . " WHERE handler='catalog')";
            $this->dbc->Execute($sql);
        }
    }

    // Функция чтения пути к текущему каталогу по его идентификатору
    function get_catalog_path($catalog_id) {

        // По умолчанию определяем путь к каталогу как путь к текущей странице
        $catalog_path = $this->page_info["structure"]["url"];

        // Формируем запрос для поиска основного каталога
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_structure'] . ' WHERE record_id=' . $catalog_id . ' ORDER BY main_flag DESC';
        //		echo $query."<br>";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {

            // Читаем идентификатор структуры
            $record = $result->FetchRow();
            $s_id = $record["s_id"];

            // Формируем запрос к структуре сайта для нахождения URL странички основного каталога
            $query = 'SELECT * FROM ' . $this->cms->tables['structure'] . ' WHERE s_id=' . $s_id . ' AND enabled=1';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }
            if ($result->RecordCount() > 0) {

                // Формируем путь к каталогу
                $record = $result->FetchRow();
                $catalog_path = $this->page_info["root_url"] . $record["relative_url"];
            }
        }

        //var_dump ($catalog_path); die();
        return $catalog_path;

    }

    // Функция чтения списка доступных наборов полей
    function get_fieldsets() {

        $fieldsets = array();

        // Читаем доступные наборы полей
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_fieldsets'] . ' ORDER BY title';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $fieldsets[ $record["id_text"] ] = $this->get_fieldsets_fields($record["id_text"]);
            }
        }

        return $fieldsets;
    }

    // Функция чтения названия полей в конкретном наборе полей
    function get_fieldsets_fields($fieldset) {

        $fieldsets_fields = array();

        // Читаем доступные наборы полей
        $query = "SELECT * FROM " . $this->cms->tables['catalog_fieldsets_data'] . " WHERE fieldset='" . $fieldset . "' ORDER BY ord DESC";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $fieldsets_fields[ $record["id_text"] ] = $record;
            }
        }

        return $fieldsets_fields;
    }

    // Функция чтения доступных списков выбора
    function get_selects() {

        $selects = array();

        // Читаем доступные списки выбора
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_selects'] . ' ORDER BY title';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $selects[ $record["id_text"] ] = $this->get_select_options($record["id_text"]);
            }
        }

        return $selects;
    }

    // Функция чтения опций списка выбора по его текстовому идентификатору
    function get_select_options($select) {

        $select_options = array();

        // Формируем имя таблицы, хранящей элементы списка
        $table_name = PREFIX_TABLE . "_catalog_select_" . $select;

        // Читаем доступные списки выбора
        $query = 'SELECT * FROM ' . $table_name . ' WHERE enabled=1 ORDER BY ord DESC, title_' . $this->lang;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $select_options[ $record["id"] ] = $record[ "title_" . $this->lang ];
            }
        }

        return $select_options;
    }

    // Рекурсивная функция чтения пути к текущему элементу
    function get_path($id, $path = array()) {

        // Читаем информацию о запрашиваемом элементе
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog'] . ' WHERE id=' . $id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

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
            $path[] = array(
                "title" => $record[ "title_" . $this->lang ],
                "id"    => $record["id"],
                "alias" => $record["alias"],
                "s_id"  => "c" . $record["id"],
                //				"url" 	=> $this->catalog_path."cid_".$record["id"]."/"
                "url"   => $this->get_url_element($record["id"], $record)
            );
        }

        return $path;
    }

    // Рекурсивная функция проверки, что все родительские элементы включены
    function get_path_enabled($id) {

        // Читаем информацию о запрашиваемом элементе
        $query = 'SELECT parent_id, enabled FROM ' . $this->cms->tables['catalog'] . ' WHERE id=' . $id . ' ORDER BY ord DESC';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {

            // Читаем информацию об элементе
            $record = $result->FetchRow();

            if ($record["enabled"] == "0") {
                return 0;
            } else {
                return $this->get_path_enabled($record["parent_id"]);
            }
        }

        return 1;
    }

    // Рекурсивная функция чтения дерева для запрошенного элемента
    function get_tree($id = 0, $type = "folder", $tree = array(), $counter = 0) {

        // Увеличиваем счетчик вложенности
        $counter++;

        // Формируем запрос к базе данных
        if ($type == "folder") {
            $query = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE parent_id=" . $id . " AND type='folder' AND enabled=1 ORDER BY ord DESC";
        } else {
            $query = "SELECT * FROM " . $this->cms->tables["catalog"] . " WHERE parent_id=" . $id . " AND enabled=1 ORDER BY ord DESC";
        }

        // Читаем информацию о запрашиваемом элементе
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {

            while ($record = $result->FetchRow()) {

                // Формируем описание элемента
                $tree[ $record["id"] ] = str_repeat("&nbsp;&nbsp;&nbsp;", $counter) . $record[ "title_" . $this->lang ];

                // Рекурсивно вызываем функцию для нахождения всех дочерних элементов
                $tree = $this->get_tree($record["id"], $type, $tree, $counter);
            }
        }

        // Уменьшаем счетчик вложенности
        $counter--;

        return $tree;
    }

    // возвращает путь к элементу каталога
    function get_url_element($id, $item = array()) {
        if (!isset($item["relative_url"])) {
            $item = $this->get_record($id);
        }
        $url = $this->catalog_path . substr($item["relative_url"], 1);

        return $url;
    }

    // возвращает элемент на основании виртуального пути
    function get_id_element_by_virtuals() {
        $this->cms->vars_404['catalog'] = 'true';
        $ret = 0;
        $arr_path = array();
        foreach ($this->page_info["virtuals"] as $virt) {
            $relative_url = '/';
            if (isset($arr_path[ count($arr_path) - 1 ])) {
                $relative_url = $arr_path[ count($arr_path) - 1 ];
            }
            $relative_url .= $virt . "/";
            $arr_path[] = $relative_url;
        }
        $count_item = count($arr_path) - 1;
        //		for($i=$count_item; $i>=0; $i--) {

        //Проверяем наличие переменной для страницы
        //------------------------------------------------------------------------------------------
        //print_r($arr_path); die();
        $_args = explode('/', $arr_path[ $count_item ]);
        $_tmp = Array();
        foreach ($_args as $_arg) {
            if (trim($_arg) != '') {
                $_tmp[] = $_arg;
            }
        }
        $_args = $_tmp;
        Unset($_tmp);

        $is_page_arg = $_args[ count($_args) - 1 ];
        $is_page_arg = explode('_', $is_page_arg);
        if (count($is_page_arg) != 2) {
            $is_page_arg = false;
        }
        if ($is_page_arg) {
            $is_page_arg = trim($is_page_arg[0]);
            if ($is_page_arg != 'cpage') {
                $is_page_arg = false;
            } else {
                $is_page_arg = true;
            }
        }

        $relative_url = $arr_path[ $count_item ];
        if ($is_page_arg and ($count_item >= 1)) {
            //Корректировка пути. Убираем переменную для пейджинга
            $relative_url = $arr_path[ $count_item - 1 ];
        }

        //print_r($relative_url); die();
        //------------------------------------------------------------------------------------------

        $sql = "SELECT id FROM " . $this->cms->tables["catalog"] . " WHERE relative_url='" . addslashes($relative_url) . "'";
        $result = $this->dbc->Execute($sql);
        if ($result && $result->RecordCount() > 0 && $ret == '') {
            $record = $result->FetchRow();
            $ret = $record["id"];
        } else {
            $this->cms->vars_404['catalog'] = 'false';
        }
        //		}
        //		echo "<pre>"; print_r($arr_path); echo "</pre>";
        //		echo $ret;
        //		die();

        return $ret;
    }

    // Функция чтения содержимого корзины
    function get_basket($basket_id) {

        $basket = "";

        // Если идентификатор корзины пустой, то сразу возвращаемся
        if (empty($basket_id)) {
            return "";
        }

        // Читаем список товаров в корзине
        $query = 'SELECT *, SUM(quantity) as quantity FROM ' . $this->cms->tables['catalog_baskets'] . ' WHERE basket_id=' . $basket_id . ' GROUP BY item_id, additional ORDER BY id';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }

        if ($result->RecordCount() > 0) {

            // Читаем описание всех товаров в корзине
            while ($record = $result->FetchRow()) {

                // Читаем информацию о товаре
                $product = $this->get_record($record["item_id"]);
                //				echo "<pre>"; print_r($product); echo "</pre>";

                if (!empty($product)) {

                    // Добавляем описание товара в корзине
                    $record["title"] = $product["title"];
                    $record["code"] = $product["code"];
                    $record["short_description"] = $product["short_description"];
                    $record["url"] = $this->get_url_element($record["item_id"], $record);
                    $record["price"] = $product["price"];
                    $record["img"] = $product["img"];
                    $record["img_sm"] = $product["img_sm"];
                    $record["img_sm1"] = $product["img_sm1"];

                    $additional = array();
                    $record["additionalArr"] = array();

                    if ($record["additional"] != '') {
                        $additional = $this->get_additional($product["id"], $product["fieldset"], $product["type"]);

                        $arrAdditional = explode("|", $record["additional"]);
                        $newAdditional = array();
                        foreach ($arrAdditional as $addit) {
                            $arrTemp = explode(":", $addit);
                            if (isset($additional[ $arrTemp[0] ])) {
                                $info_field = $additional[ $arrTemp[0] ];
                                $info_additional = array();
                                $info_additional["field"] = $info_field["field"];
                                if (count($info_field["value"]) > 0) {
                                    foreach ($info_field["value"] as $field_value) {
                                        if ($arrTemp[1] == $field_value["id"]) {
                                            $info_additional["title"] = $field_value["title"];
                                            $info_additional["price"] = $field_value["price"];
                                            $info_additional["id"] = $field_value["id"];
                                            $record["price"] += $field_value["price"];
                                        }
                                    }
                                }
                                $newAdditional[] = $info_additional;
                            }
                        }
                        $record["additionalArr"] = $newAdditional;
                    }
                    $record["count_additionalArr"] = count($record["additionalArr"]);

                    $record["cost"] = number_format($record["price"] * $record["quantity"], 2, ".", "");

                    $action["its_action"] = 0;

                    //					echo $record["price"]." ".$_SESSION["all_currency"][1]["ratio"]." ".$this->min_price_from_action."<br>";
                    if ($_SESSION["currency"] == 1) {
                        $ration = 1;
                    } else {
                        $ration = $_SESSION["all_currency"][1]["ratio"];
                    }
                    if ($record["price"] * $ration > $this->min_price_from_action) {
                        $this->catalog_id_price_more_min_action = $record["id"];
                    }

                    //					echo "<pre>"; print_r($record); echo "</pre>";
                    //					echo "<pre>"; print_r($additional); echo "</pre>";

                    // Добавляем очередной элемент в корзину
                    $basket[] = $record;
                }
            }
        }

        if ($this->page_info["user_data"]["unique"]["catalog_id"] > 0) {
            $action = $this->get_record($this->page_info["user_data"]["unique"]["catalog_id"]);
            $action["quantity"] = 1;
            $action["cost"] = number_format($action["price"] * $action["quantity"], 2, ".", "");
            $action["its_action"] = 1;
            $action["additional"] = "";
            $action["item_id"] = $action["id"];
            $basket[] = $action;
        }

        //		echo "<pre>"; print_r($basket); echo "</pre>"; die();

        return $basket;
    }

    // Функция чтения уникального идентификатора корзины
    function get_unique_basket_id() {

        // Генерируем уникальное десятизначное число
        $basket_id = mt_rand(1000000000, 9999999999);

        // Проверяем, что такого идентификатора еще нет в базе данных
        $query = 'SELECT * FROM ' . $this->cms->tables['catalog_baskets'] . ' WHERE basket_id=' . $basket_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            $basket_id = $this->get_unique_basket_id();
        }

        return $basket_id;
    }

    // Функция удаления устаревших корзин из базы данных
    function clear_old_baskets() {

        // Читаем список корзин, которые не посещались более месяца
        $query = 'SELECT basket_id FROM ' . $this->cms->tables['catalog_baskets'] . ' WHERE created < DATE_SUB(now(), INTERVAL 2 DAY ) GROUP BY basket_id';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {

            // Формируем
            $baskets_ids = array();
            while ($record = $result->FetchRow()) {
                $baskets_ids[] = $record["basket_id"];
            }
        }

        if (!empty($baskets_ids)) {

            // Удаляем устаревшие корзины
            $query = 'DELETE FROM ' . $this->cms->tables['catalog_baskets'] . ' WHERE basket_id in (' . implode(",", $baskets_ids) . ')';

            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);

                return false;
            }
        }
    }

    //............................................................................... Общие функции

    // Функция формирующая ссылки навигации по записям
    function setNavi(&$block) {

        $page_ident = 'cpage';

        $arrGet = array();
        $startPath = $this->catalog_path;

        $startPath = str_replace('/ru/', '/', $startPath);
        foreach ($this->page_info["virtuals"] as $get_var) {
            $is_page_ident = $get_var;
            $is_page_ident = explode('_', $is_page_ident);
            if (count($is_page_ident) == 2) {
                if (trim($is_page_ident[0]) == $page_ident) {
                    $is_page_ident = true;
                } else {
                    $is_page_ident = false;
                }
            } else {
                $is_page_ident = false;
            }
            if (!$is_page_ident) {
                $startPath .= $get_var . '/';
            }
        }
        //http://electronom.com.ua.local/ru/catalog/

        //print_r($startPath); die();
        //print_r($this->page_info["virtuals"]); die();

        if (count($this->GETVar) > 0) {
            foreach ($this->GETVar as $key => $value) {
                if ($key != $page_ident) {
                    $startPath .= $key . "_" . $value . "/";
                }
            }
        }
        if (isset($_GET) && count($_GET) > 0) {
            foreach ($_GET as $key => $value) {
                if ($key != $page_ident && $key != "id") {
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
            $pages["prev"] = $startPath . $page_ident . "_" . ($this->page - 1) . "/" . (($get) ? "?" . $get : "");
        } else {
            $pages["first"] = '';
            $pages["prev"] = '';
        }
        if ($this->page < $this->totalPage) {
            $pages["next"] = $startPath . $page_ident . '_' . ($this->page + 1) . "/" . (($get) ? "?" . $get : "");
            $pages["last"] = $startPath . $page_ident . '_' . $this->totalPage . "/" . (($get) ? "?" . $get : "");
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
                $temp["url"] .= $page_ident . "_" . $counter . "/";
            }
            $temp["url"] .= (($get) ? "?" . $get : "");
            $pages["pages"][] = $temp;
            $counter++;
        }
        $pages["allItemUrl"] = $startPath . 'page_all/' . (($get) ? "?" . $get : "");
        $block["navi"] = $pages;
    }

    // Функция парсинга переменных в шаблон
    function parse($template, $data) {

        // Рассчитываем имя файла содержащего шаблон
        $tpl_file = $this->plugin['path'] . 'tpls/' . $template . '.tpl';

        if (file_exists($tpl_file) && is_readable($tpl_file)) {

            // Присваиваем переменные шаблону
            $this->cms->smarty->assign(array('output' => $data));

            // Производим обработку шаблона и получаем готовый HTML-код в качестве результата
            $output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);

        } else {
            $output = false;
        }

        return $output;
    }

    // Функция получения переменных, переданных в адресной строке
    function parseGetVar() {
        $name = '';
        //die(print_r($this->page_info["virtuals"], true));
        foreach ($this->page_info["virtuals"] as $val) {
            if (strstr($val, "_")) {
                $varArr = explode("_", $val, 2);
                //die(print_r($varArr, true));
                if (in_array($varArr[0], $this->possibleGETVars)) {
                    $this->GETVar[ $varArr[0] ] = $varArr[1];
                    //$this->cms->vars_404['catalog'] = 'true';
                }
            }
        }

        //print_r($this->GETVar); die();
    }

    // Функция проверки переменных, переданных в форме
    function checkForm($send, &$block) {

        $errors = array();
        $errors['countErrors'] = 0;

        if (!isset($send['last_name']) || trim($send['last_name']) == '') {
            $errors['last_name'] = 1;
            $errors['countErrors']++;
        }

        if (!isset($send['city']) || trim($send['city']) == '') {
            $errors['city'] = 1;
            $errors['countErrors']++;
        }

        if (!isset($send['email']) || trim($send['email']) == '') {
            $errors['email'] = 1;
            $errors['countErrors']++;
        }
        if ($send['email'] != '' || trim($send['email']) != '') {
            if (!preg_match($this->regexp_email, $send['email'])) {
                $errors['email'] = 1;
                $errors['countErrors']++;
            }
        }

        /*if (!isset($send['phone']) || trim($send['phone']) == '') {
            $errors['phone'] = 1;
            $errors['countErrors']++;
        }
        if (!isset($send['address']) || trim($send['address']) == '') {
            $errors['address'] = 1;
            $errors['countErrors']++;
        }*/
        if (!isset($send['type_pay']) || (int)$send['type_pay'] < 1 || (int)$send['type_pay'] > 3) {
            $errors['type_pay'] = 1;
            $errors['countErrors']++;
        }
        if (isset($send['type_pay']) && (int)$send['type_pay'] == 1) {
            if (!isset($send['webmoney']) || isset($send['webmoney']) && $send['webmoney'] != "z" && $send['webmoney'] != "u") {
                $errors['type_pay_1'] = 1;
                $errors['countErrors']++;
            }
        }
        if (isset($send['type_pay']) && (int)$send['type_pay'] == 3) {
            if (!isset($send['type_face']) || isset($send['type_face']) && $send['type_face'] != 1 && $send['type_face'] != 2) {
                $errors['type_face'] = 1;
                $errors['countErrors']++;
            }
        }

        require_once(cms_LIB_PATH . 'protection_image.php');
        $protection_image = new protection_image;

        if (!empty($send['code'])) {
            if ($protection_image->check_code($send['code']) != true) {
                $errors['countErrors']++;
                $errors['code'] = 1;
            }
        } else {
            $errors['code'] = 1;
            $errors['countErrors']++;
        }
        $block['order'] = $send;
        $block['errors'] = $errors;
    }

    // Функция проверки переменных, переданных в форме
    function checkFormPre($send, &$block) {

        $errors = array();
        $errors['countErrors'] = 0;

        if (!isset($send['name']) || trim($send['name']) == '') {
            $errors['name'] = 1;
            $errors['countErrors']++;
        }
        if (!isset($send['email']) || trim($send['email']) == '') {
            $errors['email'] = 1;
            $errors['countErrors']++;
        }
        if ($send['email'] != '' || trim($send['email']) != '') {
            if (!preg_match($this->regexp_email, $send['email'])) {
                $errors['email'] = 1;
                $errors['countErrors']++;
            }
        }

        if (!isset($send['phone']) || trim($send['phone']) == '') {
            $errors['phone'] = 1;
            $errors['countErrors']++;
        }

        require_once(cms_LIB_PATH . 'protection_image.php');
        $protection_image = new protection_image;

        if (!empty($send['code'])) {
            if ($protection_image->check_code($send['code']) != true) {
                $errors['countErrors']++;
                $errors['code'] = 1;
            }
        } else {
            $errors['code'] = 1;
            $errors['countErrors']++;
        }
        $block['order'] = $send;
        $block['errors'] = $errors;
    }

    // Функция обработки переменных, переданных в форме
    function prepareVar(&$send) {

        foreach ($send as $key => $val) {
            if (is_array($val)) {
                $val = $this->prepareVar($val);
            } else {
                $val = strip_tags($val);
                $val = htmlspecialchars($val);
                $send[ $key ] = $val;
            }
        }
    }

    // Функция инициализации поддерживаемых на сайте языков
    function init_languages() {

        $languages = array();
        $lang = "";

        // Читаем список поддерживаемых на сайте языков
        $query = "SELECT language_id, short_title, alias FROM " . $this->cms->tables["languages"] . " ORDER BY ord ASC";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . "<br>Query: " . $query, E_USER_ERROR);

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

    // Фунцкция инициализации опций модуля
    function init_options() {

        $options = array();

        // Читаем список опций из базы данных
        $query = "SELECT * FROM " . $this->cms->tables["catalog_options"] . "";
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . "<br>Query: " . $query, E_USER_ERROR);

            return false;
        }
        if ($result->RecordCount() > 0) {
            while ($record = $result->FetchRow()) {
                $options[ $record["name"] ] = $record["value"];
            }
        }

        // Сохраняем прочитанные опции в переменной класса
        $this->options = $options;
    }

    // Функция инициализации таблиц для работы плагина
    function init_tables() {
        $pathToTable = cms_PLUGINS_PATH . $this->plugin['name'] . '_manage/tables.php';
        if (file_exists($pathToTable)) {
            $requiredFiles = get_required_files();
            if (!in_array($pathToTable, $requiredFiles)) {
                include($pathToTable);
                $this->cms->tables = array_merge(@$this->tables, $this->cms->tables);
            }
        }
    }

    //............................................................................................

    function addComments(&$block) {
        $this->checkFormComments($block);
        if ($block["countErrors"] == 0 && isset($block["formValues"])) {
            $this->saveComments($block);
        }
        $this->getComments($block);
    }

    function getComments(&$block) {
        $ret = array();
        $sql = "SELECT * FROM " . $this->cms->tables["catalog_comments"] . " WHERE catalog_id='" . (int)$block["id"] . "' AND enabled='1' ORDER BY dates DESC";
        //		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        $mark = 0;
        $count = 0;
        if ($result && $result->RecordCount() > 0) {
            while ($item = $result->FetchRow()) {
                $ret[] = $item;
            }
        }
        $block["comments"] = $ret;
        $block["countComments"] = count($ret);
    }

    function saveComments(&$block) {
        $values = $block["formValues"];
        if (!isset($values["fio"])) {
            $values["fio"] = "";
        }
        $sql = "INSERT INTO " . $this->cms->tables["catalog_comments"] . " SET catalog_id='" . (int)$block["id"] . "', fio='" . addslashes($values["fio"]) . "', comments='" . nl2br(addslashes($values["comments"])) . "', enabled='1', dates=NOW()";
        //		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);

        $commentsID = $this->dbc->insert_id();
        $this->sendToEmailComments((int)$block["id"], $commentsID);

        $target_url = $this->page_info["request_url"];
        //		echo $target_url."<br>";
        header('Location: ' . $target_url);
        die();
    }

    function sendToEmailComments($catalog_id, $id) {
        $sql = "SELECT * FROM " . $this->cms->tables['catalog_comments'] . " WHERE catalog_comments_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        $form_info = $result->FetchRow();

        $sql = "SELECT *, title_" . $this->page_info["lang"] . " as title FROM " . $this->cms->tables['catalog'] . " WHERE id='" . $catalog_id . "'";
        $result = $this->dbc->Execute($sql);
        $itemCatalog = $result->FetchRow();
        $form_info['itemId'] = $itemCatalog["id"];
        $form_info['itemName'] = $itemCatalog["title"];
        $form_info['itemURL'] = $this->page_info["request_url"];

        require_once(cms_LIB_PATH . 'mail_event.class.php');
        require_once(cms_LIB_PATH . 'email_message.class.php');
        require_once(cms_LIB_PATH . 'strcom_parser.class.php');
        $mail_event = new mail_event($this->plugin['path'] . 'tpls/send/', '.tpl');

        $form_info['site_url'] = ROOT_URL;
        //		$form_info['manager_email'] = $form_info["sendToEmail"]; //MANAGER_EMAIL;
        $form_info['datetime'] = Date('d.m.Y H:i');
        $form_info['subj'] = 'Новый комментарий';
        if (isset($form_info['fio'])) {
            $form_info['from_name'] = $form_info['fio'];
        } else {
            $form_info['from_name'] = "Аноним";
        }

        $mails = $this->getEmailManager();
        //echo '<pre>';print_r($mails);echo '</pre>';
        foreach ($mails as $mail) {
            $form_info['manager_email'] = $mail; //MANAGER_EMAIL;
            $mail_event->event('send', $form_info);
        }

        //echo '<pre>';print_r($form_info);echo '</pre>';

        header("Location: " . $this->page_info["request_url"] . "?sended=ok");
    }

    function getEmailManager() {
        $ret = array($this->admin_email, "andrey@kai-studio.com.ua");

        /*		$query = 'SELECT * FROM '.$this->cms->tables['catalog_manager_mail'].' WHERE enabled=1 ORDER BY id';
		$result = $this->dbc->Execute($query);
		if ($result && $result->RecordCount()>0) {
			while ($item = $result->FetchRow()) {
				$ret[] = $item["email"];
			}
		}*/

        return $ret;
    }

    function checkFormComments(&$block) {
        $error = 0;
        $formError = array();
        if (isset($_POST["comments"])) {
            $formComments = $_POST["comments"];
            //			echo "<pre>"; print_r($formComments); echo "</pre>";
            foreach ($formComments as $key => $value) {
                $formComments[ $key ] = htmlspecialchars(strip_tags($value));
            }
            if (trim($block["id"]) == '') {
                $error++;
                $formError["catalog_id"] = 1;
            }
            if (trim($formComments["fio"]) == '') {
                $error++;
                $formError["fio"] = 1;
            }
            if (trim($formComments["comments"]) == '') {
                $error++;
                $formError["comments"] = 1;
            }
            if (stristr($formComments["comments"], '[url=') != false && stristr($formComments["comments"], 'http://') != false) {
                $error++;
                $formError["comments"] = 2;
            }

            require_once(cms_LIB_PATH . 'protection_image.php');
            $protection_image = new protection_image;
            if (!empty($formComments['code'])) {
                if ($protection_image->check_code($formComments['code']) != true) {
                    $error++;
                    $formError['code'] = 1;
                }
            } else {
                $formError['code'] = 1;
                $error++;
            }

            $block["formValues"] = $formComments;
        }
        $block["countErrors"] = $error;
        $block["formError"] = $formError;
    }

    function get_price_currency($price) {
        $ret = $price;
        if (!isset($_SESSION["currency"])) {
            $sql = 'SELECT * FROM pm_catalog_currency WHERE currency_id=1';
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                $_SESSION["currency"] = 1;
                $item = $result->FetchRow();
                $_SESSION["all_currency"][1] = $item;
            }
        }

        if (isset($_SESSION["currency"]) && isset($_SESSION["all_currency"][ $_SESSION["currency"] ])) {
            $ret = number_format($price * $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"], 2, ".", "");
            //			echo $ret." ".$price." ".$_SESSION["all_currency"][$_SESSION["currency"]]["ratio"];
            //			die();
        }

        //		die("'".$_SESSION["currency"]."'");

        return $ret;
    }

    function get_price_currency_back($price) {
        $ret = $price;
        //return $ret;
        if (!isset($_SESSION["currency"])) {
            $sql = 'SELECT * FROM pm_catalog_currency WHERE currency_id=1';
            $result = $this->dbc->Execute($sql);
            if ($result && $result->RecordCount() > 0) {
                $_SESSION["currency"] = 1;
                $item = $result->FetchRow();
                $_SESSION["all_currency"][1] = $item;
            }
        }

        if (isset($_SESSION["currency"]) && isset($_SESSION["all_currency"][ $_SESSION["currency"] ])) {
            //$ret = number_format($price*$_SESSION["all_currency"][$_SESSION["currency"]]["ratio"], 2, ".", "");
            $ret = number_format($price / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"], 2, ".", "");
        }

        return $ret + 1;
    }

    function get_order_xls($data, $basket, $order_id, $xls_number) {
        //		echo "<pre>"; print_r($order_id); echo "</pre>";
        //		echo "<pre>"; print_r($data); echo "</pre>";
        //		echo "<pre>"; print_r($basket); echo "</pre>";

        if ($data["type_face"] == 1) {
            $billFile = $this->cms->smarty_dirs['templates'] . '/bill/bn.xls';
        } else {
            $billFile = $this->cms->smarty_dirs['templates'] . '/bill/fop.xls';
        }
        $realBillFile = 'content/catalog/bills/bill_' . date('Ymd') . '_' . $order_id . '_' . $this->addZero($xls_number, 4) . '.xls';
        $this->cms->files->checkDir(ROOT_HTML_PATH . 'content/catalog/bills/');
        //		echo $realBillFile."<br>";
        $targetFile = ROOT_HTML_PATH . $realBillFile;
        //		echo $targetFile."<br>";

        $handle = fopen($billFile, "r");
        $content = fread($handle, filesize($billFile));
        fclose($handle);
        $handle = fopen($targetFile, "w");

        $counter = 1;
        $total_nds = 0;
        $total_quantity = $total_cost = 0;
        $basketXLS = '';
        if ($this->page_info["user_data"]["unique"]["catalog_id"] > 0) {
            $count_basket = count($basket) - 1;
        } else {
            $count_basket = count($basket);
        }
        foreach ($basket as $item_id => $item) {
            if (!isset($item["its_action"]) || isset($item["its_action"]) && $item["its_action"] != 1) {
                if ((int)$data["type_face"] == 1) {
                    $item["price"] = $item["price"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                    $item["cost"] = $item["cost"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][2]["ratio"];
                } else {
                    $item["price"] = $item["price"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                    $item["cost"] = $item["cost"] / $_SESSION["all_currency"][ $_SESSION["currency"] ]["ratio"] * $_SESSION["all_currency"][5]["ratio"];
                }
                $price_without_nds = number_format((float)$item["price"] / 6 * 5, 2, ".", "");
                $cost_without_nds = number_format((float)$item["cost"] / 6 * 5, 2, ".", "");
                $sum_nds = number_format((float)$item["cost"] / 6, 2, ".", "");
                $total_nds += $sum_nds;
                $total_quantity += $item["quantity"];
                $total_cost += (float)$item["cost"]; //s177//m22390860
                if ($data["type_face"] == 1) {
                    $basketXLS .= '
   <Row ss:AutoFitHeight="0" ss:Height="11.8125">
    <Cell ss:Index="2" ss:MergeAcross="1" ss:StyleID="' . (($counter < $count_basket) ? "s171" : "s175") . '"><Data ss:Type="Number">' . $counter . '</Data></Cell>
    <Cell ss:MergeAcross="16" ss:StyleID="' . (($counter < $count_basket) ? "s168" : "s177") . '"><Data ss:Type="String">' . $this->unicode_russian($item["title"]) . '</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="' . (($counter < $count_basket) ? "m22390424" : "m22390860") . '"><Data ss:Type="Number">' . $item["quantity"] . '</Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s127"><Data ss:Type="String">' . $this->unicode_russian('шт') . '</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="s129"><Data ss:Type="Number">' . number_format($price_without_nds, 2, ".", "") . '</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="m22390454"><Data ss:Type="Number">' . number_format($cost_without_nds, 2, ".", "") . '</Data></Cell>
   </Row>';
                } else {
                    $basketXLS .= '
   <Row ss:AutoFitHeight="0" ss:Height="11.8125">
    <Cell ss:Index="2" ss:MergeAcross="1" ss:StyleID="' . (($counter < $count_basket) ? "m43403064" : "m43401228") . '"><Data ss:Type="Number">' . $counter . '</Data></Cell>
    <Cell ss:MergeAcross="16" ss:StyleID="' . (($counter < $count_basket) ? "m43403074" : "m43401238") . '"><Data ss:Type="String">' . $this->unicode_russian($item["title"]) . '</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="' . (($counter < $count_basket) ? "m43403084" : "m43401248") . '"><Data ss:Type="String">' . $item["quantity"] . '</Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="' . (($counter < $count_basket) ? "m43403094" : "m43401258") . '"><Data ss:Type="String">' . $this->unicode_russian('шт') . '</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="' . (($counter < $count_basket) ? "m43385108" : "m43385088") . '"><Data ss:Type="Number">' . number_format($item["price"], 2, ".", "") . '</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="' . (($counter < $count_basket) ? "m43385118" : "m43385098") . '"><Data ss:Type="Number">' . number_format($item["cost"], 2, ".", "") . '</Data></Cell>
   </Row>';
                }
                /*   <Row ss:AutoFitHeight="0" ss:Height="11.8125">
    <Cell ss:Index="2" ss:MergeAcross="1" ss:StyleID="s106"><Data ss:Type="Number">'.$counter++.'</Data></Cell>
    <Cell ss:MergeAcross="16" ss:StyleID="s107"><Data ss:Type="String">'.$this->unicode_russian($item["title"]).'</Data></Cell>
    <Cell ss:MergeAcross="2" ss:StyleID="s108"><Data ss:Type="String">'.$item["quantity"].'</Data></Cell>
    <Cell ss:MergeAcross="1" ss:StyleID="s110"><Data ss:Type="String">'.$this->unicode_russian('шт').'</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="s115"><Data ss:Type="Number">'.number_format($price_without_nds, 2, ".", "").'</Data></Cell>
    <Cell ss:MergeAcross="3" ss:StyleID="s116"><Data ss:Type="Number">'.number_format($cost_without_nds, 2, ".", "").'</Data></Cell>
   </Row>';*/
                $counter++;
            }
        }

        //		$totalPrice = $basketItems['totalPrice'];

        $buyers = '';
        if (!empty($data["last_name"]))
            $buyers = trim($data["last_name"]);
        if (!empty($buyers))
            $buyers .= " ";
        if (!empty($data["name"]))
            $buyers .= trim($data["name"]);
        if (!empty($buyers))
            $buyers .= " ";
        if (!empty($data["patr"]))
            $buyers .= trim($data["patr"]);

        $content = str_replace("{#buyers}", $this->unicode_russian($buyers), $content);
        $content = str_replace("{#date}", date('d.m.Y'), $content);
        $content = str_replace("{#date1}", date('d') . " " . $this->unicode_russian($this->getNameMonth(date('m'))) . " " . date('Y'), $content);
        $content = str_replace("{#totalPrice}", number_format($total_cost, 2, ".", ""), $content);
        $content = str_replace("{#totalPriceWithoutNDS}", number_format($total_cost / 6 * 5, 2, ".", ""), $content);
        $content = str_replace("{#NDS}", number_format(($total_nds), 2, ".", ""), $content);
        $content = str_replace("{#NDS_prop}", $this->unicode_russian($this->SumProp(number_format(($total_nds), 2, ".", ""))), $content);
        $content = str_replace("{#countItems}", $total_quantity, $content);
        $content = str_replace("{#orderId}", $this->addZero($xls_number, 4), $content);
        $content = str_replace("{#totalPrice}", $total_cost, $content);
        $content = str_replace("{#sumprop}", $this->unicode_russian($this->SumProp($total_cost)), $content);

        $content = str_replace("{#basket}", $basketXLS, $content);

        if ($data["type_face"] == 1) {
            $content = str_replace("{#rowcount}", (39 + $counter - 1), $content);
        } else {
            $content = str_replace("{#rowcount}", (32 + $counter - 1), $content);
        }

        $content = str_replace("{#expdate}", $this->unicode_russian("на протязі 3-х днів"), $content);

        fwrite($handle, $content);
        fclose($handle);

        //		die();

        return $targetFile;

    }

    function getNameMonth($month) {
        switch ((int)$month) {
            case 1:
            default:
                $ret = "Січня";
                break;
            case 2:
                $ret = "лютого";
                break;
            case 3:
                $ret = "березня";
                break;
            case 4:
                $ret = "квітня";
                break;
            case 5:
                $ret = "травня";
                break;
            case 6:
                $ret = "червня";
                break;
            case 7:
                $ret = "липня";
                break;
            case 8:
                $ret = "серпня";
                break;
            case 9:
                $ret = "вересня";
                break;
            case 10:
                $ret = "жовтня";
                break;
            case 11:
                $ret = "листопада";
                break;
            case 12:
                $ret = "грудня";
                break;
        }

        return $ret;
    }

    function unicode_russian($str) {
        $encode = "";
        for ($ii = 0; $ii < strlen($str); $ii++) {
            $xchr = substr($str, $ii, 1);
            if (ord($xchr) > 191) {
                $xchr = ord($xchr) + 848;
                $xchr = "&#" . $xchr . ";";
            }
            if (ord($xchr) == 168) {
                $xchr = "&#1025;"; //
            }
            if (ord($xchr) == 184) {
                $xchr = "&#1105;"; //
            }

            if (ord($xchr) == 179) { //і
                $xchr = "&#1110;";
            }

            if (ord($xchr) == 191) { //ї
                $xchr = "&#1111;";
            }

            if (ord($xchr) == 186) { //є
                $xchr = "&#1108;";
            }

            if (ord($xchr) == 178) { //І
                $xchr = "&#1030;";
            }

            if (ord($xchr) == 175) { //Ї
                $xchr = "&#1031;";
            }

            if (ord($xchr) == 170) { //Є
                $xchr = "&#1028;";
            }

            $encode = $encode . $xchr;
        }

        return $encode;
    }

    function Number($c) {
        $c = str_pad($c, 3, "0", STR_PAD_LEFT);
        //---------сотни
        switch ($c[0]) {
            case 0:
                $d[0] = "";
                break;
            case 1:
                $d[0] = "сто";
                break;
            case 2:
                $d[0] = "двісті"; //"двести";
                break;
            case 3:
                $d[0] = "триста";
                break;
            case 4:
                $d[0] = "чотириста"; //"четыреста";
                break;
            case 5:
                $d[0] = "п'ятьсот"; //"пятьсот";
                break;
            case 6:
                $d[0] = "шістьсот"; //"шестьсот";
                break;
            case 7:
                $d[0] = "сімсот"; //"семьсот";
                break;
            case 8:
                $d[0] = "вісімсот"; //"восемьсот";
                break;
            case 9:
                $d[0] = "дев'ятьсот"; //"девятьсот";
                break;
        }
        //--------------десятки
        switch ($c[1]) {
            case 0:
                $d[1] = "";
                break;
            case 1: {
                $e = $c[1] . $c[2];
                switch ($e) {
                    case 10:
                        $d[1] = "десять";
                        break;
                    case 11:
                        $d[1] = "одинадцять"; //"одиннадцать";
                        break;
                    case 12:
                        $d[1] = "дванадцять"; //"двенадцать";
                        break;
                    case 13:
                        $d[1] = "тринадцать";
                        break;
                    case 14:
                        $d[1] = "чотирнадцять"; // "четырнадцать";
                        break;
                    case 15:
                        $d[1] = "п'ятнадцять"; //"пятнадцать";
                        break;
                    case 16:
                        $d[1] = "шістнадцять"; //"шестнадцать";
                        break;
                    case 17:
                        $d[1] = "сімнадцять"; //"семнадцать";
                        break;
                    case 18:
                        $d[1] = "вісімнадцять"; //"восемнадцать";
                        break;
                    case 19:
                        $d[1] = "дев'ятнадцять"; //"девятнадцать";
                        break;
                };
            }
                break;
            case 2:
                $d[1] = "двадцять"; //"двадцать";
                break;
            case 3:
                $d[1] = "тридцять"; //"тридцать";
                break;
            case 4:
                $d[1] = "сорок";
                break;
            case 5:
                $d[1] = "п'ятдесят"; //"пятьдесят";
                break;
            case 6:
                $d[1] = "шістдесят"; //"шестьдесят";
                break;
            case 7:
                $d[1] = "сімдесят"; //"семьдесят";
                break;
            case 8:
                $d[1] = "вісімдесят"; //"восемьдесят";
                break;
            case 9:
                $d[1] = "дев'яносто"; //"девяносто";
                break;
        }
        //--------------единицы
        $d[2] = "";
        if ($c[1] != 1):
            switch ($c[2]) {
                case 0:
                    $d[2] = "";
                    break;
                case 1:
                    $d[2] = "одна"; //"один";
                    break;
                case 2:
                    $d[2] = "дві"; //"два";
                    break;
                case 3:
                    $d[2] = "три";
                    break;
                case 4:
                    $d[2] = "чотири"; //"четыре";
                    break;
                case 5:
                    $d[2] = "п'ять"; //"пять";
                    break;
                case 6:
                    $d[2] = "шість"; //"шесть";
                    break;
                case 7:
                    $d[2] = "сім"; //"семь";
                    break;
                case 8:
                    $d[2] = "вісім"; //"восемь";
                    break;
                case 9:
                    $d[2] = "дев'ять"; //"девять";
                    break;
            }
        endif;

        return $d[0] . ' ' . $d[1] . ' ' . $d[2];

    }

    function SumProp($sum) {
        // Проверка ввода
        $sum = str_replace(' ', '', $sum);
        $sum = trim($sum);
        if ((!(@eregi('^[0-9]*' . '[,\.]' . '[0-9]*$', $sum) || @eregi('^[0-9]+$', $sum))) || ($sum == '.') || ($sum == ',')) :
            return "Это не деньги: $sum";
        endif;
        // Меняем запятую, если она есть, на точку
        $sum = str_replace(',', '.', $sum);
        if ($sum >= 1000000000):
            return "Максимальная сумма &#151 один миллиард рублей минус одна копейка";
        endif;
        // Обработка копеек
        $rub = floor($sum);
        $kop = 100 * round($sum - $rub, 2);
        $kop .= " копійок";
        if (strlen($kop) == 6):
            $kop = "0" . $kop;
        endif;
        // Выясняем написание слова 'рубль'
        $one = substr($rub, -1);
        $two = substr($rub, -2);
        if ($two > 9 && $two < 21):
            $namerub = "гривень"; //"рублей";

        elseif ($one == 1):
            $namerub = "гривня"; //"рубль";

        elseif ($one > 1 && $one < 5):
            $namerub = " гривні"; //" рубля";

        else:
            $namerub = "гривень"; //"рублей";

        endif;
        if ($rub == "0"):
            return "Нуль гривень $kop"; //"Ноль рублей $kop";
        endif;
        //----------Сотни
        $sotni = substr($rub, -3);
        $nums = $this->Number($sotni);
        if ($rub < 1000):
            return ucfirst(trim("$nums $namerub $kop"));
        endif;
        //----------Тысячи
        if ($rub < 1000000):
            $ticha = substr(str_pad($rub, 6, "0", STR_PAD_LEFT), 0, 3);
        else:
            $ticha = substr($rub, strlen($rub) - 6, 3);
        endif;
        $one = substr($ticha, -1);
        $two = substr($ticha, -2);
        if ($two > 9 && $two < 21):

            $name1000 = " тисяч"; //" тысяч";
        elseif ($one == 1):

            $name1000 = " тисяча"; //" тысяча";
        elseif ($one > 1 && $one < 5):

            $name1000 = " тисячі"; //" тысячи";
        else:

            $name1000 = " тисяч"; //" тысяч";
        endif;
        $numt = $this->Number($ticha);
        if ($one == 1 && $two != 11):
            $numt = str_replace('один', 'одна', $numt);
        endif;
        if ($one == 2):
            $numt = str_replace('два', 'две', $numt);
            $numt = str_replace('двед', 'двад', $numt);
        endif;
        if ($ticha != '000'):
            $numt .= $name1000;
        endif;
        if ($rub < 1000000):
            return ucfirst(trim("$numt $nums $namerub $kop"));
        endif;
        //----------Миллионы
        $million = substr(str_pad($rub, 9, "0", STR_PAD_LEFT), 0, 3);
        $one = substr($million, -1);
        $two = substr($million, -2);
        if ($two > 9 && $two < 21):

            $name1000000 = " мілліонів"; //" миллионов";
        elseif ($one == 1):

            $name1000000 = " мілліон"; //" миллион";
        elseif ($one > 1 && $one < 5):

            $name1000000 = " мілліона"; //" миллиона";
        else:

            $name1000000 = " мілліонів"; //" миллионов";
        endif;
        $numm = $this->Number($million);
        $numm .= $name1000000;

        return ucfirst(trim("$numm $numt $nums $namerub $kop"));
    }

    function addZero($val, $totalCount) {
        $ret = '';
        $nowLength = strlen((string)$val);
        $ret = str_repeat("0", ($totalCount - $nowLength)) . $val;

        return $ret;
    }

}

?>