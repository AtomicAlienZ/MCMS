<?php
@include_once('init.php');
if (!class_exists('cms_admin') ) {
    die('Configuration error. Class cms_admin not found.');
}
$cms = new cms_admin();
$cms->direct();
?>