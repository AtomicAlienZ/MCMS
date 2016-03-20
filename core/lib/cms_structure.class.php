<?php

class cms_structure {
    var $cms = 0;
    var $dbc = 0;

    function cms_structure(){

    }

    function get_relative_url($s_id){
        return $this->cms->encode_url($s_id);

        $query = 'SELECT s_id, left_key, right_key FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
		$result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        }
        $structure = $result->FetchRow();
        $query = 'SELECT s_id, alias FROM '.$this->cms->tables['structure'].' WHERE left_key <= '.$structure['left_key'].' AND right_key >= '.$structure['right_key'].' ORDER BY left_key';
		$result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
            return false;
        }
        $path = array();
        while ($row = $result->FetchRow()){
            $path[] = $row['alias'];
        }
		if (is_array($path) && count($path)>0){
			$url = implode('/', $path);
		} else {
			$url = '';
		}
        return $url;
    }

	function update_relative_url($s_id){
		$url = $this->cms->encode_url($s_id);

		if (empty($url)) {
//			return false;
		}
		
//		$query = 'UPDATE '.$this->cms->tables['structure'].' SET relative_url=CONCAT("'.addslashes($url).'", alias, "/") WHERE s_id='.$s_id;
		
		$query1 = 'SELECT '.$this->cms->tables['structure'].' alias WHERE s_id='.$s_id;
		$result1 = $this->dbc->Execute($query1);
		echo $result1;

		
		$query = 'UPDATE '.$this->cms->tables['structure'].' SET relative_url=CONCAT("'.addslashes($url).'", alias, "/") WHERE s_id='.$s_id;
		
//		echo $query."<br>";

		$result = $this->dbc->Execute($query);
		
		$templates = array();
		
		if (!$result) {
			trigger_error($this->dbc->ErrorMsg().'<br>Query: '.$query, E_USER_ERROR);
			return false;
		}
		
		return true;
	}

	function switch_node($s_id,$enabled = 0){
		return $this->switch_nodes(array($s_id),$enabled);
	}

	function switch_nodes($s_ids, $enabled = 0){
		if (!is_array($s_ids) || count($s_ids)==0 ) {
			return false;
		}
		if ($enabled) {
			/* enable item */
			while(list(,$id) = each($s_ids)){
				$id = (int) $id;

				$query = 'SELECT type, parent, language_id FROM '.$this->cms->tables['structure'].' WHERE s_id='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()<>1) {
					return false;
				}
				$item = $result->FetchRow();

				$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET enabled='.$enabled.' WHERE s_id='.$id;
				$update_result = $this->dbc->Execute($update_query);
				if (!$update_result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}

				if($item["type"]==1 && $item["parent"]==0) {
					$update_query = 'UPDATE '.$this->cms->tables['languages'].' SET enabled='.$enabled.' WHERE language_id='.$item["language_id"];
					$this->dbc->Execute($update_query);
				}

//Включаем всех

				$query = 'SELECT s_id FROM '.$this->cms->tables['structure'].' WHERE parent='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()>0) {
					$childs = array();
					while($items = $result->FetchRow()) {
						$childs[] = $items["s_id"];
					}
					$this->switch_nodes($childs, $enabled);
				}
			
			}
		}
		else {
			/* disable item and its childrens */
			while(list(,$id) = each($s_ids)){
				$id = (int) $id;
				$query = 'SELECT type, parent, language_id FROM '.$this->cms->tables['structure'].' WHERE s_id='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()<>1) {
					return false;
				}
				$item = $result->FetchRow();

				$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET enabled='.$enabled.' WHERE s_id='.$id;
				$update_result = $this->dbc->Execute($update_query);
				if (!$update_result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}

				if($item["type"]==1 && $item["parent"]==0) {
					$update_query = 'UPDATE '.$this->cms->tables['languages'].' SET enabled='.$enabled.' WHERE language_id='.$item["language_id"];
					$this->dbc->Execute($update_query);
				}
				
				$query = 'SELECT s_id FROM '.$this->cms->tables['structure'].' WHERE parent='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()>0) {
					$childs = array();
					while($items = $result->FetchRow()) {
						$childs[] = $items["s_id"];
					}
					$this->switch_nodes($childs, $enabled);
				}
			}
		}

		return true;
	}

	function switch_nodes1($s_ids, $enabled = 0){
		if (!is_array($s_ids) || count($s_ids)==0 ) {
			return false;
		}
		if ($enabled) {
			/* enable item */
			while(list(,$id) = each($s_ids)){
				$id = (int) $id;

				$query = 'SELECT type, enabled, right_key, left_key FROM '.$this->cms->tables['structure'].' WHERE s_id='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()<>1) {
					return false;
				}
				$item = $result->FetchRow();

				if ( ($item['right_key']-$item['left_key']==1) && ($item['type']==1) ) {
					continue;
				}
				$query = 'SELECT s_id FROM '.$this->cms->tables['structure'].' WHERE enabled=0 and left_key<'.$item['left_key'].' AND right_key>'.$item['right_key'].'';
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()>0) {
					continue;
				}
				$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET enabled='.$enabled.' WHERE s_id='.$id;
				$update_result = $this->dbc->Execute($update_query);
				if (!$update_result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
			}
		}
		else {
			/* disable item and its childrens */
			while(list(,$id) = each($s_ids)){
				$id = (int) $id;
				$query = 'SELECT left_key, right_key FROM '.$this->cms->tables['structure'].' WHERE s_id='.$id;
				$result = $this->dbc->Execute($query);
				if (!$result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
				elseif ($result->RecordCount()<>1) {
					return false;
				}
				$item = $result->FetchRow();
				$update_query = 'UPDATE '.$this->cms->tables['structure'].' SET enabled='.$enabled.' WHERE left_key>='.$item['left_key'].' and right_key<='.$item['right_key'];
				$update_result = $this->dbc->Execute($update_query);
				if (!$update_result) {
					$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
					return false;
				}
			}
		}

		return true;
	}

	function insert_node($parent, $item){
		$query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE s_id='.$item['parent'];
		$result = $this->dbc->Execute($query);
		if ($result->RecordCount()==0) {
			$parent = array(
				's_id'        => 0,
				'left_key'    => 0,
				'right_key'   => 0,
			);
		}
		else {
			$parent = $result->FetchRow();
		}
		if($item["s_id"]==$item["parent"]) {
			$insertLast = false;
		}
		else {
			$insertLast = true;
		}

		if($item['ord']=="last" || $insertLast===true) {
			$sql = "SELECT MAX(ord) as ord FROM ".$this->cms->tables["structure"]." WHERE parent='".$item['parent']."'";
			$results = $this->dbc->Execute($sql);
			$row = $results->FetchRow();
			$nowOrd = $row["ord"]+1;
		}
		elseif($item['ord']=="first") {
			$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE parent='".$item['parent']."'";
			$this->dbc->Execute($sql);
			$nowOrd = 1;
		}
		else {
			$sql = "SELECT ord FROM ".$this->cms->tables["structure"]." WHERE s_id='".$item["ord"]."' AND parent='".$item['parent']."'";
			$results = $this->dbc->Execute($sql);
			$row = $results->FetchRow();
			$nowOrd = $row["ord"]+1;

			$sql = "UPDATE ".$this->cms->tables["structure"]." SET ord=(ord+1) WHERE ord>='".$nowOrd."' AND parent='".$item['parent']."'";
			$results = $this->dbc->Execute($sql);
		}
		if ($item['type']==1) {
			$item['enabled'] = 0;
		}
		$item['enabled'] = 0;

		$insert = array(
//			'parent'              => $parent['s_id'],
			'parent'              => (int) $item['parent'],
			'level'               => $parent['level'] + 1,
			'alias'               => "'".trim($item['alias'])."'",
			'title'               => "'".trim(addslashes($item['title']))."'",
			'template_id'         => (int) $item['template_id'],
			'type'                => (int) $item['type'],
			'enabled'             => (int) $item['enabled'],
			'language_id'         => (int) $item["arg"]['language'],
			'ord'                => (int) $nowOrd,
			'creation_time'       => 'NOW()',
			'redirect_url'        => "'".trim(addslashes($item['redirect_url']))."'"
		);
		$insert_fields = '';
		$insert_values = '';
		$sql_sep = '';
		while (list($field, $value) = each($insert)) {
			$insert_fields .= $sql_sep.$field;
			$insert_values .= $sql_sep.$value;
			$sql_sep = ', ';
		}
		$query = 'INSERT INTO '.$this->cms->tables['structure'].' ('.$insert_fields.') VALUES('.$insert_values.')';
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$insertedId = $this->dbc->insert_id();
		$this->_install_handlers($insertedId, intval($item['template_id']));
		$this->update_relative_url($insertedId);
		return $insertedId;
	}

    function insert_root_node($parent, $item){
        $insert = array(
                      'parent'              => 0,
                      'left_key'            => $parent['right_key']+1,
                      'right_key'           => $parent['right_key']+2,
                      'level'               => 1,
                      'alias'               => "'".trim($item['alias'])."'",
                      'title'               => "'".trim(addslashes($item['title']))."'",
                      'template_id'         => (int) $item['template_id'],
                      'type'                => (int) $item['type'],
                      'creation_time'       =>'now()',
                      'modification_time'   =>'now()',
                      'redirect_url'        => "'".trim(addslashes($item['redirect_url']))."'"
                      );
        $insert_fields = '';
        $insert_values = '';
        $sql_sep = '';
        while (list($field, $value) = each($insert)) {
            $insert_fields .= $sql_sep.$field;
            $insert_values .= $sql_sep.$value;
            $sql_sep = ', ';
        }
        $query = 'INSERT INTO '.$this->cms->tables['structure'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $s_id = $this->dbc->insert_id();
        $this->_install_handlers($s_id, intval($item['template_id']));
        $this->update_relative_url($s_id);
        return $s_id;
    }

    function _install_handlers($s_id, $template_id){
        $query = 'DELETE FROM '.$this->cms->tables['toc'].' WHERE s_id='.$s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }

        $query = 'SELECT s_id, template_id FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $item = $result->FetchRow();

        $query = 'SELECT * FROM '.$this->cms->tables['templates'].' WHERE template_id='.$template_id;
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount()<>1) {
            return false;
        }
        $template = $result->FetchRow();
        $containers = str_replace(' ', '', $template['handlers_install']);
        $containers = str_replace('*', '', $template['handlers_install']);
        $containers = explode(',', $containers);
        while (list(, $container) = each($containers)) {
            if (empty($container)) {
                continue;
            }
            $container_name = substr($container, 0, strpos($container, '('));
            $handlers = substr($container, strpos($container, '(')+1, strpos($container, ')')-strpos($container, '(')-1);
            $handlers = explode('|', $handlers);
            while (list(, $handler) = each($handlers)) {
                if (empty($handler)) {
                    continue;
                }
                $toc = array(
                              's_id'    => $s_id,
                              'container'   	=> $container_name,
                              'handler'   		=> $handler
                              );
                $this->_install_handler($toc);
            }
        }
        return true;
    }

    function get_locked_handlers($handlers_install, $tpl_containers){
        $locked = array_flip($tpl_containers);
        $containers = str_replace(' ', '', $handlers_install);
        $containers = explode(',', $containers);
        while (list(, $container) = each($containers)) {
            if (empty($container)) {
                continue;
            }
            $container_name = substr($container, 0, strpos($container, '('));
            $locked[$container_name] = array();
            $handlers = substr($container, strpos($container, '(')+1, strpos($container, ')')-strpos($container, '(')-1);
            $handlers = explode('|', $handlers);
            while (list(, $handler) = each($handlers)) {
                if ($handler[0]=='*') {
                    $handler = substr($handler, 1);
                    $locked[$container_name][$handler] = true;
                }
            }
        }
        return $locked;
    }

    function get_containers($c_str){
        $containers = str_replace(' ', '', $c_str);
        $containers = explode(',', $containers);
        while(list($id,$c_alias)=each($containers)){
            if (empty($c_alias)) {
                unset($containers[$id]);
            }
        }
        return $containers;
    }

    function _install_handler($toc){
		if (empty($this->cms->handlers[$toc['handler']])) {
            trigger_error('Plugin interface failed, undefined handler: '.$toc['handler'], E_USER_WARNING);
			return false;
		}

        $insert = array(
                      's_id'    => $toc['s_id'],
                      'container'   	=> "'".addslashes($toc['container'])."'",
                      'handler'   		=> "'".addslashes($toc['handler'])."'"
                      );
        $insert_fields = '';
        $insert_values = '';
        $sql_sep = '';
        while (list($field, $value) = each($insert)) {
            $insert_fields .= $sql_sep.$field;
            $insert_values .= $sql_sep.$value;
            $sql_sep = ', ';
        }
        $query = 'INSERT INTO '.$this->cms->tables['toc'].' ('.$insert_fields.') VALUES('.$insert_values.')';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $toc['toc_id']=$this->dbc->insert_id();
        /**/
		$handler = $this->cms->handlers[$toc['handler']];
        $informer_file = $handler['path'].'informer.class.php';
        if (file_exists($informer_file)) {
            $informer_class_name 		= $handler['name'].'_informer';
            if (!class_exists($informer_class_name)) {
                include_once($informer_file);
                if (!class_exists($informer_class_name)) {
                    trigger_error('Plugin interface failed, undefined class: '.$informer_class_name.' in '.basename($informer_file), E_USER_WARNING);
                    return false;
                }
            }

            $content_handler             = &new $informer_class_name();
            $content_handler->plugin     = $handler;
            $content_handler->cms  	 = &$this->cms;
            $content_handler->dbc        = &$this->cms->dbc;
            if (file_exists($handler['path'].'plugin.config.php')) {
                include_once($handler['path'].'plugin.config.php');
                $content_handler->config = $plugin_config;
            }
            return $content_handler->install($toc);
        } else {
            trigger_error('Plugin interface failed, file not exists: '.basename($handler_file), E_USER_WARNING);
            return false;
        }
        return true;
    }

	function delete_node($s_id) {
		$query = 'SELECT s_id, ord, language_id, parent FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
//		echo $query."<br>";
//		die($query);
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount()<>1) {
			return false;
		}
		$item = $result->FetchRow();

		$query = 'SELECT s_id FROM '.$this->cms->tables['structure'].' WHERE parent='.$item['s_id'];
