@@message@@
[from]=info@{#site};
[to]={#email};
[subject]=Новый комментарий на {#site};
[mail_type]=html;
[body]
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>
<p>{#name} добавил новый комментарий под <a href="{#url}">фотографией</a>:
<p>{#comment}
</body>
</html>
[body]
@@message@@
