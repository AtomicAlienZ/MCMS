<?php
/***** misc ***********/
define('PM_DEBUG', false);
define('PM_DISPLAY_DEBUGPANEL', false);
define('PM_DISPLAY_DEBUGINFO', true);
define('PM_DESIGN', true);
define('PM_CACHING', false);
define('PM_DISPLAY_ERRORS', true);
define('PM_ERROR_REPORTING', E_ALL);//'E_ALL'

define('SERVER_SOFTWARE', 'apache');
/***************************    Var Database    **************************/
define('ROOT_DB_DRIVER', 'mysql');
define('ROOT_DB_HOST', 'localhost');
define('ROOT_DB_USER', 'root');
define('ROOT_DB_PASS', '');
define('ROOT_DB_NAME', 'mcms');
define('PREFIX_TABLE', 'pm');
/***** general ********/
// Full path from root
define('ROOT_HOME_PATH', 'C:\\Workspace\\MCMS\\');
define('ROOT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('ROOT_HTML_PATH', ROOT_HOME_PATH . '');
/***** core ***********/
define('cms_CORE_PATH', ROOT_HOME_PATH . 'core/');
define('cms_PLUGINS_PATH', cms_CORE_PATH . 'plugins/');
define('cms_LIB_PATH', cms_CORE_PATH . 'lib/');
define('cms_DATA_PATH', cms_CORE_PATH . 'data/');
define('cms_CACHE_PATH', cms_CORE_PATH . 'cache/');

/***** admin **********/
define('cms_ADMIN_PATH', ROOT_HTML_PATH . 'admin/');
define('cms_ADMIN_URL', ROOT_URL . 'admin/index.php');
/***** site ***********/
define('SITE_ID', 'mi');
define('cms_SITE_URL', ROOT_URL);
define('cms_SITE_PATH', ROOT_HTML_PATH);
define('cms_PUBLISHER_URL', ROOT_URL . '');


/**************************************************************************************/
/***************************    New Config Var    ***************************************/
define('cms_PATH_TOOLS', cms_LIB_PATH . 'tools/');

/**************************************************************************************/

$FOLDERS = array();
$FOLDERS['images'] = array(
    'type' => 'images',
    'path' => ROOT_HTML_PATH . 'content/images/',
    'path1' => 'content/images/',
    'url' => '/content/images/',
    'caption' => 'Images'
);
$FOLDERS['files'] = array(
    'type' => 'files',
    'path' => ROOT_HTML_PATH . 'content/files/',
    'path1' => 'content/files/',
    'url' => '/content/files/',
    'caption' => 'Files'
);
/***** misc ***********/
/* ACCESS_LEVELS */
define('AL_GUEST',		0);
define('AL_USER',		10);
define('AL_MANAGER',	50);
define('AL_ADMIN',		60);
define('AL_DEVELOPER',	90);

$ACCESS_LEVELS = array(
    AL_DEVELOPER => 'Разработчик',
    AL_USER => 'Менеджер',
    AL_GUEST => 'Гость'
);

/* ACCESS_PERMISSIONS */
define('AP_NONE', 0);
define('AP_PARTIAL', 5);
define('AP_FULL', 9);

$ACCESS_PERMISSIONS = array(
    AP_FULL => 'полный доступ',
    AP_PARTIAL => 'частичный доступ',
    AP_NONE => 'нет доступа',
);
$AP_COLORS = array(
    AP_FULL => '#008800',
    AP_PARTIAL => '#666666',
    AP_NONE => '#990000',
);
$ACCESS_SITE = array(
    AL_DEVELOPER => AP_FULL,
    AL_ADMIN => AP_FULL,
    AL_MANAGER => AP_PARTIAL,
    AL_USER => AP_PARTIAL,
    AL_GUEST => AP_NONE
);
$ACCESS_CMS = array(
    AL_DEVELOPER => AP_FULL,
    AL_ADMIN => AP_FULL,
    AL_MANAGER => AP_PARTIAL,
    AL_USER => AP_NONE,
    AL_GUEST => AP_NONE
);
/**********************/
?>