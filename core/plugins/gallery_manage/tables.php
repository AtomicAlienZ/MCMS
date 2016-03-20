<?php

	$tableName = "gallery";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["gallery_id"]			= "int(11) NOT NULL auto_increment";
		$columns["parent_id"]			= "int(11) NOT NULL default '0'";
		$columns["type"]				= "int(1) NOT NULL default '0'";	//	0 - раздел, 1 - фото

		$columns["alias"]				= "varchar(255) NOT NULL default ''";
		$columns["relative_url"]		= "varchar(255) NOT NULL default ''";

		$columns["title_ru"]			= "varchar(255) NOT NULL default ''";
		$columns["title_en"]			= "varchar(255) NOT NULL default ''";
		$columns["title_ua"]			= "varchar(255) NOT NULL default ''";

		$columns["meta_title_ru"]		= "varchar(255) NOT NULL default ''";
		$columns["meta_title_en"]		= "varchar(255) NOT NULL default ''";
		$columns["meta_title_ua"]		= "varchar(255) NOT NULL default ''";

		$columns["meta_keywords_ru"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords_en"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords_ua"]	= "varchar(255) NOT NULL default ''";

		$columns["meta_description_ru"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_description_en"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_description_ua"]	= "varchar(255) NOT NULL default ''";

		$columns["descr_ru"]			= "text NOT NULL default ''";
		$columns["descr_en"]			= "text NOT NULL default ''";
		$columns["descr_ua"]			= "text NOT NULL default ''";

		$columns["content_ru"]			= "text NOT NULL default ''";
		$columns["content_en"]			= "text NOT NULL default ''";
		$columns["content_ua"]			= "text NOT NULL default ''";

		$columns["img"]					= "varchar(255) NOT NULL default ''";
		$columns["img_sm"]				= "varchar(255) NOT NULL default ''";
		$columns["img_sm1"]				= "varchar(255) NOT NULL default ''";
		
		$columns["author"]				= "varchar(255) NOT NULL default ''";
		$columns["source"]				= "varchar(255) NOT NULL default ''";
		$columns["source_url"]			= "varchar(255) NOT NULL default ''";

		$columns["ord"]					= "int(11) NOT NULL default '0'";
		$columns["enabled"]				= "int(1) NOT NULL default '0'";

		$columns["created"]				= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["visited"]			= "int(10) unsigned NOT NULL DEFAULT '0'";
                $columns["words"]                       = "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]		= "gallery_id";

		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	
	$tableName = "gallery_options";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["name"]			= "varchar(30) NOT NULL default ''";
		$columns["value"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "gallery_structure";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["s_id"]			= "int(11) NOT NULL default '0'";
		$columns["toc_id"]			= "int(11) NOT NULL default '0'";
		$columns["record_id"]		= "int(11) NOT NULL default '0'";
		$columns["open_this"]		= "int(1) NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "gallery_comments";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["comment_id"]	= "int(5) unsigned NOT NULL auto_increment";
		$columns["user_id"]	= "varchar(255) NOT NULL default ''";
		$columns["gallery_id"]	= "int(11) NOT NULL";
		$columns["time"]	= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["comment"]	= "text NOT NULL";
		$columns["primarykey"][]= "comment_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
?>
