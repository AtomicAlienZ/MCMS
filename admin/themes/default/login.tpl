<!DOCTYPE html>
<html>
<head>
	<title>{$title}</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,nofollow">
	<meta name="Cache-control" content="no-cache, must-revalidate">

	<link href='https://fonts.googleapis.com/css?family=Roboto:400,500,700&subset=latin,cyrillic-ext,cyrillic' rel='stylesheet' type='text/css'>
	<link href="/admin/css/cms.css" rel="stylesheet" type="text/css">
	<link href="/admin/css/style.css" rel="stylesheet" type="text/css">

	<script type="text/javascript" src="/admin/js/cms.js"></script>
	<script type=text/javascript>
		function focusLogin(the_form, field_name) {
			var elts = document.forms[the_form].elements;
			var elts_cnt = (typeof(elts.length) != 'undefined')
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
	</script>
</head>

<body class="b-admin__body_login">

<div class="b-admin__login">
	<form name="fob" method="post" action="index.php" enctype="multipart/form-data">
		<div class="b-admin__login__field">
			<div class="b-admin__login__field__name">Логин</div>
			<input class="b-admin__login__field__input" type="text" name="fob[login]">
		</div>
		<div class="b-admin__login__field">
			<div class="b-admin__login__field__name">Пароль</div>
			<input class="b-admin__login__field__input" type="password" name="fob[password]">
		</div>

		<div class="b-admin__login__submit">
			<input type="Submit" class="b-admin__login__submit__button" name="fob[submit]" value="Войти">
		</div>
	</form>
</div>


<!--
<table width="100%" height="100%">
	<tr>
		<td class="login">

			{$messages_top}

			{$messages_bottom}

			</div>

		</td>
	</tr>
</table>-->

<script type=text/javascript><!--
	focusLogin('fob', 'fob[login]');
	//--></script>
</html>