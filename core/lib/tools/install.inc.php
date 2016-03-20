<?php
class InstallDB {
	var $tables = array();
	var $tablesColumn = array();
	var $dbc;
	
	function InstallDB() {
//		$this->SetStartInfo();
//		$this->CreateTable();
//		$this->installDefaultValue();
//		$this->AddField();
//		$this->DropField();
//		$this->ChangeField();
	}
	function runInstall() {
		$this->CreateTable();
		$this->AddField();
//		$this->DropField();
//		$this->ChangeField();
		$this->installDefaultValue();
	}
	function SetStartInfo() {
		$this->SetUsedTables();
		$this->SetColumnsInTables();
	}
	function SetUsedTables() {
/*
		$this->tables[] = "default";
	*/
	}
	function SetColumnsInTables() {
/*
		$this->tablesColumn["default"]["default_id"] = "int(11) NOT NULL auto_increment";
		$this->tablesColumn["default"]["createdate"] = "datetime NOT NULL";
		$this->tablesColumn["default"]["modifieddate"] = "datetime NULL";
		$this->tablesColumn["default"]["module"] = "varchar(50) NOT NULL";
		$this->tablesColumn["default"]["defaulttext"] = "text NULL";
		$this->tablesColumn["default"]["current"] = "int(3) NULL";
		$this->tablesColumn["default"]["primarykey"][] = "default_id";
*/
	}

	function installDefaultValue() {
//		echo md5('password');
		if(isset($this->tables["users"]) && $this->tables["users"]) {
			$sql = "SELECT COUNT(*) as counts FROM ".$this->tables["users"]."";
			$results = $this->dbc->Execute($sql);
			$row = $results->FetchRow();
			if ($row["counts"]==0) {
				$sql = "INSERT INTO ".$this->tables["users"]." SET uid=1, login='admin', `password`='".md5('password')."', email='', name='KAI', reg_date=NOW(), valid_account=1, access_level=90";
				$this->dbc->Execute($sql);
			}
		}
		if(isset($this->tables["templates"]) && $this->tables["templates"]) {
			$sql = "SELECT COUNT(*) as counts FROM ".$this->tables["templates"]."";
			$results = $this->dbc->Execute($sql);
			$row = $results->FetchRow();
			if ($row["counts"]==0) {
				$sql = "INSERT INTO ".$this->tables["templates"]." SET `title`='default', `filename`='default', `containers`='left, main, right, footer', `handlers_install`='main(html_editor)', `handlers_init`='', `handlers`='main_menu', `tpl_alias`='default', `modification_time`=NOW()";
				$this->dbc->Execute($sql);
				$sql = "INSERT INTO ".$this->tables["templates"]." SET `title`='index', `filename`='index', `containers`='left, main, right, footer', `handlers_install`='', `handlers_init`='', `handlers`='main_menu', `tpl_alias`='index', `modification_time`=NOW()";
				$this->dbc->Execute($sql);
			}
		}
	}

