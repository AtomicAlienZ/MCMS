@@message@@
[from]=quartzetto;
[to]={#email};
[subject]=Восстановление пароля на сайте {#site};
[mail_type]=html;
[body]
<html><head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style>
<!--
body { margin: 0; padding: 20px; background: #fff; }
body, td, th, input, textarea, select { font: 11px Tahoma, Geneva, Arial, Helvetica, sans-serif; color: #4E4E4E; }
a, a:link { color: #2B4486; }
a:visited { color: #2B4486; }
a:hover { color: #2B4486; }
h1 { margin: 0 0 10px 0; }
h1, h1 a, h1 a:link, h1 a:visited, h1 a:hover { font-weight: bold; font-size: 14px; color: #2B4486; }
h2 { margin: 10px 0 5px 0; }
h2, h2 a, h2 a:link, h2 a:visited, h2 a:hover { font-weight: normal; font-size: 14px; color: #2B4486; }
h3 { margin: 10px 0 5px 0;}
h3, h3 a, h3 a:link, h3 a:visited, h3 a:hover { font-weight: bold; font-size: 13px; color: #2B4486;}
p { margin: 0 0 10px 0; }
ul, ol { padding-left: 22px; margin: 0; }
li { margin: 0 0 5px 0; color: #2B4486; }
hr { border: 0; height: 1px; color: #999; background: #999; }
-->
</style>
</head>
<body>
<h3>Добрый день!</h3>
<p>На {#site} была подана заявка на восстановление пароля</p>
<h3>Используйте для входа такие данные:</h3>
<table>
    <tr>
        <td>Логин/E-mail:</td>
        <td>{#login}</td>
    </tr>
	<tr>
		<td>Пароль:</td>
		<td>{#password}</td>
    </tr>
</table>
</body>
</html>
[body]
@@message@@
