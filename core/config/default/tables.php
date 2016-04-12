<?php
	$this->tables = array();
	$this->tableColumn = array();

	$tableName = "structure";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["s_id"]				= "int(11) unsigned NOT NULL auto_increment";

		//	Названия для разных модулей

		$columns["title"]				= "varchar(255) NOT NULL default ''";
		$columns["title_menu"]			= "varchar(255) NOT NULL default ''";
		$columns["title_page"]			= "varchar(255) NOT NULL default ''";
		$columns["title_map"]			= "varchar(255) NOT NULL default ''";

		//	Мета данные

		$columns["meta_title"]			= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords"]		= "text NOT NULL";
		$columns["meta_description"]	= "text NOT NULL";

		$columns["level"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["alias"]				= "varchar(64) NOT NULL default ''";
		$columns["relative_url"]		= "varchar(255) NOT NULL default ''";

		$columns["parent"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["template_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["type"]				= "int(1) unsigned NOT NULL default '0'";
		$columns["enabled"]				= "int(1) unsigned NOT NULL default '0'";
		$columns["restricted"]			= "int(1) unsigned NOT NULL default '0'";
		$columns["access_level"]		= "int(11) NOT NULL default '0'";

		//	Где показывать

		$columns["show_map"]			= "int(1) unsigned NOT NULL default '1'";
		$columns["show_menu_top"]		= "int(1) unsigned NOT NULL default '1'";
		$columns["show_menu_top2"]		= "int(1) unsigned NOT NULL default '1'";
		$columns["show_menu_side"]		= "int(1) unsigned NOT NULL default '1'";
		$columns["show_menu_bottom"]	= "int(1) unsigned NOT NULL default '1'";

		$columns["redirect_url"]		= "varchar(255) NOT NULL default ''";
		$columns["cache_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["cache_lifetime"]		= "int(11) unsigned NOT NULL default '0'";
		$columns["creation_time"]		= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["modification_time"]	= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["language_id"]			= "int(11) unsigned NOT NULL";
		$columns["ord"]					= "int(11) unsigned NOT NULL";
		$columns["tags"]				= "varchar(255) NOT NULL default ''";
		$columns["count_visit"]			= "int(11) unsigned NOT NULL default '0'";

		$columns["primarykey"][]		= "s_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "toc";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["toc_id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["s_id"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["container"]			= "varchar(32) NOT NULL default ''";
		$columns["sort_order"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["handler"]				= "varchar(32) NOT NULL default ''";
		$columns["content_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["enabled"]				= "int(1) unsigned NOT NULL default '0'";
		$columns["tpl_alias"]			= "varchar(32) NOT NULL default ''";
		$columns["inheritable"]			= "int(1) unsigned NOT NULL default '0'";
		$columns["cache_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["primarykey"][]		= "toc_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "toc_inh_exclusions";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["toc_id"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["s_id"]				= "int(11) unsigned NOT NULL default '0'";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "templates";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["template_id"]			= "int(11) unsigned NOT NULL auto_increment";
		$columns["title"]				= "varchar(64) NOT NULL default ''";
		$columns["filename"]			= "varchar(128) NOT NULL default ''";
		$columns["containers"]			= "varchar(255) NOT NULL default ''";
		$columns["handlers_install"]	= "varchar(255) NOT NULL default ''";
		$columns["handlers_init"]		= "varchar(255) NOT NULL default ''";
		$columns["handlers"]			= "varchar(255) NOT NULL default ''";
		$columns["tpl_alias"]			= "varchar(32) NOT NULL default ''";
		$columns["modification_time"]	= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["primarykey"][]		= "template_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "users_iplog";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]					= "int(11) unsigned NOT NULL auto_increment";
		$columns["uid"]					= "int(11) unsigned NOT NULL default '0'";
		$columns["ip"]					= "varchar(15) NOT NULL default ''";
		$columns["login_time"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["last_refresh"]		= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["primarykey"][]		= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "users";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["uid"]					= "int(1) unsigned NOT NULL auto_increment";
		$columns["login"]				= "varchar(32) NOT NULL default ''";
		$columns["password"]			= "varchar(32) NOT NULL default ''";

		$columns["name"]				= "varchar(255) NOT NULL default ''";
		$columns["last_name"]			= "varchar(255) NOT NULL default ''";
		$columns["patr"]				= "varchar(255) NOT NULL default ''";
		$columns["birthday"]			= "date NOT NULL default '0000-00-00'";
		$columns["sex"]					= "int(3) NOT NULL default '0'";

		$columns["country"]				= "varchar(255) NOT NULL default ''";
		$columns["city"]				= "varchar(255) NOT NULL default ''";
		$columns["address"]				= "text NOT NULL default ''";
		$columns["email"]				= "varchar(255) NOT NULL default ''";
		$columns["phone"]				= "varchar(255) NOT NULL default ''";
		$columns["icq"]					= "varchar(255) NOT NULL default ''";
		$columns["skype"]				= "varchar(255) NOT NULL default ''";

		$columns["company"]				= "varchar(255) NOT NULL default ''";
		$columns["position"]			= "varchar(255) NOT NULL default ''";
		$columns["avatar"]				= "varchar(255) NOT NULL default ''";
		$columns["info"]				= "text NOT NULL default ''";

		$columns["subscribe"]			= "int(1) unsigned NOT NULL default '0'";

		$columns["reg_date"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["valid_account"]		= "int(1) unsigned NOT NULL default '0'";
		$columns["subscribe"]			= "int(1) unsigned NOT NULL default '0'";
		$columns["access_level"]		= "int(2) unsigned NOT NULL default '0'";

		$columns["site_access"]			= "text NOT NULL";
		$columns["cms_structure_access"]= "text NOT NULL";
		$columns["cms_plugins_access"]	= "varchar(255) NOT NULL default ''";

//		Избавиться
		$columns["member_of_groups"]	= "varchar(255) NOT NULL default ''";
		$columns["member_of_network"]	= "int(11) unsigned NOT NULL default '0'";     
		$columns["networks_access"]		= "varchar(255) NOT NULL default ''";     
//		Избавиться

		$columns["primarykey"][]		= "uid";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "user_sessions";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]					= "int(12) unsigned NOT NULL auto_increment";
		$columns["uid"]					= "int(5) unsigned NOT NULL default '0'";
		$columns["session_id"]			= "varchar(32) NOT NULL default ''";
		$columns["login_time"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["last_refresh"]		= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["last_forum"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";  
		$columns["ip"]					= "varchar(15) NOT NULL default ''";
		$columns["primarykey"][]		= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "user_permissions";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["permission_id"]		= "int(11) unsigned NOT NULL auto_increment";
		$columns["uid"]					= "int(5) unsigned NOT NULL default '0'";
		$columns["site_id"]				= "varchar(64) NOT NULL default ''";
		$columns["plugin"]				= "varchar(64) NOT NULL default ''";
		$columns["type"]				= "enum('site','plugin','action','structure','group') NOT NULL default 'site'";
		$columns["object"]				= "varchar(64) NOT NULL default ''";
		$columns["value"]				= "int(1) unsigned NOT NULL default '0'";
		$columns["primarykey"][]		= "permission_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "common_blocks";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["toc_id"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["title"]				= "varchar(255) NOT NULL default ''";
		$columns["parent"]				= "int(11) NOT NULL default '-1'";
		$columns["depth"]				= "int(11) NOT NULL default '-1'";
		$columns["template"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["handler"]				= "varchar(32) NOT NULL default ''";
		$columns["order_by"]			= "int(11) unsigned NOT NULL default '10'";
		$columns["limit_to"]			= "int(11) NOT NULL default '-1'";
		$columns["primarykey"][]		= "toc_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	                                                 
	$tableName = "content_html";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["content_id"]			= "int(11) unsigned NOT NULL auto_increment";
		$columns["title"]				= "varchar(128) NOT NULL default ''";
		$columns["announce"]			= "text NOT NULL";
		$columns["content"]				= "text NOT NULL";
		$columns["field1"]				= "varchar(255) NOT NULL default ''";
		$columns["field2"]				= "varchar(255) NOT NULL default ''";
		$columns["field3"]				= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]		= "content_id";
		$columns["fulltextkey"][]		= "content";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "languages";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["language_id"]			= "int(11) unsigned NOT NULL auto_increment";
		$columns["title"]				= "varchar(64) NOT NULL default ''";

		$columns["alias"]				= "varchar(64) NOT NULL default ''";
		$columns["enabled"]				= "int(1) unsigned NOT NULL";
		$columns["ord"]					= "int(11) unsigned NOT NULL";
		$columns["primarykey"][]		= "language_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "tags";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["tag_id"]			= "int(11) unsigned NOT NULL auto_increment";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "tag_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}	

	self::$_tables = $this->tables;
?>