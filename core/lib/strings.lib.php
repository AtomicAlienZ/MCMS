<?php
    
    function translit( $cyr_str) {
        $tr = array(
            "�"=>"G","�"=>"YO","�"=>"E","�"=>"YI","�"=>"I",
            "�"=>"i","�"=>"g","�"=>"yo", "�"=>"e",
            "�"=>"yi","�"=>"A","�"=>"B","�"=>"V","�"=>"G",
            "�"=>"D","�"=>"E","�"=>"ZH","�"=>"Z","�"=>"I",
            "�"=>"Y","�"=>"K","�"=>"L","�"=>"M","�"=>"N",
            "�"=>"O","�"=>"P","�"=>"R","�"=>"S","�"=>"T",
            "�"=>"U","�"=>"F","�"=>"H","�"=>"TS","�"=>"CH",
            "�"=>"SH","�"=>"SCH","�"=>"'","�"=>"YI","�"=>"",
            "�"=>"E","�"=>"YU","�"=>"YA","�"=>"a","�"=>"b",
            "�"=>"v","�"=>"g","�"=>"d","�"=>"e","�"=>"zh",
            "�"=>"z","�"=>"i","�"=>"y","�"=>"k","�"=>"l",
            "�"=>"m","�"=>"n","�"=>"o","�"=>"p","�"=>"r",
            "�"=>"s","�"=>"t","�"=>"u","�"=>"f","�"=>"h",
            "�"=>"ts","�"=>"ch","�"=>"sh","�"=>"sch","�"=>"'",
            "�"=>"yi","�"=>"","�"=>"e","�"=>"yu","�"=>"ya"
        );
        return strtr($cyr_str,$tr);
    }
  
    function get_valid_filename($target_filename){
        $target_filename = trim(translit($target_filename));
        if ( !preg_match('/^[a-z0-9\-~_]$/is', $target_filename) ) {
            $chars = preg_split('//', $target_filename, -1, PREG_SPLIT_NO_EMPTY); 
            while (list($id, $char)=each($chars)) {
                if ( !preg_match('/^[a-z0-9\-~_]$/is', $char) ) {
                    $chars[$id] = '_';
                }
            }
            $target_filename = implode('', $chars);
        }
        return $target_filename;
    }

?>
