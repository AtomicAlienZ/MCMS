@@message@@
[from]=info@{#site};
[to]={#email};
[subject]=����� ����������� �� {#site};
[mail_type]=html;
[body]
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
</head>
<body>
<p>{#name} ������� ����� ����������� ��� <a href="{#url}">�����������</a>:
<p>{#comment}
</body>
</html>
[body]
@@message@@
