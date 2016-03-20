<?php

	$tableName = "words";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["word_id"]			= "int(11) NOT NULL auto_increment";
		$columns["word_ru"]			= "varchar(255) NOT NULL default ''";
		$columns["word_ua"]			= "varchar(255) NOT NULL default ''";
		$columns["word_en"]			= "varchar(255) NOT NULL default ''";
		$columns["count_view_ru"]	= "int(11) NOT NULL default '0'";
		$columns["count_view_ua"]	= "int(11) NOT NULL default '0'";
		$columns["count_view_en"]	= "int(11) NOT NULL default '0'";
		$columns["enabled"]			= "int(1) NOT NULL default '0'";
		$columns["primarykey"][]	= "word_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}


?>