<?php
define('PATH', cms_DATA_PATH.'backup/');
define('URL',  'backup/');
// Максимальное время выполнения скрипта в секундах
// 0 - без ограничений
define('TIME_LIMIT', 600);
// Ограничение размера данных доставаемых за одно обращения к БД (в мегабайтах)
// Нужно для ограничения количества памяти пожираемой сервером при дампе очень объемных таблиц
define('LIMIT', 1);

class plugin_admin_interface extends cms_plugin_admin {
    var $plugin = array();
    var $config = array();
    var $dbc;
    var $fob;
    var $cms = '';
    var $_cookie_sep = '-';
    var $supported_extensions = array('sql', 'gz', 'bz2');
    var $excluded_tables = array('admin_translate');


    function index($arguments) {
        $output = '';
        $variables['tabs'] = $this->_print_tabs('backup', $s_id);
        $variables['output'] = $this->_print_files(PATH);
        $output .= $this->cms->parse_template($variables, 'workarea');
        return $output;
    }


    function _print_tabs($active_tab='manage', $s_id=0) {
        setcookie ('pm_simtab', $active_tab,time()+3600*24, '');
        $arguments = array('s_id'=>$s_id);
        $tabs  = array(
            'backup' => '<a href="'.$this->cms->format_url($this->plugin['name'], 'index').'">Резервные копии БД</a>',
        );
        $output = $this->cms->int_add_tabs($tabs, $active_tab);
        return $output;
    }


    function _print_files($folder_path){
        $output = '';
        $files = array();
        $base_path = $folder_path;
        if ($handle = opendir($base_path)) {
            while (false !== ($file = readdir($handle))) {
                $file_path = $base_path.$file;
                $path_parts = pathinfo($file_path);
                if (!is_file($file_path) || !in_array($path_parts['extension'], $this->supported_extensions) ) {
                    continue;
                }
				$fileInfo = explode(".", $file);
				$nameFileInfo = explode("_", $fileInfo[0]);
				$cNFI = count($nameFileInfo);

				$dateTimeCreatedFile = substr($nameFileInfo[$cNFI-2], -2).".".substr($nameFileInfo[$cNFI-2], -4, 2).".".substr($nameFileInfo[$cNFI-2], 0, 4)." ".substr($nameFileInfo[$cNFI-1], 0, 2).":".substr($nameFileInfo[$cNFI-1], 2, 2).":".substr($nameFileInfo[$cNFI-1], -2);

                $item = array();
                $item['name'] = $file;
                $item['size'] = round(filesize($file_path) / 1024 / 1024, 3);
//                $item['ctime'] = date("Y-m-d H:i:s", filectime($file_path));
                $item['ctime'] = $dateTimeCreatedFile;
                $item['path'] = $file_path;
                $files[$nameFileInfo[$cNFI-2].$nameFileInfo[$cNFI-1]] = $item;
            }
            krsort($files);
        }
        if (count($files)==0) {
            $edit_url = $this->cms->format_url($this->plugin['name'], 'backup');
            $output .= '<p class="faded">Резервные копии отсутствуют</p><input type="button" class="button" value="Создать резервную копию" onclick="location.href=\''.$edit_url.'\'">';
            return $output;
        }
        
        $output .= '<table class="admin-table" width="100%">';
        $output .= '<tr class="th">';
        $output .= '<td width="150">Дата создания</td>';
        $output .= '<td>Имя файла</td>';
        $output .= '<td width="100">Размер файла</td>';
        $output .= '<td width="200">Операции</td>';
        $output .= '</tr>';
        
        /* files */
        while(list(,$item)=each($files)){
            $arguments = array('file'=>$item['name']);
            $actions = array(
                'download'  =>  '<a href="'.$this->cms->format_url($this->plugin['name'], 'download',  $arguments).'">скачать</a>',
                'restore'  =>  '<a href="'.$this->cms->format_url($this->plugin['name'], 'restore',    $arguments).'">восстановить</a>',
                'delete'   =>  '<a onclick="return confirm(\'Удалить файл?\')" href="'.$this->cms->format_url($this->plugin['name'], 'delete', $arguments).'">удалить</a>'
            );
            $output .= '<tr class="td">';
            $output .= '<td>'.stripslashes($item['ctime']).'</td>';
            $output .= '<td>'.stripslashes($item['name']).'</td>';
            $output .= '<td>'.stripslashes($item['size']).' МБ</td>';
            $output .= '<td><small>'.implode(' | ', $actions).'</small></td>';
            $output .= '</tr>';
        }
        $output .= '</table>';
        $edit_url = $this->cms->format_url($this->plugin['name'], 'backup');
        $output .= '<input type="button" class="button" value="Создать резервную копию" onclick="location.href=\''.$edit_url.'\'">';
        return $output;
    }


