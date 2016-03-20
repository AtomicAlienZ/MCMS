<?
class html_editor_informer {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $mai = '';

    function exec($toc) {
        $query = 'SELECT title, SUBSTRING(content, 1, 500) as content FROM '.$this->cms->tables['content_html'].' WHERE content_id='.$toc['content_id'];
		$result = $this->dbc->Execute($query);
        if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);		
            return false;
        } else if ($result->RecordCount()==0) {
            return false;
        }
        $row = $result->FetchRow();
        $content = strip_tags($row['content']).'...';
        if (!empty($row['title'])) {
            $content = '<b>'.$row['title'].'</b> '.$content;
        }
        return $content;
    }

    function install($toc){
        return $this->_insert_item($toc['toc_id']);
    }    

    function _insert_item($toc_id){
        $insert = array(
                      'title'   => "''"
                      );
        $insert_fields = '';
        $insert_values = '';
        $sql_sep = '';
        while (list($field, $value) = each($insert)) {
            $insert_fields .= $sql_sep.$field;
            $insert_values .= $sql_sep.$value;
            $sql_sep = ', ';
        }       
        $query = 'INSERT INTO '.$this->cms->tables['content_html'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $content_id = $this->dbc->insert_id();
        $query = 'UPDATE '.$this->cms->tables['toc'].' SET content_id='.$content_id.' WHERE toc_id='.$toc_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        return $content_id;
    }
    
}

?>