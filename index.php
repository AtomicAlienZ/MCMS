<?php

@session_start();
@include_once('core/config-host.php');

if (!defined('cms_CORE_PATH')) {
    die('Configuration error. config-host not found.');
}

//set_magic_quotes_runtime(0);
error_reporting(PM_ERROR_REPORTING);
error_reporting(E_ALL);

include_once(cms_CORE_PATH . 'lib/cms_core.class.php');
include_once(cms_CORE_PATH . 'lib/cms_publisher.class.php');

if (!class_exists('cms_publisher')) {
    die('Configuration error.');
}

$cms = & new cms_publisher();
$pageContent = $cms->display('default');
$pageContent = str_replace(array("/ru/"), array("/"), $pageContent);
echo $pageContent;

function error_warning($errors = array())
{
    if (count($errors) > 0 && PM_DISPLAY_ERRORS) {
        $message = '
            <div style="padding: 20px; padding-bottom: 5px;">
                Причиною збою стали наступні помилки:
                <li>' . implode('<li>', $errors) . '
            </div>
        ';
    }
    echo 'Due to a technical reasons the page you have requested is temporary unavailable.<br />Please try again shortly.<br /><br />' . $message ;
    die();
}

?>