    function download($arguments){
        $file = $arguments['file'];
        $target = PATH.basename($file);
        if (!file_exists($target) || !is_file($target)) {
            return false;
        }
        //ob_end_clean();
        //ob_end_clean();
        $user_data = array();
        $length = filesize($target);
        ini_set("max_execution_time",3600*5);
        header("Content-Type: application/force-download\r\n");
        header("Content-Transfer-Encoding: binary\r\n");
        header("Content-Length: ".$length);
        header("Content-Disposition: attachment; filename=".basename($target)."\r\n");
        header("Connection: Keep-Alive\r\n");
        header("Pragma: no-cache\r\n");
        $chunk_size = 50*1024; //kb
        $chunks = ceil($length/($chunk_size))+1;
        $chunk = 0;
        $handle = fopen($target, "rb");
        do {
            $data = fread($handle, $chunk_size);
            $data_len = strlen($data);
            echo $data;
            flush();
        } while($data_len > 0);
        fclose ($handle);
        flush();
        exit;
    }


    function delete($arguments){
        $file = $arguments['file'];
        $filename = PATH.basename($file);
        if (file_exists($filename)) {
            unlink($filename);
        }
        //$this->cms->int_set_message('bottom', 'File '.$file.' was succesfully deleted.', $this->plugin['title'], 'information');
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: '.$target_url);
    }


