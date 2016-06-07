<?php
class cmsLanguages extends cms_core{
	function cmsLanguages() {
	}
	function getListEncoding() {
		$ret = array();
		$ret["��������"] = array(
			"windows-1251"	=> "Windows 1251",
			"ISO-8859-5"	=> "ISO-8859-5",
		);
		$ret["�������"] = array(
			"windows-1252"	=> "Windows 1252",
			"ISO-8859-1"	=> "ISO-8859-1",
		);
		$ret["������������������"] = array(
			"windows-1250"	=> "Windows 1250",
			"ISO-8859-2"	=> "ISO-8859-2",
		);
		$ret["���������"] = array(
			"windows-1253"	=> "Windows 1253",
			"ISO-8859-7"	=> "ISO-8859-7",
		);
		$ret["UTF-8"] = "������";
		return $ret;
	}
//	function initLanguages(&$parent) {
//	}
	function getAllLanguages() {
		$sql = "SELECT * FROM ".$this->cms->tables["languages"]." ORDER BY ord ASC";
		$results = $this->dbc->Execute($sql);
		$ret = array();
		if($results->RecordCount()>0) {
			while($row=$results->FetchRow()) {
				$ret[] = $row;
			}
		}
		return $ret;
	}
}
?>