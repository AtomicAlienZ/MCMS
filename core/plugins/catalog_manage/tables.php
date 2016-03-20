<?php

// Основная таблица со списком элементов каталога
$tableName = "catalog";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]								= "int(11) NOT NULL auto_increment";
	$columns["parent_id"]						= "int(11) NOT NULL default '0'";
	$columns["type"]							= "enum ('folder','item') NOT NULL default 'item'";

	$columns["alias"]	     					= "varchar(30) NOT NULL default ''";
	$columns["relative_url"]					= "varchar(255) NOT NULL default ''";
	$columns["full_relative_url"]				= "varchar(255) NOT NULL default ''";

	$columns["fieldset"]	     				= "varchar(30) NOT NULL default ''";

	// Генерируем зависимые от языка поля
	foreach($this->langs as $lang) {

		$columns["title_".$lang]				= "varchar(255) NOT NULL default ''";
		$columns["meta_title_".$lang]			= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords_".$lang]		= "varchar(255) NOT NULL default ''";
		$columns["meta_description_".$lang]		= "varchar(255) NOT NULL default ''";
		$columns["short_description_".$lang]	= "text NOT NULL";
		$columns["description_".$lang]			= "text NOT NULL";
		$columns["video_".$lang]				= "text NOT NULL";
	}

	$columns["img"]								= "varchar(255) NOT NULL default ''";
	$columns["img_sm"]							= "varchar(255) NOT NULL default ''";
	$columns["img_sm1"]							= "varchar(255) NOT NULL default ''";
	$columns["img_sm2"]							= "varchar(255) NOT NULL default ''";
	$columns["img_sm3"]							= "varchar(255) NOT NULL default ''";

	$columns["num_views"]						= "int(11) NOT NULL default '0'";
	$columns["num_orders"]						= "int(11) NOT NULL default '0'";
	$columns["price"]							= "float(8,2) NOT NULL default '0.0'";

	$columns["created"]							= "datetime NOT NULL default '0000-00-00 00:00:00'";
	$columns["ord"]								= "int(4) NOT NULL default '0'";
	$columns["enabled"]							= "int(1) NOT NULL default '0'";
	
	$columns["news"]							= "int(1) NOT NULL default '0'";
	$columns["best"]							= "int(1) NOT NULL default '0'";
	$columns["action"]							= "int(1) NOT NULL default '0'";
	$columns["recomended"]						= "int(1) NOT NULL default '0'";

	$columns["words"]							= "varchar(255) NOT NULL default ''";

	$columns["state"]							= "int(4) NOT NULL default '0'";
	//	0 - имеется в наличии
	//	1 - нет в наличии
	//	2 - ожидается

	$columns["code"]							= "varchar(255) NOT NULL default ''";
	$columns["count_last"]						= "int(11) NOT NULL default '0'";
	$columns["action_for_user"]					= "int(11) NOT NULL default '0'";

	$columns["primarykey"][]					= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица определяющая местонахождение блоков каталога в структуре
$tableName = "catalog_structure";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["s_id"]			= "int(11) NOT NULL default '0'";
	$columns["toc_id"]			= "int(11) NOT NULL default '0'";
	$columns["record_id"]		= "int(11) NOT NULL default '0'";
	$columns["main_flag"]		= "int(1) NOT NULL default '0'";
	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица со списком наборов полей
$tableName = "catalog_fieldsets";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["id_text"]			= "varchar(30) NOT NULL default ''";
	$columns["title"]			= "varchar(255) NOT NULL default ''";
	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица со списком полей
$tableName = "catalog_fieldsets_data";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["id_text"]			= "varchar(30) NOT NULL default ''";
	$columns["fieldset"]		= "varchar(30) NOT NULL default ''";
	$columns["title"]			= "varchar(255) NOT NULL default ''";
	$columns["type"]			= "varchar(30) NOT NULL default ''";
	$columns["options"]			= "varchar(255) NOT NULL default ''";
	$columns["show_list_flag"]	= "int(1) NOT NULL default '0'";
	$columns["show_view_flag"]	= "int(1) NOT NULL default '0'";
	$columns["ord"]	    		= "int(4) NOT NULL default '0'";
	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица хранящая списки выборов
$tableName = "catalog_selects";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["id_text"]			= "varchar(30) NOT NULL default ''";
	$columns["title"]			= "varchar(255) NOT NULL default ''";
	$columns["menu_flag"]		= "int(1) NOT NULL default '0'";
	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

