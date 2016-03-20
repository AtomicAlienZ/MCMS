<?php
    
    function translit( $cyr_str) {
        $tr = array(
            "¥"=>"G","¨"=>"YO","ª"=>"E","¯"=>"YI","²"=>"I",
            "³"=>"i","´"=>"g","¸"=>"yo", "º"=>"e",
            "¿"=>"yi","À"=>"A","Á"=>"B","Â"=>"V","Ã"=>"G",
            "Ä"=>"D","Å"=>"E","Æ"=>"ZH","Ç"=>"Z","È"=>"I",
            "É"=>"Y","Ê"=>"K","Ë"=>"L","Ì"=>"M","Í"=>"N",
            "Î"=>"O","Ï"=>"P","Ð"=>"R","Ñ"=>"S","Ò"=>"T",
            "Ó"=>"U","Ô"=>"F","Õ"=>"H","Ö"=>"TS","×"=>"CH",
            "Ø"=>"SH","Ù"=>"SCH","Ú"=>"'","Û"=>"YI","Ü"=>"",
            "Ý"=>"E","Þ"=>"YU","ß"=>"YA","à"=>"a","á"=>"b",
            "â"=>"v","ã"=>"g","ä"=>"d","å"=>"e","æ"=>"zh",
            "ç"=>"z","è"=>"i","é"=>"y","ê"=>"k","ë"=>"l",
            "ì"=>"m","í"=>"n","î"=>"o","ï"=>"p","ð"=>"r",
            "ñ"=>"s","ò"=>"t","ó"=>"u","ô"=>"f","õ"=>"h",
            "ö"=>"ts","÷"=>"ch","ø"=>"sh","ù"=>"sch","ú"=>"'",
            "û"=>"yi","ü"=>"","ý"=>"e","þ"=>"yu","ÿ"=>"ya"
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