    function restore($arguments) {
        $file = PATH.basename($arguments['file']);
        $message = '';
        $server_info = $this->dbc->ServerInfo();
        preg_match("/^(\d+)\.(\d+)\.(\d+)/", $server_info['version'], $m);
        $this->mysql_version = sprintf("%d%02d%02d", $m[1], $m[2], $m[3]);
        // Определение формата файла
        if(preg_match("/^(.+?)\.sql(\.(bz2|gz))?$/", $file, $matches)) {
            if (isset($matches[3]) && $matches[3] == 'bz2') {
                $this->SET['comp_method'] = 2;
            } elseif (isset($matches[2]) &&$matches[3] == 'gz') {
                $this->SET['comp_method'] = 1;
            } else{
                $this->SET['comp_method'] = 0;
            }
            $this->SET['comp_level'] = '';
            if (!file_exists($file)) {
                print tpl_l("ОШИБКА! Файл не найден!", C_ERROR);
                print tpl_enableBack();
                exit;
            }
        } else {
            print tpl_l("ОШИБКА! Не выбран файл!", C_ERROR);
            print tpl_enableBack();
            exit;
        }
        $fp = $this->fn_open($file, "r");
        $this->file_cache = $sql = $table = $insert = '';
        $is_skd = $query_len = $execute = $q =$t = $i = $aff_rows = 0;
        $limit = 300;
        $index = 4;
        $tabs = 0;
        $cache = '';
        $info = array();
        while(($str = $this->fn_read_str($fp)) !== false){
            if (empty($str) || preg_match("/^(#|--)/", $str)) {
                if (!$is_skd && preg_match("/^#SKD101\|/", $str)) {
                    $info = explode("|", $str);
                    $is_skd = 1;
                }
                continue;
            }
            $query_len += strlen($str);
            if (!$insert && preg_match("/^(INSERT INTO `?([^` ]+)`? .*?VALUES)(.*)$/i", $str, $m)) {
                if ($table != $m[2]) {
                    $table = $m[2];
                    $tabs++;
                    $i = 0;
                    if ($is_skd)
                    print tpl_s(100 , $t / $info[4]);
                }
                $insert = $m[1] . ' ';
                $sql .= $m[3];
                $index++;
                $info[$index] = isset($info[$index]) ? $info[$index] : 0;
                $limit = round($info[$index] / 20);
                $limit = $limit < 300 ? 300 : $limit;
                if ($info[$index] > $limit){
                    $cache = '';
                }
              } else {
                $sql .= $str;
                if ($insert) {
                            $i++;
                        $t++;
                            if ($is_skd && $info[$index] > $limit && $t % $limit == 0) {
                        //print tpl_s($i / $info[$index], $t / $info[4]);
                    }
                }
            }
            if (!$insert && preg_match("/^CREATE TABLE (IF NOT EXISTS )?`?([^` ]+)`?/i", $str, $m) && $table != $m[2]){
                $table = $m[2];
                $insert = '';
                $tabs++;
                $cache .= "Таблица `{$table}`.";
                $i = 0;
            }
            if ($sql) {
                if (preg_match("/;$/", $str)) {
                    $sql = rtrim($insert . $sql, ";");
                    if (empty($insert)) {
                        if ($this->mysql_version < 40101) {
                            $sql = preg_replace("/ENGINE\s?=/", "TYPE=", $sql);
                        }
                    }
                    $insert = '';
                    $execute = 1;
                }
                if ($query_len >= 65536 && preg_match("/,$/", $str)) {
                    $sql = rtrim($insert . $sql, ",");
                    $execute = 1;
                }
                if ($execute) {
                        $q++;
                    $query = $sql;
                    $result = $this->dbc->Execute($query);
                    if (!$result) {
                        $this->cms->int_set_message('top', $this->dbc->ErrorMsg(), 'SQL Error', 'error');
                        //return false;
                        }
                              if (preg_match("/^insert/i", $sql)) {
                            $aff_rows += mysql_affected_rows();
                        }
                        $sql = '';
                        $query_len = 0;
                        $execute = 0;
                }
            }
        }
        $message .= "БД успешно восстановлена из резервной копии.<br>";
        $message .= "Количество восстановленных таблиц: {$tabs}<br>";
        $message .= "Количество восстановленных записей: {$aff_rows}<br>";
        $message .= "Всего выполнено запросов к БД: {$q}<br>";
        $this->tabs = $tabs;
        $this->records = $aff_rows;
        $this->size = filesize($this->filename);
        $this->comp = $this->SET['comp_method'] * 10 + $this->SET['comp_level'];
        $str = base64_decode('aHR0cDovL3phcGltaXIubmV0L3N0YXRzLnBocD8=') . "r={$this->tabs},{$this->records},{$this->size},{$this->comp},106";
        $this->fn_close($fp);
        $this->cms->int_set_message('top', $message, 'Восстановление БД', 'information');
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: '.$target_url);
    }


