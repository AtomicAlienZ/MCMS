<?php
/***********************************************************
* 
* Plugin:		pages map
*
* @package      phpcms
* @subpackage   plugins
*/
class sitemap_informer {
	var $plugin = array();
	var $config = array();
	var $dbc;
	var $cms = '';


	function exec($toc) {
		echo $query = 'SELECT * FROM '.$this->cms->tables['common_blocks'].' WHERE toc_id='.$toc['toc_id'];
		$result = $this->dbc->Execute($query);
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);		
			return false;
		}
		elseif ($result->RecordCount()==0) {
			return false;
		}
		$row = $result->FetchRow();
		$content = ''.$row['title'].'';
		return $content;
	}

}

?>