/*

// Формат автоматически генерируемой таблицы для хранения данных

$tableName = "catalog_table_".$fieldset_id_text;
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["record_id"]		= "int(11) NOT NULL default '0'"

	..............................................................

	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Формат автоматически генерируемой таблицы для хранения списков выбора

$tableName = "catalog_select_".$select_id_text;
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]						= "int(11) NOT NULL auto_increment";

	// Генерируем зависимые от языка поля
	foreach($this->langs as $lang) {

		$columns["title_".$lang]		= "varchar(255) NOT NULL default ''";
	}

	$columns["image"]					= "varchar(255) NOT NULL default ''"
	$columns["ord"]						= "int(4) NOT NULL default '0'";
	$columns["enabled"]					= "int(1) NOT NULL default '0'";
	$columns["primarykey"][]			= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Допустимые типы полей при генерации таблицы

number      = "int(11) NOT NULL default '0'"
float       = "float(8,2) NOT NULL default '0.0'"
string      = "varchar(255) NOT NULL default ''"
text        = "text NOT NULL"
html        = "text NOT NULL"
image       = "varchar(255) NOT NULL default ''"
select      = "varchar(255) NOT NULL default ''"
multiple    = "varchar(255) NOT NULL default ''"
date        = "date NOT NULL default '0000-00-00'"

// Способ кодирования данных для разных типов полей

number      = целое число
float       = вещественное число
string      = обычная строка символов
text        = обычная строка символов
html        = обычная строка символов
img         = абсолютный путь к большому изображению
img_sm      = абсолютный путь к уменьшенной версии изображения
img_sm1     = абсолютный путь ко второй уменьшенной версии изображения
select      = идентификатор выбранного пункта (3)
multiple    = список идентификаторов, разделенный вертикальной чертой (1|4|5)
date        = дата в стандартном формате

Для типов полей "string", "text" и "html" генерируются поля для всех поддерживаемых языков

*/

// Таблица со списком прикрепленных файлов
$tableName = "catalog_files";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]						= "int(11) NOT NULL auto_increment";
	$columns["record_id"]				= "int(11) NOT NULL default '0'";

	// Генерируем зависимые от языка поля
	foreach($this->langs as $lang) {

		$columns["title_".$lang]		= "varchar(255) NOT NULL default ''";
		$columns["description_".$lang]	= "text NOT NULL";
	}

	$columns["url"]						= "varchar(255) NOT NULL default ''";
	$columns["filename"]				= "varchar(255) NOT NULL default ''";
	$columns["created"]					= "datetime NOT NULL default '0000-00-00 00:00:00'";
	$columns["ord"]						= "int(4) NOT NULL default '0'";
	$columns["enabled"]					= "int(1) NOT NULL default '0'";
	$columns["primarykey"][]			= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица со списком прикрепленных изображений
$tableName = "catalog_gallery";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]						= "int(11) NOT NULL auto_increment";
	$columns["record_id"]				= "int(11) NOT NULL default '0'";

	// Генерируем зависимые от языка поля
	foreach($this->langs as $lang) {

		$columns["title_".$lang]		= "varchar(255) NOT NULL default ''";
		$columns["description_".$lang]	= "text NOT NULL";
	}
        $columns["meta_title"]					= "varchar(255) NOT NULL default ''";
        $columns["meta_description"]				= "varchar(255) NOT NULL default ''";
        $columns["meta_keywords"]				= "varchar(255) NOT NULL default ''";
	$columns["img"]						= "varchar(255) NOT NULL default ''";
	$columns["img_sm"]					= "varchar(255) NOT NULL default ''";
	$columns["img_sm1"]					= "varchar(255) NOT NULL default ''";
	$columns["img_sm2"]					= "varchar(255) NOT NULL default ''";
	$columns["img_sm3"]					= "varchar(255) NOT NULL default ''";
	$columns["created"]					= "datetime NOT NULL default '0000-00-00 00:00:00'";
	$columns["ord"]						= "int(4) NOT NULL default '0'";
	$columns["enabled"]					= "int(1) NOT NULL default '0'";
	$columns["primarykey"][]			= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// Таблица хранящая список опций каталога
$tableName = "catalog_options";
$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
if($this->tables[$tableName]) {
	$columns = array();
	$columns["id"]				= "int(11) NOT NULL auto_increment";
	$columns["name"]			= "varchar(50) NOT NULL default ''";
	$columns["value"]			= "varchar(255) NOT NULL default ''";
	$columns["primarykey"][]	= "id";
	$this->tableColumn[$this->tables[$tableName]] = $columns;
}

