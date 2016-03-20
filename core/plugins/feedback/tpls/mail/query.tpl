@@message@@
[from]={#from};
[to]={#to};
[subject]=Форма обратной связи;
[mail_type]=html;
[body]
<html><head>
<title></title>
<meta charset="utf-8">
<style>
<!--
body, td, th, input, textarea, select {font: 12px Arial, Helvetica, sans-serif; color: #545454;}
td { padding: 2px 2px 0px 0px; }
.ttl { font-weight: bold; }
a, a:link {color: #065FAB;}
a:visited {color: #065FAB;}
a:hover {color: #600;}
h1 {margin: 0 0 10px 0; font-weight: bold; font-size: 24px;}
h1, h1 a, h1 a:link, h1 a:visited, h1 a:hover {color: #600;}
h2 {margin: 10px 0 5px 0; font-weight: bold; font-size: 16px;}
h2, h2 a, h2 a:link, h2 a:visited, h2 a:hover {color: #065FAB;}
h3 {margin: 10px 0 5px 0; font-weight: bold; font-size: 14px;}
h3, h3 a, h3 a:link, h3 a:visited, h3 a:hover {color: #065FAB;}
p {margin: 0 0 10px 0;}
ul, ol {padding: 0 0 0 22px; margin: 0 0 10px 22px;}
hr {border: 0; height: 1px; color: #CACACA; background: #CACACA;}
-->
</style>
</head>
<body>
<p>На сайте <a href="{#site_url}"><strong>{#site}</strong></a> была заполнена <strong>"Форма обратной связи"</strong></p>
<table>
	<tr><td><span>ФИО:</span></td><td><strong>{#fio}</strong></td></tr>
	<tr><td><span>E-mail:</span></td><td><a href="mailto:{#email}">{#email}</a></td></tr>
	<tr><td><span>Вопрос:</span></td><td>{#query}</td></tr>
</table>
<h3>С уважением, администрация сайта <a href="{#site_url}"><strong>{#site}</strong></a></h3>
</body>
</html>
[body]
@@message@@