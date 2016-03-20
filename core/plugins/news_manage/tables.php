<?php

	$tableName = "news";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["descr"]			= "text";
		$columns["content"]			= "text";
		$columns["img_orig"]		= "varchar(255) NOT NULL default ''";
		$columns["img"]				= "varchar(255) NOT NULL default ''";
		$columns["img_sm"]			= "varchar(255) NOT NULL default ''";
		$columns["img_sm1"]			= "varchar(255) NOT NULL default ''";
		$columns["img_sm2"]			= "varchar(255) NOT NULL default ''";
		$columns["enabled"]			= "int(1) NOT NULL default '0'";
		$columns["dates"]			= "date NOT NULL default '0000-00-00'";
		$columns["sourceName"]		= "varchar(255) NOT NULL default ''";
		$columns["sourceUrl"]		= "varchar(255) NOT NULL default ''";
		$columns["author"]			= "varchar(255) NOT NULL default ''";
		$columns["rubrics"]			= "varchar(255) NOT NULL default ''";
		$columns["tags"]			= "varchar(255) NOT NULL default ''";
		$columns["clicks"]			= "int(11) NOT NULL default '0'";
		$columns["auto_start"]		= "int(1) NOT NULL default '0'";
		$columns["dates_start"]		= "date NOT NULL default '0000-00-00'";
		$columns["dates_end"]		= "date NOT NULL default '0000-00-00'";

		$columns["meta_title"]		= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_description"]= "varchar(255) NOT NULL default ''";
		$columns["tags"]			= "varchar(255) NOT NULL default ''";
                $columns["words"]			= "varchar(255) NOT NULL default ''";


		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	$tableName = "news_gallery";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["news_id"]			= "int(11) NOT NULL default '0'";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["descr"]			= "text";

		$columns["meta_title"]		= "varchar(255) NOT NULL default ''";
		$columns["meta_keywords"]	= "varchar(255) NOT NULL default ''";
		$columns["meta_description"]= "varchar(255) NOT NULL default ''";

		$columns["img_orig"]		= "varchar(255) NOT NULL default ''";
		$columns["img"]				= "varchar(255) NOT NULL default ''";
		$columns["img_sm"]			= "varchar(255) NOT NULL default ''";
		$columns["img_sm1"]			= "varchar(255) NOT NULL default ''";
		$columns["img_sm2"]			= "varchar(255) NOT NULL default ''";
		$columns["enabled"]			= "int(1) NOT NULL default '0'";
		$columns["clicks"]			= "int(11) NOT NULL default '0'";

		$columns["ord"]				= "int(11) NOT NULL default '0'";

		$columns["created"]			= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["modified"]		= "datetime NOT NULL default '0000-00-00 00:00:00'";

		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	$tableName = "news_rubrics";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["rubric_id"]		= "int(11) NOT NULL auto_increment";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
        $columns["alias"]			= "varchar(255) NOT NULL default ''";
        $columns["title_menu"]			= "varchar(255) NOT NULL default ''";
        $columns["meta_title"]			= "varchar(255) NOT NULL default ''";
        $columns["meta_description"]			= "varchar(255) NOT NULL default ''";
        $columns["meta_keywords"]			= "varchar(255) NOT NULL default ''";
        $columns["description"]			= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "rubric_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

	$tableName = "news_options";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["name"]			= "varchar(30) NOT NULL default ''";
		$columns["value"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["value2"]			= "decimal(11, 4) NOT NULL default '0'";
		$columns["groups"]			= "int(3) unsigned NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	$tableName = "news_structure";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["s_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["toc_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["sorttype"]		= "varchar(255) NOT NULL default 'dates'";
		$columns["lim"]				= "int(11) unsigned NOT NULL default '5'";
		$columns["show_navi"]		= "int(1) NOT NULL default '0'";
		$columns["sorttype"]		= "varchar(255) NOT NULL default 'id'";
		$columns["block_id"]		= "int(11) NOT NULL default '0'";
		$columns["rubrics"]			= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	$tableName = "news_calendar";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["s_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["toc_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["block_id"]		= "int(11) NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	$tableName = "news_comments";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["comment_id"]	= "int(5) unsigned NOT NULL auto_increment";
		$columns["user_id"]	= "varchar(255) NOT NULL default ''";
		$columns["news_id"]	= "int(11) NOT NULL";
		$columns["time"]	= "datetime NOT NULL default '0000-00-00 00:00:00'";
		$columns["comment"]	= "text";
		$columns["primarykey"][]= "comment_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}

?>