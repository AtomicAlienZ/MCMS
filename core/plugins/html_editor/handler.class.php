<?php
/**
* @package      phpcms
* @subpackage   plugins
*/
class html_editor_handler {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $cms_ai = '';


    function exec($toc) {
        //return false;
        /*
        if (!empty($this->page_info['virtuals'][0])) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: '.$this->page_info['structure']['url']);
            header('Connection: close');
            exit;
        }
        */
        
        if (empty($toc['tpl_alias'])) {
            $toc['tpl_alias'] = 'default';
        }

        $tpl_file = $this->plugin['path'].'tpls/'.$toc['tpl_alias'].'.tpl';
        $cache_id = $this->cms->site_id.'_'.$toc['toc_id'];

        $compile_id = $toc['cache_id'];
        if( $this->cms->smarty->caching && $this->cms->smarty->is_cached($tpl_file, $cache_id, $compile_id) ) {
            $output = $this->cms->smarty->fetch($tpl_file, $cache_id);
            return $output;
        } else {
        }
        
        $handler_result = array();            
        $query = 'SELECT title, content FROM '.$this->cms->tables['content_html'].' WHERE content_id='.$toc['content_id'];
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);        
            return false;
        } 
        $content = $result->FetchRow();

        if (!empty($toc['tpl_alias']) && file_exists($tpl_file) && is_readable($tpl_file)) {
            $content["test"] = 'test';
            $this->cms->smarty->assign(array('output'=>$content));
            $output = $this->cms->smarty->fetch($tpl_file, $cache_id);

        } else {
            $output = $content['content'];
        }

        return $output;
    }
    
}

?>
