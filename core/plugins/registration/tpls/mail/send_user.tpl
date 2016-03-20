@@message@@
[from]=quartzetto;
[to]={#manager_mail};
[subject]=����������� �� {#site};
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
<h1>������ ����!</h1>
<p>�������, {#dates} �� ����� <a href="http://{#site}">{#site}</a> �� ���� ����������������.</p>
<p>���� ������:</p>
<table>
	<tr>
		<td>�����/E-mail:</td>
		<td>{#email}</td>
	</tr>
	<tr>
		<td>������:</td>
		<td>{#password}</td>
	</tr>  	
	<tr>
		<td>���/�������:</td>
		<td>{#last_name} {#name}</td>
	</tr>
	<tr>
		<td>������:</td>
		<td>{#country}</td>
	</tr>
	<tr>
		<td>�����:</td>
		<td>{#city}</td>
	</tr>
	<tr>
		<td>�������:</td>
		<td>{#phone}</td>
	</tr>
 </table>
</body>
</html>
[body]
@@message@@
