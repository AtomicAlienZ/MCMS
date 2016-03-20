<?php
@include_once('../core/config-host.php');
if(!defined('cms_CORE_PATH')) {
    die('Configuration error. config-host not found.');
}
set_magic_quotes_runtime(0);
error_reporting(PM_ERROR_REPORTING);
//include_once(cms_CORE_PATH.'lib/cms.class.php');
include_once(cms_CORE_PATH.'lib/cms_core.class.php');
include_once(cms_CORE_PATH.'lib/cms_admin.class.php');
include_once(cms_CORE_PATH.'lib/cms_plugin_admin.class.php');
include_once(cms_CORE_PATH.'lib/strcom_parser.class.php');
include_once(cms_CORE_PATH.'lib/message_box.class.php');
require_once(cms_PATH_TOOLS.'install.inc.php');
//die();
?>