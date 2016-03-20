<html>
<head>
<title>CMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link href="/admin/css/cms.css" rel="stylesheet" type="text/css">
<script src="/admin/js/cms.js" type="text/javascript"></script>
</head>

<body id="cms">

<div class="sn"><div class="bg"><div class="l"><div class="r">
  <div class="title-s">{$title}</div>
  <div class="title"><a href="{$admin_url}">{$title}</a></div>
</div></div></div></div>

<div class="menu0"><div class="cont">{$menu0}<a href="{$admin_url}?plg=cms_auth&cmd=logout" class="exit">�����</a></div></div>

{$hierarchy_path}

<table id="cols">
<tr>
  <td id="col-left">

    <div class="col">
    <div class="col-lt"><div class="col-rt"><div class="col-rb"><div class="col-lb">
      <div class="col-title">{$active_group_title}</div>
      <div class="col-cont">{$menu1}</div>
    </div></div></div></div>
    </div>

    <div class="min-width"><img src="/admin/img/px.gif" width="1" height="1" alt=""></div>

  </td>
  <td id="col-main">

    <div class="col">
    <div class="col-lt"><div class="col-rt"><div class="col-rb"><div class="col-lb">
      {$messages_top}{$body}{$messages_bottom}
    </div></div></div></div>
    </div>

    <div class="min-width"><img src="/admin/img/px.gif" width="1" height="1" alt=""></div>

  </td>
</tr>
</table>

<div id="bot"><img src="/admin/img/px.gif" width="1" height="1" alt=""></div>
</body>
</html>
