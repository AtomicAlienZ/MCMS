<?php
@include_once('init.php');
if (!class_exists('cms_admin') ) {
    die('Configuration error. Class cms_admin not found.');
}

// Autoloading and auto-initting
function autoload ($name) {
    $curCWD = getcwd();
    $curCWDPath = explode(DIRECTORY_SEPARATOR, $curCWD);
    $nameParts = explode('_', $name);
    $pluginName = strtolower($nameParts[0]);
    $path = 'core/plugins/'.$pluginName.'/';

    if (end($curCWDPath) == 'admin') {
        $path = '../'.$path;
    }

    if (file_exists($path)) {
        require_once $path.$name.'.php';
    }
}

spl_autoload_register('autoload');

$cms = new cms_admin();
$cms->shell();
//die();
?>