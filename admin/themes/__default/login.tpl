<html>
<head>
<title>{$title}</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex,nofollow"> 
<meta name="Cache-control" content="no-cache, must-revalidate">
<meta name="Pragma" content="no-cache">
<meta name="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
<link href="/admin/css/cms.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="/admin/js/cms.js"></script>
<script type=text/javascript>
<!--
function focusLogin(the_form, field_name){
    var elts = document.forms[the_form].elements;
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;
    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
            if (elts[i].name == field_name) {
                elts[i].focus();
            }
        } // end for
    } // end if... else
    return true;
}
//-->
</script>
</head>

<body id="cms">

<table width="100%" height="100%">
<tr>
  <td class="login">


      {$messages_top}

      <div class="shadow">
      
        <div class="h"><div class="l"><div class="r">
          <div class="title-s">Добро пожаловать</div>
          <div class="title">Добро пожаловать</div>
        </div></div></div>
        <div class="fields"><div class="fields-bot">
        <form name="fob" method="post" action="index.php" enctype="multipart/form-data">
        <table width="100%">
        <tr class="top">
        	<td class="l"><label for="fob[login]">Логин</label></td>
        	<td class="f"><input type="Text" class="text" name="fob[login]"></td>
        </tr>
        <tr>
        	<td class="l"><label for="fob[password]">Пароль</label></td>
        	<td class="f"><input type="Password" class="text" name="fob[password]"></td>
        </tr>
        <tr>
        	<td class="l"><label for="fob[member]">Запомнить</label></td>
        	<td class="f"><input type="checkbox" name="fob[member]" value=1></td>
        </tr>
        <tr class="bot">
          <td>&nbsp;</td>
          <td class="f"><div class="submit"><input type="Submit" class="button" name="fob[submit]" value="Вход"></div></td>
        </tr>
        </table>
        </form>
        </div></div>
      
      </div>

      {$messages_bottom}

    </div>

  </td>
</tr>
</table>

<script type=text/javascript><!--
focusLogin('fob', 'fob[login]');
//--></script>
</html>