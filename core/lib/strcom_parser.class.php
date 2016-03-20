<?php
/**
* class strcom_parser
*/
class strcom_parser
{

################################################################################
## 1. tags

/**
* strcom_parser::getTagsList()
* returns array of unique tags found in $content
* (Example: <tag>block</tag>)
*
* @param string $b_left       left bracket
* @param string $b_right      right bracket
*/
function getTagsList($content, $b_left="[", $b_right="]") {
    $b_left   = preg_quote ($b_left);
    $b_right  = preg_quote ($b_right);
    $patern = '/'.$b_left.'(\w{1,50})'.$b_right.'/siU';
    preg_match_all ( $patern , $content , $outdata);
    return array_unique($outdata[1]);
}

/**
* strcom_parser::getTags()
* returns array of unique tags found in $content and their corresponding blocks
* (Example: <tag>block</tag>)
*
* @param string $b_left        left bracket
* @param string $b_right       right bracket
* @param string $b_closer      closing sing for 2nd tag
*/
function getTags($content, $b_left="[", $b_right="]", $b_closer="/") {
    $tags_list = $this->getTagsList($content, $b_left, $b_right);
    $tags = array();
    while (list(,$tag) = each($tags_list)) {
        $tags[$tag] = $this->getTag($content, $tag, $b_left, $b_right, $b_closer);
    }
    return $tags;
}

/**
* strcom_parser::getTag()
* returns array of blocks contained in $tag
* (Example: <tag>block</tag>)
*
* @param string $b_left        left bracket
* @param string $b_right       right bracket
* @param string $b_closer      closing sing for 2nd tag
*/
function getTag($content, $tag, $b_left="[", $b_right="]", $b_closer="/") {
    $b_left   = preg_quote ($b_left);
    $b_right  = preg_quote ($b_right);
    $b_closer = $this->_quote_closer($b_closer);
    $patern = '/'.$b_left.$tag.$b_right.'(.*)'.$b_left.$b_closer.$tag.$b_right.'/siU';
    preg_match_all ( $patern , $content , $outdata);
    return $outdata[1];
}

################################################################################
## 2. variables
## string defined by some variable -> <var> = (.*);
## 1) [var1] = bla-bla-bla;
## 2) [var2] = bla-bla-bla
## 3) [var3] bla-bla-bla;
/**
* strcom_parser::getVarsList()
* returns array of unique variables found in $content
* Example: [variable] = value;
*
* @param string $b_left        left bracket
* @param string $b_right       right bracket
*/
function getVarsList($content, $b_left="[", $b_right="]") {
    $b_left   = preg_quote ($b_left);
    $b_right  = preg_quote ($b_right);
    $patern = '/'.$b_left.'(.*)'.$b_right.'/siU';
    preg_match_all ( $patern , $content , $outdata);
    return array_unique($outdata[1]);
}


/**
* strcom_parser::getVar()
* returns array of values defined by variable $var
*
* @param string $b_left        left bracket
* @param string $b_right       right bracket
* @param string $equal_mark
* @param string $equation_end
*/
function getVar($content, $var, $b_left='[', $b_right=']', $equal_mark = '=', $equation_end = ';') {
    $b_left   = preg_quote ($b_left);
    $b_right  = preg_quote ($b_right);
    $patern = '/'.$b_left.$var.$b_right.$equal_mark.'(.*)'.$equation_end.'/smiU';
    preg_match_all( $patern , $content , $outdata);
    return $outdata[1];
}

/**
* strcom_parser::getVars()
* returns array of variables found in $content and their corresponding values
*
* @param string $b_left        left bracket
* @param string $b_right       right bracket
* @param string $equal_mark
* @param string $equation_end
*/
function getVars($content, $b_left='[', $b_right=']', $equal_mark = '=', $equation_end = ';') {
    $vars_list = $this->getVarsList($content, $b_left, $b_right);
    $vars = array();
    while (list(,$var) = each($vars_list)) {
        $vars[$var] = $this->getVar($content, $var, $b_left, $b_right, $equal_mark, $equation_end);
    }
    return $vars;
}


################################################################################
## 3. commands

/**
* strcom_parser::getCommands()
* decodes $content to array of commands and their arguments
*/
function getCommands($content) {
    $content=str_replace("\r","",$content);
    $content=preg_replace("/  +/"," ",$content);
    $patern = '/^\s{0,1}(\w*)\s{0,1}(.*)$/simU';
    $lines = explode("\n", $content);
    while (list($i, $line) = each($lines))
        if (!empty($line)){
            $line = trim($line);
            $arguments = array();
            $arguments = explode (' ', $line );
            $command = $arguments[0];
            array_shift($arguments);
            $commands[$command] = implode(' ', $arguments);
            //$commands[$i][0] = $command;
            //$commands[$i][1] = implode(' ', $arguments);
        }
    return $commands;
}

################################################################################

/**
* strcom_parser::_quote_closer()
* internal function
*/
function _quote_closer($b_closer) {
    if ($b_closer == '/') {
        return "\/";
    } else {
        return preg_quote ($b_closer);
    }
}

} /* end of strcom_parser class*/




?>