    function backup() {
        $this->SET['last_action']     = 0;
        $this->SET['last_db_backup']  = '';
        $this->SET['tables_exclude']  = 0;
        $this->SET['tables']          = '';
        $this->SET['comp_method']     = 1;
        $this->SET['comp_level']      = 7;
        $this->SET['tables'] = array();
        foreach($this->excluded_tables AS $table){
            if (!empty($this->SET['tables'][$table])) {
                unset($this->SET['tables'][$table]);
            }
        }
        if (!empty($this->SET['tables'])) {
            foreach($this->SET['tables'] AS $table) {
            $table = preg_replace("/[^\w*?^]/", "", $table);
            $pattern = array( "/\?/", "/\*/");
            $replace = array( ".", ".*?");
            $tbls[] = preg_replace($pattern, $replace, $table);
            }
        } else {
            $this->SET['tables_exclude'] = 1;
        }
        if ($this->SET['comp_level'] == 0) {
            $this->SET['comp_method'] = 0;
        }
        $tables = array();
        $query = 'SHOW TABLES';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $all = 0;
        while($row = $result->FetchRow()) {
            $row = array_values($row);
            $status = 0;
            if (!empty($tbls)) {
                foreach($tbls AS $table) {
                    $exclude = preg_match("/^\^/", $table) ? true : false;
                    if (!$exclude) {
                        if (preg_match("/^{$table}$/i", $row[0])) {
                            $status = 1;
                        }
                        $all = 1;
                    }
                    if ($exclude && preg_match("/{$table}$/i", $row[0])) {
                        $status = -1;
                    }
                }
            } else {
                $status = 1;
            }
            if ($status >= $all) {
                $tables[] = $row[0];
            }
        }
        $tabs = count($tables);
        // Определение размеров таблиц
        $query = 'SHOW TABLE STATUS';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        $tabinfo = array();
        $tabinfo[0] = 0;
        $info = '';
        $this->size = 0;
        while ($item = $result->FetchRow()) {
            if (in_array($item['Name'], $tables)) {
                $item['Rows'] = empty($item['Rows']) ? 0 : $item['Rows'];
                $tabinfo[0] += $item['Rows'];
                $tabinfo[$item['Name']] = $item['Rows'];
                $this->size += $item['Data_length'];
                $tabsize[$item['Name']] = 1 + round(LIMIT * 1048576 / ($item['Avg_row_length'] + 1));
                if ($item['Rows']) $info .= "|" . $item['Rows'];
            }
        }
        $show = 10 + $tabinfo[0] / 50;
        $info = $tabinfo[0] . $info;
        $name = ROOT_DB_NAME . '_' . date("Ymd_His");
        $filename = $this->fn_filename($name);
        $fp = $this->fn_open($filename, "w");
        $this->fn_write($fp, "#SKD101|{".ROOT_DB_NAME."}|{$tabs}|" . date("Y.m.d H:i:s") ."|{$info}\n\n");
        $t=0;
        $query = 'SET SQL_QUOTE_SHOW_CREATE = 1';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        }
        foreach ($tables AS $table) {
            // Создание таблицы
            $query = "SHOW CREATE TABLE {$table}";
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
            $tab = $result->FetchRow();
            $tab = $tab['Create Table'];
            $tab = preg_replace('/(default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP|DEFAULT CHARSET=\w+|character set \w+|collate \w+)/i', '/*!40101 \\1 */', $tab);
            $this->fn_write($fp, "DROP TABLE IF EXISTS {$table};\n{$tab};\n\n");
            // Опредеделяем типы столбцов
            $NumericColumn = array();
            $field = 0;
            $query = "SHOW COLUMNS FROM {$table}";
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                return false;
            }
            $order_by_col = '';
            while($col = $result->FetchRow()) {
                if (empty($order_by_col)) {
                    $order_by_col = $col['Field'];
                } else if ($col['Key']=='PRI'){
                    $order_by_col = $col['Field'];
                }
                $NumericColumn[$field++] = preg_match("/^(\w*int|year)/", $col['Type']) ? 1 : 0;
            }
            $fields = $field;
            $from = 0;
            $limit = $tabsize[$table];
            $limit2 = round($limit / 3);
            if ($tabinfo[$table] > 0) {
                if ($tabinfo[$table] > $limit2) {}
                $i = 0;
                $this->fn_write($fp, "INSERT INTO `{$table}` VALUES");
                $query = 'SELECT * FROM '.$table.' ORDER BY '.$order_by_col.' LIMIT '.$from.', '.$limit.'';
                $query = 'SELECT * FROM '.$table.' ORDER BY '.$order_by_col.'';
                $result = $this->dbc->Execute($query);
                if (!$result) {
                    $this->cms->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
                    return false;
                }
                $total = $result->RecordCount();
                while($row = $result->FetchRow()) {
                    $row= array_values($row);
                    $i++;
                    $t++;
                    for($k = 0; $k < $fields; $k++){
                        if ($NumericColumn[$k]) {
                            $row[$k] = isset($row[$k]) ? $row[$k] : "NULL";
                        } else {
                            $row[$k] = isset($row[$k]) ? "'" . mysql_escape_string($row[$k]) . "'" : "NULL";
                        }
                    }
                    $this->fn_write($fp, ($i == 1 ? "" : ",") . "\n(" . implode(", ", $row) . ")");
                }
                $from += $limit;
                $this->fn_write($fp, ";\n\n");
            }
        }
        $this->tabs = $tabs;
        $this->records = $tabinfo[0];
        $this->comp = $this->SET['comp_method'] * 10 + $this->SET['comp_level'];
        $this->fn_close($fp);
        $messages = array();
        $messages[] = "Резервная копия успешно создана.";
        $messages[] = "Размер базы даннх: " . round($this->size / 1048576, 2) . " МБ";
        $filesize = round(filesize($this->filename) / 1048576, 2) . " МБ";
        $messages[] = "Размер файла: {$filesize}";
        $messages[] = "Таблиц в резервной копии БД: {$tabs}";
        $messages[] = "Записей в резервной копии БД:   " . number_format($tabinfo[0], 0, ',', ' ') ;
        $this->cms->int_set_message('top', implode('<br>',$messages), 'Создание резервной копии БД', 'information');
        $target_url = $this->cms->format_url($this->plugin['name'], 'index', array());
        header('Location: '.$target_url);
        return true;
    }


    function fn_filename($name){
        if ($this->SET['comp_method'] == 2) {
            $this->filename = PATH.$name.'.sql.bz2';
        } else if ($this->SET['comp_method'] == 1) {
            $this->filename = PATH.$name.'.sql.gz';
        } else {
            $this->filename = PATH.$name.'.sql';
        }
        return $this->filename;
    }


    function fn_open($filename, $mode){
        $this->filename = $filename;
        if ($this->SET['comp_method'] == 2) {
            return bzopen($this->filename, $mode.'b'.$this->SET['comp_level']);
        } elseif ($this->SET['comp_method'] == 1) {
            return gzopen($this->filename, $mode.'b'.$this->SET['comp_level']);
        } else {
            return fopen($this->filename, $mode.'b');
        }
    }


    function fn_write($fp, $str){
        if ($this->SET['comp_method'] == 2) {
            bzwrite($fp, $str);
        }
        elseif ($this->SET['comp_method'] == 1) {
            gzwrite($fp, $str);
        }
        else{
            fwrite($fp, $str);
        }
    }


    function fn_read($fp){
        if ($this->SET['comp_method'] == 2) {
            return bzread($fp, 4096);
        } elseif ($this->SET['comp_method'] == 1) {
            return gzread($fp, 4096);
        } else {
            return fread($fp, 4096);
        }
    }


    function fn_read_str($fp) {
        $string = '';
        $this->file_cache = ltrim($this->file_cache);
        $pos = strpos($this->file_cache, "\n", 0);
        if ($pos < 1) {
            while (!$string && ($str = $this->fn_read($fp))){
                $pos = strpos($str, "\n", 0);
                if ($pos === false) {
                    $this->file_cache .= $str;
                } else {
                    $string = $this->file_cache . substr($str, 0, $pos);
                    $this->file_cache = substr($str, $pos + 1);
                }
            }
            if (!$str) {
                if ($this->file_cache) {
                    $string = $this->file_cache;
                    $this->file_cache = '';
                    return trim($string);
                }
                return false;
            }
        } else {
            $string = substr($this->file_cache, 0, $pos);
            $this->file_cache = substr($this->file_cache, $pos + 1);
        }
        return trim($string);
    }


    function fn_close($fp){
        if ($this->SET['comp_method'] == 2) {
            bzclose($fp);
        }
        elseif ($this->SET['comp_method'] == 1) {
            gzclose($fp);
        }
        else{
            fclose($fp);
        }
        @chmod(PATH . $this->filename, 0666);
        $this->fn_index();
    }


    function fn_index(){
        if (!file_exists(PATH . 'index.html')) {
            $fh = fopen(PATH . 'index.html', 'wb');
            fwrite($fh, '');
            fclose($fh);
            @chmod(PATH . 'index.html', 0666);
        }
    }


    function init(){
        require_once(cms_LIB_PATH.'form_builder.class.php');
        $this->cms->init_dbc();
        $this->dbc = &$this->cms->dbc;
		$this->cms->pathModule[] = '<a href="'.$this->admin_url.'?'.$this->cms->request_vars['group'].'=modules">Програмные модули</a>';
		$this->cms->pathModule[] = '<a href="'.$this->cms->format_url($this->plugin['name'], 'index', array()).'">Резервное копирование БД</a>';
		$this->cms->intSetHierarchy();
    }


    function main($command, $arguments) {
        $this->init();
        $this->cms->int_set_h1($this->plugin['title']);
        if (!empty($command) && $command[0]!='_' && method_exists($this, $command)) {
            $result = $this->$command($arguments);
            return $result;
        }
        switch($command) {
            default:
            case 'index':
            $result = $this->index($arguments);
            break;
        }
        return $result;
    }


}

?>