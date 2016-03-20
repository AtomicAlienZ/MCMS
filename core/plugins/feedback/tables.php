<?php
	$tableName = "feedback";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["feedback_id"]		= "int(5) unsigned NOT NULL auto_increment";
		$columns["s_id"]			= "int(5) unsigned NOT NULL default '0'";
		$columns["toc_id"]			= "int(5) unsigned NOT NULL default '0'";
		$columns["enabled"]			= "int(1) unsigned NOT NULL default '0'";
		$columns["sort_order"]		= "int(2) unsigned NOT NULL default '0'";
		$columns["mail"]			= "varchar(255) NOT NULL default ''";
		$columns["primarykey"][]	= "feedback_id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
?>