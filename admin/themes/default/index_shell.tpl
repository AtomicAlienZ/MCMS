<!DOCTYPE html>
<html>
<head>
<title>CMS</title>
	<link href='https://fonts.googleapis.com/css?family=Roboto:400,500,700&subset=latin,cyrillic-ext,cyrillic' rel='stylesheet' type='text/css'>

    <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
    <link href="/admin/css/cms.css" rel="stylesheet" type="text/css">
    <link href="/admin/css/style.css" rel="stylesheet" type="text/css">

    <script src="http://code.jquery.com/jquery-latest.min.js"></script>
    <script src="/admin/js/cms.js" type="text/javascript"></script>

    <script type="text/javascript" src="/admin/js/spectrum/spectrum.js"></script>
    <link href="/admin/js/spectrum/spectrum.css" rel="stylesheet" type="text/css">
</head>

<body id="cms">

<div class="b-admin-header">
    <a class="b-admin-header-sitename" href="{$admin_url}">{$title}</a>

    <div class="b-admin-menu">
        {$menu0}
        <a class="b-admin-menu-item" href="{$admin_url}?plg=cms_auth&cmd=logout" class="exit">Выход</a>
    </div>

</div>

{$hierarchy_path}


    <div class="b-admin-content">
        <div class="b-admin-content-column b-admin-content-column__narrow">
            <div class="b-admin-content-column-header">{$active_group_title}</div>
            <div class="b-admin-content-column-content">{$menu1}</div>
        </div>
        <div class="b-admin-content-column b-admin-content-column__main">
            <div class="b-admin-content-column-content">
                {$messages_top}
                {$body}
                {$messages_bottom}
            </div>
        </div>
    </div>


</body>
</html>