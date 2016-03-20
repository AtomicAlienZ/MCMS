<?php
    $this->root_path            = ROOT_HOME_PATH;
    /* core */
    $this->core_path            = cms_CORE_PATH;
    $this->plugins_path         = cms_PLUGINS_PATH;
    $this->startup_path         = cms_CORE_PATH.'startup/';
    $this->lib_path                            = cms_LIB_PATH;
    $this->cache_path                        = cms_CACHE_PATH;
    $this->data_path                        = cms_DATA_PATH;
    define('SMARTY_DIR',          $this->core_path.'lib/smarty/');

    /*site*/
    $this->site_id              = SITE_ID;
    $this->root_url             = ROOT_URL;
    $this->site_url             = ROOT_URL;
    $this->publisher_url        = cms_PUBLISHER_URL;
    $this->site_path            = cms_SITE_PATH;
    $this->url_mode             = 'smart';
    /*admin*/
    $this->admin_url            = cms_ADMIN_URL;
    $this->admin_path           = cms_ADMIN_PATH;
    $this->themes_path          = cms_ADMIN_PATH.'themes/';
    $this->theme                = 'default';
    /**/
    $this->smarty_base_dir      = SMARTY_DIR;
    $this->smarty_dirs          = array(
                                    'templates'   => $this->core_path.'templates',
                                    'templates_c' => cms_CORE_PATH.'cache',
                                    'configs'     => '',
                                    'plugins'     => '',
                                    'cache'       => cms_CORE_PATH.'cache'
                                    );

  $this->debug                  = PM_DEBUG;
  $this->display_debuginfo      = PM_DISPLAY_DEBUGINFO;
  $this->display_debugpanel     = PM_DISPLAY_DEBUGPANEL;

  $this->db_cache_interval      = 0;
  $this->cache_interval         = -1;
  $this->caching                = PM_CACHING;

  include_once(cms_CORE_PATH.'/config/default/tables.php');
  if (!defined('SERVER_SOFTWARE') || strlen(SERVER_SOFTWARE)==0  ) {
      if (!empty($_ENV["SERVER_SOFTWARE"])) {
          $server = strtolower($_ENV["SERVER_SOFTWARE"]);
      } else if (!empty($_SERVER["SERVER_SOFTWARE"])) {
          $server = strtolower($_SERVER["SERVER_SOFTWARE"]);
      } else {
          $server = '';
      }
      if (strpos($server, 'IIS')>0) {
          $this->server = 'iis';
      } else {
          $this->server = 'apache';
      }
  } else {
      $this->server = SERVER_SOFTWARE;
  }
/* rg@0606 */
    $this->restrict_site_access = true;
/* /rg@0606 */


?>