// ........................................ Таблицы, который формируются в режиме интернет-магазина

if (!empty($this->shop_flag)) {

	// Таблица хранящая список элементов в корзине
	$tableName = "catalog_baskets";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["basket_id"]		= "int(11) NOT NULL default '0'";
		$columns["item_id"]			= "int(11) NOT NULL default '0'";
		$columns["quantity"]		= "int(4) NOT NULL default '0'";
		$columns["created"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["additional"]		= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	// Таблица хранящая список заказов
	$tableName = "catalog_orders";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["last_name"]		= "varchar(255) NOT NULL default ''";
		$columns["name"]		= "varchar(255) NOT NULL default ''";
		$columns["patr"]			= "varchar(255) NOT NULL default ''";
		$columns["country"]			= "varchar(255) NOT NULL default ''";
		$columns["city"]			= "varchar(255) NOT NULL default ''";
		$columns["address"]			= "varchar(255) NOT NULL default ''";
		$columns["phone"]			= "varchar(255) NOT NULL default ''";
		$columns["email"]			= "varchar(255) NOT NULL default ''";
		$columns["delivery"]		= "int(2) NOT NULL default '0'";
		$columns["type_pay"]		= "int(2) NOT NULL default '0'";
		$columns["webmoney"]		= "varchar(50) NOT NULL default ''";
		$columns["type_face"]		= "int(2) NOT NULL default '0'";
		$columns["action_code"]		= "varchar(50) NOT NULL default ''";
		$columns["created"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["comments"]		= "text NOT NULL";
		$columns["items"]			= "text NOT NULL";
		$columns["uid"]				= "int(11) NOT NULL default '0'";
		$columns["state"]			= "int(4) NOT NULL default '0'";
		$columns["active"]			= "int(1) NOT NULL default '0'";
		$columns["price"]			= "float(11,2) NOT NULL default '0.0'";
		$columns["price_delivery"]	= "float(11,2) NOT NULL default '0.0'";
		$columns["total_quantity"]	= "int(11) NOT NULL default '0'";
		$columns["currency"]		= "int(11) NOT NULL default '0'";
		$columns["xls_number"]		= "int(11) NOT NULL default '0'";
		$columns["link_file_order"]	= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	// Таблица хранящая список заказов
	$tableName = "catalog_preorders";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["record_id"]		= "int(11) NOT NULL default '0'";
		$columns["last_name"]		= "varchar(255) NOT NULL default ''";
		$columns["name"]		    = "varchar(255) NOT NULL default ''";
		$columns["patr"]			= "varchar(255) NOT NULL default ''";
		$columns["country"]			= "varchar(255) NOT NULL default ''";
		$columns["city"]			= "varchar(255) NOT NULL default ''";
		$columns["phone"]			= "varchar(255) NOT NULL default ''";
		$columns["email"]			= "varchar(255) NOT NULL default ''";
		$columns["created"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["comments"]		= "text NOT NULL";
		$columns["uid"]				= "int(11) NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	// Таблица хранящая список заказов
	$tableName = "catalog_orders_items";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["order_id"]		= "int(11) NOT NULL default '0'";
		$columns["item_id"]			= "int(11) NOT NULL default '0'";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["quantity"]		= "int(4) NOT NULL default '0'";
		$columns["price"]			= "float(11,2) NOT NULL default '0.0'";
		$columns["additional"]		= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "catalog_comments";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["catalog_comments_id"]		= "int(11) unsigned NOT NULL auto_increment";
		$columns["catalog_id"]				= "int(11) NOT NULL default '0'";
		$columns["fio"]						= "varchar(255) NOT NULL default ''";
		$columns["comments"]				= "text";
		$columns["enabled"]					= "enum('0','1') NOT NULL default '0'";
		$columns["dates"]					= "datetime default '0000-00-00 00:00:00'";
		$columns["primarykey"][]			= "catalog_comments_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "catalog_currency";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["currency_id"]		= "int(11) unsigned NOT NULL auto_increment";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["ratio"]			= "float(11, 2) NOT NULL default '0.00'";
		$columns["ord"]				= "int(11) NOT NULL default '0'";
		$columns["primarykey"][]	= "currency_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "catalog_like";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["from_id"]			= "int(11) NOT NULL default '0'";
		$columns["to_id"]			= "int(11) NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}


}
?>