//		echo $query."<br>";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		elseif ($result->RecordCount()>0) {
			while($child = $result->FetchRow()) {
				$this->delete_node($child["s_id"]);
			}
		}

		$query = 'DELETE FROM '.$this->cms->tables['structure'].' WHERE s_id='.$s_id;
//		echo $query."<br>";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}
		$sql = "UPDATE ".$this->cms->tables['structure']." SET ord=(ord-1) WHERE ord>='".$item["ord"]."' AND parent='".$item['parent']."'";
		$result = $this->dbc->Execute($sql);
//		echo $sql."<br>";

		/*        */
        /**/
        /* вибираєм з toc всі записи
        * і викликаєм відповідні handlers, щоб витерти контент */
        /**/
		$query = 'DELETE FROM '.$this->cms->tables['toc'].' WHERE s_id='.$item['s_id'].'';
//		echo $sql."<hr>";
		$result = $this->dbc->Execute($query);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
			return false;
		}

		return true;
    }

	function is_valid_alias($alias, $parent, $s_id = 0){
		if (preg_match('/^[a-zA-Z0-9_\-]{1,64}$/', $alias) ) {
			$query = 'SELECT * FROM '.$this->cms->tables['structure'].' WHERE parent='.$parent.' and alias="'.$alias.'"';
			if (!empty($s_id)) {
				$query = $query.' and s_id<>'.$s_id;
			}
			$result = $this->dbc->Execute($query);
			if (!$result) {
				$this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
				return false;
			}
			elseif ($result->RecordCount() == 0) {
				return true;
			}
			elseif ($result->RecordCount()>0) {
				return false;
			}
		}
		else {
			return false;
		}
    }


}

?>