	function CreateTable() {
//		echo "<pre>"; print_r($this->tables); echo "</pre>";
//		echo "<pre>"; print_r($this->tablesColumn); echo "</pre>";
//echo "<pre>"; print_r(get_class_methods($this)); echo "</pre>";
//echo "<pre>"; print_r(get_object_vars($this)); echo "</pre>";
		foreach($this->tables as $name) {
			if(isset($this->tablesColumn[$name]) && count($this->tablesColumn[$name])>1) {
				$sql = "CREATE TABLE IF NOT EXISTS ".$name;
				$i = 0;
				foreach($this->tablesColumn[$name] as $column => $type) {
					if ($column != "primarykey" && $column != "fulltextkey" && $column != "uniquekey" && $column != "key") {
						if ($i++ > 0) $sql .= ", ";
						else $sql .= " (";
						$sql .= "`".$column."` ".$type;
					}
				}
				if(isset($this->tablesColumn[$name]["primarykey"]) && count($this->tablesColumn[$name]["primarykey"])>0) {
					$sql .= ", PRIMARY KEY(";
					$j = 0;
					foreach((array)$this->tablesColumn[$name]["primarykey"] as $column) {
						if ($j++ > 0) $sql .= ", ".$column;
						else  $sql .= $column;
					}
					$sql .= ")";
				}
				if(isset($this->tablesColumn[$name]["uniquekey"]) && count($this->tablesColumn[$name]["uniquekey"])>0) {
					$sql .= "";
					$j = 0;
					foreach((array)$this->tablesColumn[$name]["uniquekey"] as $column) {
						$sql .= ", UNIQUE KEY ".$column." (".$column.")";
					}
				}
				if(isset($this->tablesColumn[$name]["fulltextkey"]) && count($this->tablesColumn[$name]["fulltextkey"])>0) {
					$sql .= ", FULLTEXT KEY(";
					$j = 0;
					foreach((array)$this->tablesColumn[$name]["fulltextkey"] as $column) {
						if ($j++ > 0) $sql .= ", ".$column;
						else  $sql .= $column;
					}
					$sql .= ")";
				}
				if(isset($this->tablesColumn[$name]["key"]) && count($this->tablesColumn[$name]["key"])>0) {
					$j = 0;
					foreach((array)$this->tablesColumn[$name]["key"] as $column) {
						$sql .= ", KEY ".$column;
					}
				}
				$sql .= ") DEFAULT CHARACTER SET cp1251";
//				echo $sql."<br><br>";
				$results = $this->dbc->Execute($sql);
//				if(!$results) echo "<h1>".$sql."</h1>";
			}
		}
	}
	function AddField() {
		global $SystemConnect;
		foreach($this->tables as $name) {
			$columnsInDB = $this->GetFields($name);
			foreach($this->tablesColumn[$name] as $column => $type) {
				if ($column != "primarykey" && !array_search($column, $columnsInDB)) {
					$sql = "ALTER TABLE ".$name." ADD COLUMN ".$column." ".$type."";
					$this->dbc->Execute($sql);
				}
			}
		}
	}
	function DropField() {
		global $SystemConnect;
		foreach($this->tables as $name) {
			$i = 1;
			foreach($this->tablesColumn[$name] as $column => $type) {
				if ($column != "primarykey")
					$columnsInArray[$i++] = $column;
			}
			$columnsInDB = $this->GetFields($name);
			foreach ($columnsInDB as $column) {
				if (!array_search($column, $columnsInArray)) {
					$sql = "ALTER TABLE ".$name." DROP COLUMN ".$column."";
					$this->dbc->Execute($sql);
				}
			}
		}
	}
	function ChangeField() {
		global $SystemConnect;
		foreach($this->tables as $nameTable) {
			$columnsInfoInDB = $this->GetFullInfoFields($nameTable);
			foreach($this->tablesColumn[$nameTable] as $column => $type) {
				if ($column != "primarykey") {
					$sql = "ALTER TABLE ".$nameTable." MODIFY COLUMN ".$column." ".$type."";
					$this->dbc->Execute($sql);
				}
			}
		}
	}
	function GetFields($table) {
		global $SystemConnect;
		$sql = "SHOW COLUMNS from ".$table;
//		echo $sql."<br>";
		$results = $this->dbc->Execute($sql);
		$i=0;
		while($row = $results->FetchRow()) {
			$columns[$i+1] = $row['Field'];
			$i++;
		}
		return $columns;
	}
	function GetFullInfoFields($table) {
		global $SystemConnect;
		$sql = "SHOW COLUMNS from ".$table;
		$results = $this->dbc->Execute($sql);
		$i=0;
		while($row = $results->FetchRow()) {
			$columns[$row['Field']]['type'] = $row['Type'];
			$columns[$row['Field']]['null'] = $row['Null'];
			$columns[$row['Field']]['key'] = $row['Key'];
			$columns[$row['Field']]['default'] = $row['Default'];
			$columns[$row['Field']]['extra'] = $row['Extra'];
			$i++;
		}
		return $columns;
	}
}
//$clInstallDB = new InstallDB;
?>