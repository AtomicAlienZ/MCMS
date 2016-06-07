<?php                                                                        

	$tableName = "banners";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;  
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["zone_id"]			= "int(11) unsigned NOT NULL default '0'";   
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["file_url"]		= "varchar(255) NOT NULL default ''"; 
		$columns["preview"]			= "varchar(255) NOT NULL default ''"; 
		$columns["w_size"]			= "int(5) unsigned NOT NULL default '0'";  
		$columns["h_size"]			= "int(5) unsigned NOT NULL default '0'";          
		//$columns["h_size"]		= "int(5) unsigned NOT NULL default '0'";  
		$columns["preference"]		= "int(11) NOT NULL default '0'";
		$columns["type"]			= "int(1) unsigned NOT NULL default '0'";
		$columns["enabled"]			= "int(1) unsigned NOT NULL default '0'"; 
		////////////////////////////////////////////////////////////////////
		$columns["url"]				= "varchar(255) NOT NULL default ''";
		$columns["flash_menu"]		= "int(1) unsigned NOT NULL default '0'"; 
		$columns["transparent_b"]	= "int(1) unsigned NOT NULL default '0'";
		$columns["background"]		= "varchar(10) NOT NULL default ''"; 
		$columns["quality"]			= "int(1) unsigned NOT NULL default '0'";  
		$columns["totalShow"]		= "int(11) unsigned NOT NULL default '0'";  
		$columns["useTotalShow"]	= "int(1) unsigned NOT NULL default '0'"; 
		$columns["code"]			= "text NOT NULL default ''";  
		$columns["showAfterPage"]	= "int(1) unsigned NOT NULL default '0'"; 
		
		$columns["limitLife"]		= "int(1) unsigned NOT NULL default '0'"; 
		$columns["limitLifeStart"]	= "date NOT NULL default '0000-00-00'"; 
		$columns["limitLifeEnd"]	= "date NOT NULL default '0000-00-00'"; 

		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	/////////////////////
	// type - тип баннера
	// 1 - изображение
	// 2 - флэш 
	// 3 - код
	/////////////////////
	// quality - качество (только для флэш)
	// 1 - низкое
	// 2 - среднее
	// 3 - высокое
	/////////////////////
	
	$tableName = "banners_stat";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		$columns["banner_id"]		= "int(11) unsigned NOT NULL default '0'";
		$columns["operation"]		= "varchar(10) NOT NULL default ''";
		$columns["user_ip"]			= "varchar(50) NOT NULL default ''";  
		$columns["dates"]			= "datetime NOT NULL default '0000-00-00 00:00:00'"; 
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	$tableName = "banners_zones";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;  
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) NOT NULL auto_increment";
		$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["w_size"]			= "int(5) unsigned NOT NULL default '0'";  
		$columns["h_size"]			= "int(5) unsigned NOT NULL default '0'";          
		//$columns["h_size"]			= "int(5) unsigned NOT NULL default '0'";  
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	
	$tableName = "banners_group";
	$this->tables[$tableName] = PREFIX_TABLE."_".$tableName;
	if($this->tables[$tableName]) {
		$columns = array();
		$columns["id"]				= "int(11) unsigned NOT NULL auto_increment";
		//$columns["title"]			= "varchar(255) NOT NULL default ''";
		$columns["lim"]				= "int(11) unsigned NOT NULL default '0'";
		$columns["insert_mode"]		= "int(1) unsigned NOT NULL default '0'";  
		$columns["s_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["toc_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["zone_id"]			= "int(11) unsigned NOT NULL default '0'";
		$columns["primarykey"][]	= "id";
		$this->tableColumn[$this->tables[$tableName]] = $columns;
	}
	/////////////////////
	// insert_mode - метод вставки кода
	// 1 - HTML
	// 2 - JavaScript
	// 3 - iFrame
	/////////////////////

?>