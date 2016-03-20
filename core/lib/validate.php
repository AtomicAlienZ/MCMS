<?php
/**************************************************************************/
  function is_alpha( $var ) {
    return (ereg("^[A-Za-z0-9]+$" , $var ));
  }
/**************************************************************************/
function is_login($string, $empty_is_valid = false) {
	if (  (empty($string)) && ($empty_is_valid === true) ) {
	    return true;
	} else if (empty($string)) {
        return false;
	}
   if ( preg_match('/^[a-z0-9]+[a-z0-9\-_]+[a-z0-9]+$/is', $string) ) {
       return true;
   } else {
       return false;
   }	
  }
/**************************************************************************/
function is_password($string, $check_notempty = false) {
	if (  (empty($string)) && ($check_notempty) ) {
	    return false;
	} else if (empty($string)) {
        return true;
	}
   if ( preg_match('/^[a-z0-9\-_@#\$,\.]+$/is', $string) ) {
       return true;
   } else {
       return false;
   }	
  }
/**************************************************************************/
  function is_email ($email) {
   if ( preg_match('/^[a-z0-9\.\-_]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $email) ) {
       return true;
   } else {
       return false;
   }
  }

/**************************************************************************/
function compare_passwords($pass1, $pass2) {
      if ($pass1 == $pass2) {
          return true;
      } else {
          return false;
      }
    }
/**************************************************************************/
function is_password_secure($password, $login)  {
        $min_uniq_chars           = 0.4;
		$oliver_chardistance	  = 2;
		$oliver_percentage		  = 50;
        $password = preg_replace ("'[\s]'", "", $password);
        $login    = preg_replace ("'[\s]'", "", $login);
        $password = strtolower($password);
        $login    = strtolower($login);
		$chardistance = similar_text ($password, $login, $percentage);
		if ($percentage >= $oliver_percentage || (strlen ($password)-$chardistance) < $oliver_chardistance)	{
            return false;
        }
    	if (soundex ($password) == soundex ($login)) {
    	    return false;
        }
    	if (metaphone ($password) == metaphone ($login)) {
    	    return false;
    	}
    	if (is_numeric ($password)) {
    	    return false;
        }
        $chars = preg_split('//', $password, -1, PREG_SPLIT_NO_EMPTY);
        $uniq = array_flip($chars);
        if (count($uniq) < ceil(strlen($password)*$min_uniq_chars) ) {
            return false;
        }
    	return true;
    }

?>
