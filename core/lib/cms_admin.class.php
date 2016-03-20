<?php

class cms_admin extends cms_core
{
    var $default_group = 'structure';
    var $default_plugin = 'cmsStructure';
    var $groups = array();
    var $groups_menu = array();
    var $plugins = array();
    var $plugins_menu = array();
    var $handlers = array();
    var $handlers_list = array();
    var $managers_list = array();
//	var $informers_list		= array();
    var $tpl_prefix = '{$';
    var $tpl_suffix = '}';
    var $templates = array();
    var $template_variables = array();
    var $request_vars = array(
        'mode' => 'mode',
        'group' => 'grp',
        'plugin' => 'plg',
        'command' => 'cmd',
        'arguments' => 'arg'
    );

    var $message_box = '';
    var $admin_lang = 'ru';

    var $pathAdmin = array();
    var $pathModule = array();

    function cms_admin()
    {
        require_once(cms_LIB_PATH . "files.inc.php");
        $this->files = new files;
//        $this->cms_core();
    }

    function shell($plugin = '', $command = '', $arguments = array())
    {
        $this->reindex_configs();
        $this->load_config($this->site_id);
        $this->init_dbc();

        $this->initInstall();
        $this->installDefaultTable();

        $this->startup();
        $this->init_admin_lang();
        $this->reindex_plugins();
        $this->_load_templates(true);
        $active_arguments = '';
        $active_command = '';
//		echo "<pre>"; print_r($_REQUEST); echo "</pre>";
//		echo "<pre>"; print_r($this->request_vars); echo "</pre>";
//		echo "<pre>"; print_r($this->groups); echo "</pre>";
        if (!empty($_REQUEST[$this->request_vars['group']]) && !empty($this->groups[$_REQUEST[$this->request_vars['group']]])) {
            $active_group = $_REQUEST[$this->request_vars['group']];
        } else {
            $active_group = '';
        }
        if (!empty($_REQUEST[$this->request_vars['plugin']]) && !empty($this->plugins[$_REQUEST[$this->request_vars['plugin']]])) {
            $active_plugin = $_REQUEST[$this->request_vars['plugin']];
        } else {
            $active_plugin = '';
        }

        if (empty($active_group) && empty($active_plugin)) {
            if (!empty($this->default_group)) {
                $active_group = $this->default_group;
            } else {
                $tmp = $this->groups;
                $tmp = array_shift($tmp);
                $active_group = $tmp['alias'];
                unset($tmp);
            }
            if (!empty($this->groups[$active_group]['default_plugin'])) {
                $plugin_name = $this->groups[$active_group]['default_plugin'];
                if (!empty($this->plugins[$plugin_name]) && !empty($this->plugins[$plugin_name]['group']) && $this->plugins[$plugin_name]['group'] == $active_group) {
                    $active_plugin = $plugin_name;
                }
            }
            if (empty($active_plugin)) {
                while (list($plugin_name) = each($this->plugins)) {
                    if (!empty($this->plugins[$plugin_name]['group']) && $this->plugins[$plugin_name]['group'] == $active_group) {
                        $active_plugin = $plugin_name;
                        break;
                    }
                }
            }
        } elseif (!empty($active_plugin)) {
            $active_group = $this->plugins[$active_plugin]['group'];
            if (!empty($_REQUEST[$this->request_vars['command']])) {
                $active_command = $_REQUEST[$this->request_vars['command']];
            }
            if (!empty($_REQUEST[$this->request_vars['arguments']])) {
                $active_arguments = $_REQUEST[$this->request_vars['arguments']];
            }
        } elseif (!empty($active_group) && empty($active_plugin)) {
            if (!empty($this->groups[$active_group]['default_plugin'])) {
                $plugin_name = $this->groups[$active_group]['default_plugin'];
                if (!empty($this->plugins[$plugin_name]) && !empty($this->plugins[$plugin_name]['group']) && $this->plugins[$plugin_name]['group'] == $active_group) {
                    $active_plugin = $plugin_name;
                }
            }
            if (empty($active_plugin)) {
                while (list($plugin_name) = each($this->plugins)) {
                    if (!empty($this->plugins[$plugin_name]['group']) && $this->plugins[$plugin_name]['group'] == $active_group) {
                        $active_plugin = $plugin_name;
                        break;
                    }
                }
            }
        }
        $template = 'index_shell';
        if (empty($this->user_data) || $this->user_data['uid'] == 0) {
            $active_group = 'auth';
            $active_plugin = 'cms_auth';
            $template = 'login';
        } elseif ($plugin == 'cms_auth') {
            $template = 'login';
        } elseif ($this->user_data['cms_permissions'] == AP_NONE) {
            die('Access denied');
        } elseif ($this->user_data['cms_permissions'] == AP_PARTIAL) {
            /* rg@0606 */
            $plugins_access = $this->_get_plugins_access($this->user_data['uid']);
            $access_buttons = array(
                'назад' => 'javascript:history.go(-1);',
                'на главную' => $this->admin_url
            );

            if (!empty($active_command)) {
                $action = $active_command;
            } else {
                $action = 'index';
            }

            if (!array_key_exists($action, $plugins_access[$active_plugin]) && !empty($plugins_access[$active_plugin]['index']) && $plugins_access[$active_plugin]['index'] == true) {
            } elseif (array_key_exists($action, $plugins_access[$active_plugin]) && $plugins_access[$active_plugin][$action] == 0) {
                if (!empty($active_command)) {
                    $this->int_set_message('top', 'У вас недостаточно прав для выполнения операции (' . $active_plugin . ' -> ' . $action . ').', 'Access Denied', 'warning', $access_buttons);
                } else {
                    $this->int_set_message('top', 'У вас недостаточно прав для работы с модулем <b>' . $active_plugin . '</b>.', 'Access Denied', 'warning', $access_buttons);
                }
                $active_group = '';
                $active_plugin = '';
                $template = 'index_message';
            }

            if (!empty($active_arguments['s_id'])) {
                $structure_access = $this->_get_structure_access($this->user_data['uid']);
//				echo "<br><pre>";print_r($structure_access);die("<br><br>@".__FILE__.":".__LINE__);

                if (array_key_exists($active_arguments['s_id'], $structure_access) && $structure_access[$active_arguments['s_id']] == 1) {
                } elseif (array_key_exists($active_arguments['s_id'], $structure_access) && $structure_access[$active_arguments['s_id']] == 0) {
                    $this->int_set_message('top', 'У вас недостаточно прав для редактирование этой страницы.', 'Access Denied', 'warning', $access_buttons);
                    $active_group = '';
                    $active_plugin = '';
                    $template = 'index_message';
                }
            }

//			echo "<br><pre>$active_plugin<br />$action<br />";print_r($plugins_access);die("<br><br>@".__FILE__.":".__LINE__);
//			die('Access denied.');
            /* /rg@0606 */
        }
        if (!empty($active_plugin)) {
            $plugin_output = $this->_exec_plugin($active_plugin, $active_command, $active_arguments);
        } else {
            $plugin_output = '';
        }
        $this->set_template_var('body', $plugin_output);
        if (!empty($this->user_data) && $this->user_data['uid'] > 0) {
            $this->set_template_var('user_exit_url', $this->format_url('cms_auth', 'logout'));
            $this->set_template_var('user_exit_title', $this->translate('exit'));
            $this->set_template_var('user_name', $this->user_data['name']);
        }

        $this->_format_interface($active_plugin, $active_group);
        /* rg@0606 */
        /*
                if (!empty($this->user_data) && $this->user_data['uid']>0) {
                    $this->_display('index_shell');
                }
                else {
                    $this->_display('login');
                }
        */
        $this->_display($template);
        /* /rg@0606 */
    }

    function direct($plugin = '', $command = '', $arguments = array())
    {
        $this->reindex_configs();
        $this->load_config($this->site_id);
        $this->init_dbc();
        $this->startup();
        $this->init_admin_lang();
        $this->reindex_plugins();
        $this->_load_templates();
        $active_arguments = '';
        $active_command = '';
        if (!empty($_REQUEST[$this->request_vars['plugin']]) && !empty($this->plugins[$_REQUEST[$this->request_vars['plugin']]])) {
            $active_plugin = $_REQUEST[$this->request_vars['plugin']];
        } else {
            return false;
        }
        if (!empty($_REQUEST[$this->request_vars['command']])) {
            $active_command = $_REQUEST[$this->request_vars['command']];
        } else {
            return false;
        }
        if (!empty($_REQUEST[$this->request_vars['arguments']])) {
            $active_arguments = $_REQUEST[$this->request_vars['arguments']];
        }
        /* rg@0606 */
        $template = 'index_direct';
        if (empty($this->user_data) || $this->user_data['uid'] == 0) {
            return false;
        } else if ($this->user_data['cms_permissions'] == AP_NONE) {
            return false;
        } else if ($this->user_data['cms_permissions'] == AP_PARTIAL) {
            $plugins_access = $this->_get_plugins_access($this->user_data['uid']);
            $action = $active_command;
            if (!array_key_exists($action, $plugins_access[$active_plugin]) && !empty($plugins_access[$active_plugin]['index']) && $plugins_access[$active_plugin]['index'] == true) {
            } else if (array_key_exists($action, $plugins_access[$active_plugin]) && $plugins_access[$active_plugin][$action] == 0) {
                return false;
            }
        }
        /* /rg@0606 */
        if (!empty($active_plugin) && !empty($active_command)) {
            $plugin_output = $this->_exec_plugin($active_plugin, $active_command, $active_arguments);
        } else {
            $plugin_output = '';
        }
        $this->set_template_var('body', $plugin_output);
        /* rg@0606 */
        $this->_display($template);
        /* /rg@0606 */
    }

    function startup()
    {
        $startup = array();
        /*
        if (count($this->include['startup'])>0) {
            while (list(, $plugin_dir) = each($this->include['startup'])) {
                if ( (is_dir($this->startup_path.$plugin_dir)) && file_exists($this->startup_path.$plugin_dir.'/startup.php') && (array_search($plugin_dir, $this->exclude['startup']) === false)) {
                    $startup[$plugin_dir] = $this->startup_path.$plugin_dir.'/startup.php';
                }
            }
        } else */
        //die($this->startup_path);
        if ($handle = opendir($this->startup_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && (is_dir($this->startup_path . $file)) && file_exists($this->startup_path . $file . '/startup.php') && (array_search($file, $this->exclude['startup']) === false)) {
                    $startup[$file] = $this->startup_path . $file . '/startup.php';
                }
            }
            closedir($handle);
        } else {
            trigger_error("Error opening " . $this->startup_path . "");
        }
        ksort($startup);
        while (list($dir, $file) = each($startup)) {
            include_once($file);
        }
    }

    function reindex_configs()
    {
        $config_path = cms_CORE_PATH . 'config/';
        $sites = array();
        if ($handle = opendir($config_path)) {
            while (false !== ($file = readdir($handle))) {
                if (is_dir($config_path . $file) && file_exists($config_path . $file . '/config.php')) {
                    $sites[$file] = $file;
                }
            }
            closedir($handle);
            ksort($sites);
            $this->sites = $sites;
        } else {
            trigger_error("Error opening $config_path");
        }
        if (!empty($_COOKIE['pm_site_id'])) {
            $site_id = $_COOKIE['pm_site_id'];
            if (empty($sites[$site_id])) {
                $site_id = array_shift($sites);
            }
        } else {
            $site_id = array_shift($sites);
        }
        $this->site_id = $site_id;
        //$this->site_id = 'cms';
    }


    function reindex_plugins()
    {
        /*
            if (count($this->include['plugins'])>0) {
                while (list(, $plugin_dir) = each($this->include['plugins'])) {
                    if ( (is_dir($this->plugins_path.$plugin_dir)) && file_exists($this->plugins_path.$plugin_dir.'/plugin.php') && (array_search($plugin_dir, $this->exclude['plugins']) === false)) {
                        if ($plugin_info = $this->get_plugin_info($this->plugins_path.$plugin_dir.'/plugin.php')) {
                            $plugin_info['path'] = $this->plugins_path.$plugin_dir.'/';
                            $this->plugins[$plugin_info['name']] = $plugin_info;
                            if ($plugin_info['menu']) {
                                $this->plugins_menu[$plugin_info['name']] = $plugin_info['menu'];
                            }
                        }
                    }

                }
            } else */
        $groups_counter = array();
        if ($handle = opendir($this->plugins_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == "." || $file == ".." || (!is_dir($this->plugins_path . $file))) {
                    continue;
                } else if (file_exists($this->plugins_path . $file . '/plugin-info.php') && (array_search($file, $this->exclude['plugins']) === false)) {
                    if ($plugin_info = $this->_load_plugin_info($this->plugins_path . $file . '/plugin-info.php')) {
//						echo "<prE>"; print_r($plugin_info); echo "</pre>";
                        $plugin_info['path'] = $this->plugins_path . $file . '/';
                        $plugin_info['title'] = $this->translate($plugin_info['title'], $plugin_info['name']);
                        $this->plugins[$plugin_info['name']] = $plugin_info;
                        if (!empty($plugin_info['group'])) {
                            $groups_counter[] = $plugin_info['group'];
                        }
                        if (!empty($plugin_info['menu']) && !empty($plugin_info['sort_order'])) {
                            $this->plugins_menu[$plugin_info['sort_order'] . '_' . $plugin_info['name']] = $plugin_info['name'];
                        } else if (!empty($plugin_info['menu'])) {
                            $this->plugins_menu[$plugin_info['name']] = $plugin_info['name'];
                        }
                        $manager_file = $plugin_info['path'] . 'admin.class.php';
                        $handler_file = $plugin_info['path'] . 'handler.class.php';
                        $informer_file = $plugin_info['path'] . 'informer.class.php';
                        if (file_exists($handler_file)) {
                            $this->handlers[$plugin_info['name']] = $plugin_info;
                        }
                        if (file_exists($informer_file)) {
                            $this->handlers_list[$plugin_info['name']] = $plugin_info['title'];
                        }
                        if (file_exists($manager_file)) {
                            $this->managers_list[$plugin_info['name']] = $plugin_info['title'];
                        }
                    }
                } else if (file_exists($this->plugins_path . $file . '/group-info.php') && (array_search($file, $this->exclude['plugins']) === false)) {
                    if ($plugin_info = $this->_load_plugin_info($this->plugins_path . $file . '/group-info.php')) {
                        $plugin_info['path'] = $this->plugins_path . $file . '/';
                        $plugin_info['title'] = $this->translate($plugin_info['title'], $plugin_info['alias']);
                        $this->groups[$plugin_info['alias']] = $plugin_info;
                    }
                }
            }
//			echo "<prE>"; print_r($this->plugins_menu); echo "</pre>";
            $valid_groups = array_flip($groups_counter);
            while (list(, $plugin_info) = each($this->groups)) {
                if (empty($valid_groups[$plugin_info['alias']]) || empty($plugin_info['title'])) {
                    continue;
                }
                if (!empty($plugin_info['title']) && !empty($plugin_info['sort_order'])) {
                    $this->groups_menu[$plugin_info['sort_order'] . '_' . $plugin_info['alias']] = $plugin_info['alias'];
                } else {
                    $this->groups_menu[$plugin_info['alias']] = $plugin_info['alias'];
                }
            }
            closedir($handle);

            ksort($this->plugins_menu);
            //var_dump($this->plugins_menu); die();
            ksort($this->plugins);
            ksort($this->handlers);
            ksort($this->handlers_list);
            ksort($this->groups_menu);
        } else {
            trigger_error("Error opening " . $this->plugins_path . "");
        }
    }

    function _load_plugin_info($plugin_file)
    {
        include_once($plugin_file);
        if (!empty($plugin) && is_array($plugin)) {
            return $plugin;
        } else {
            return false;
        }
    }

    function _format_interface($active_plugin, $active_group)
    {
        /* rg@0606 */
        if (!isset($this->user_data['cms_permissions']) || isset($this->user_data['cms_permissions']) && $this->user_data['cms_permissions'] == AP_PARTIAL) {
            $plugins_access = $this->_get_plugins_access($this->user_data['uid']);
        }
        /* /rg@0606 */


        /*menu0*/
        $menu0 = '';
        while (list(, $g_alias) = each($this->groups_menu)) {
            $g_info = $this->groups[$g_alias];
            $tpl_alias = $g_alias == $active_group ? 'menu0a' : 'menu0';
            $g_info['group_alias'] = $g_alias;
            $g_info['group_url'] = $this->admin_url . '?' . $this->request_vars['group'] . '=' . $g_alias;
            $m_item = $this->parse_template($g_info, $tpl_alias);
            $menu0 .= $m_item;
        }
        $this->set_template_var('menu0', $menu0);


        /*menu1*/
        $menu1 = '';
//        reset($this->plugins_menu);
        $menu1_items = '';
        while (list(, $p_name) = each($this->plugins_menu)) {
            $p_info = $this->plugins[$p_name];
            if ($p_info['group'] <> $active_group || empty($p_info['menu']) || $p_info['menu'] == false) {
                continue;
            }
            /* rg@0606 */
            if ($this->user_data['cms_permissions'] == AP_PARTIAL) {
                $permissions = $plugins_access[$p_name];
                if (!array_key_exists('index', $permissions)) {
                    continue;
                } elseif (array_key_exists('index', $permissions) && $permissions['index'] == FALSE) {
                    continue;
                }
            }
            /* /rg@0606 */

            if ((array_key_exists('submenu_function', $this->plugins[$p_name]))) {
                $submenu_class = $this->plugins[$p_name]['name'] . '_submenu';
                include_once($this->plugins[$p_name]['path'] . 'submenu.class.php');
                $submenu = new $submenu_class();
                $submenu->plugin = $this->plugins[$p_name];
                $submenu->cms = &$this;
                $menu1 = $submenu->main();
            } else {
                $p_info['url'] = $this->format_url($p_name);
                $level = 2;
                $class = $p_name == $active_plugin ? ' a' : '';
                $menu1_items .= '<div class="l' . $level . $class . '"><a href="' . $p_info['url'] . '">' . $p_info['title'] . '</a></div>';

                if (isset($p_info['submenu']) && $p_name == $active_plugin) {
                    $sub_level = 3;
                    $sub_class = '';
                    foreach ($p_info['submenu'] as $cmd => $desc) {
                        $sub_info['url'] = "index.php?plg=" . $p_name . "&cmd=" . $cmd;
                        $sub_info['title'] = $desc;
                        $menu1_items .= '<div class="l' . $sub_level . $sub_class . '"><a href="' . $sub_info['url'] . '">' . $sub_info['title'] . '</a></div>';
                    }
                }

            }
        }
        if (!empty($menu1_items)) {
            $menu1['output'] = $menu1_items;
            $menu1 = $this->parse_template($menu1, 'menu1');
        }
        $this->set_template_var('menu1', $menu1);
        $languages = array();
        while (list($lang) = each($this->languages)) {
            if ($lang == $this->admin_lang) {
                $languages[] = '<b>' . $lang . '</b>';
            } else {
                $languages[] = '<a href="" onclick="switch_admin_lang(\'' . $lang . '\'); return false;">' . $lang . '</a>';
            }
        }
        /* rg@0606 */
        if (!empty($this->groups[$active_group])) {
            $this->set_template_var('active_group_title', $this->groups[$active_group]['title']);
        }
        /* /rg@0606 */
        $this->set_template_var('languages', implode(' | ', $languages));
        if (is_object($this->message_box)) {
            $this->set_template_var('messages_top', $this->message_box->get_group('top', true));
            $this->set_template_var('messages_bottom', $this->message_box->get_group('bottom', true));
        }

    }

    function _display($template)
    {
        //$content = $this->templates[$template];
        $title = parse_url($this->admin_url);
        $this->set_template_var('title', $title['host']);
        $this->set_template_var('hierarchy', '');
        $this->set_template_var('admin_url', $this->admin_url);
        $this->set_template_var('root_url', $this->root_url);
//        $this->set_template_var('act_grp_url', $this->admin_url.'?'.$this->request_vars['group'].'='.$_REQUEST[$this->request_vars['group']]);

        //$this->set_template_var('theme_url', 'themes/'.$this->theme.'/');

        $content = $this->parse_template($this->template_variables, $template);
        /*$parser = new strcom_parser();
        $variables = $parser->gettagslist($content, $this->tpl_prefix, $this->tpl_suffix);
        while (list(, $var) = each($variables)) {
        $content = str_replace($this->tpl_prefix.$var.$this->tpl_suffix,'',$content);
        }*/
        echo $content;
    }

    function _exec_plugin($active_plugin, $active_command = '', $active_arguments = array())
    {
        if (file_exists($this->plugins[$active_plugin]['path'] . 'admin.class.php')) {
            include_once($this->plugins[$active_plugin]['path'] . 'admin.class.php');
            $plugin_ai = new plugin_admin_interface();
            $plugin_ai->plugin = $this->plugins[$active_plugin];
            $plugin_ai->cms = &$this;
            $plugin_ai->dbc = &$this->dbc;
            if (file_exists($this->plugins[$active_plugin]['path'] . 'plugin.config.php')) {
                include_once($this->plugins[$active_plugin]['path'] . 'plugin.config.php');
                $plugin_ai->config = $plugin_config;
            }
            $plugin_output = $plugin_ai->main($active_command, $active_arguments);
        } else {
            $plugin_output = '';
            trigger_error('Plugin interface failed, file not exists: admin.class.php', E_USER_WARNING);
        }
        return $plugin_output;
    }

    function _parse_template($code, $variables)
    {
        while (list($var, $value) = each($variables)) {
            $code = str_replace($this->tpl_prefix . $var . $this->tpl_suffix, $value, $code);
        }
        return $code;
    }

    function format_url($plugin, $command = '', $arguments = array(), $target = '')
    {
        if (!empty($command)) {
            $command = '&' . $this->request_vars['command'] . '=' . $command;
        }
        $arguments_link = '';
        if (is_array($arguments) && count($arguments) > 0) {
            while (list($arg, $val) = each($arguments)) {
                $arguments_link .= '&' . $this->request_vars['arguments'] . '[' . $arg . ']=' . $val;
            }
        }
        if (empty($target)) {
            //$target = $this->admin_url;
        }
        return $target . '?' . $this->request_vars['plugin'] . '=' . $plugin . $command . $arguments_link;
    }

    function set_template_var($variable, $value)
    {
        $this->template_variables[$variable] = $value;
    }

    function _load_templates($force_reindex = false)
    {
        return false;
        $cache_file = $this->cache_path . $this->site_id . '-cms_tpl_cache.php';
        /*
        if (file_exists($cache_file)) {
           $file_age = time()-filemtime($cache_file);
           if ($file_age > 3600*24*7) {$force_reindex = true;}
        }
        */
        $cache = $this->get_cached_array($cache_file);
        if (!$force_reindex && (is_array($cache) && (count($cache) > 0))) {
            $this->templates = $cache;
        } else {
            $tpl_path = $this->themes_path . $this->theme . '/';
            if ($handle = opendir($tpl_path)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && substr($file, -4) == '.tpl') {
                        $tpl_content = file_get_contents($tpl_path . $file);
                        if ($tpl_content <> false) {
                            $name = substr($file, 0, -4);
                            if ($this->compress_html) {
                                $this->templates[$name] = $this->compress_html($tpl_content);
                            } else {
                                $this->templates[$name] = $tpl_content;
                            }
                        }
                    }
                }
            } else {
                trigger_error("Error opening " . $tpl_path . "");
            }
            $this->save_cache($cache_file, serialize($this->templates));
        }
    }

    /* interface */
    function int_set_h1($message)
    {
        $html = $this->parse_template(array('message' => $message), 'h1');
        $this->set_template_var('h1', $html);
    }

    function int_set_hierarchy($s_id = 0, $path2 = array())
    {
        $path = array();
//		echo $s_id;
        if ($s_id) {
            $topId = $this->getTopFromId($s_id);
            $urlFirst = $this->format_url('sa', 'index', array('s_id' => $topId));
        } else {
            $urlFirst = $this->format_url('sa', 'index', array());
        }
        $this->pathAdmin[] = '<a class="b-admin-breadcrumbs-item" href="' . $urlFirst . '">Структура</a>';
        if ($s_id) $this->getPathFromId($s_id);
        if (is_array($path) && count($path2) > 0) {
            $lastItem = '<span class="b-admin-breadcrumbs-item">' . $path2[count($path2) - 1] . '</span>';
            $lastItem = '<span class="b-admin-breadcrumbs-item">' . strip_tags($lastItem) . '</span>';
            $path2[count($path2) - 1] = $lastItem;

            $path2_html = ' &rarr; ' . implode(' &rarr; ', $path2);
        } else {
            $lastItem = '<span class="b-admin-breadcrumbs-item">' . $this->pathAdmin[count($this->pathAdmin) - 1] . '</span>';
            $lastItem = '<span class="b-admin-breadcrumbs-item">' . strip_tags($lastItem) . '</span>';
            $this->pathAdmin[count($this->pathAdmin) - 1] = $lastItem;

            $path2_html = '';
        }
        $html = '<div class="b-admin-breadcrumbs">' . implode(' — ', $this->pathAdmin) . $path2_html . '</div>';
        $this->set_template_var('hierarchy_path', $html);
        return true;
    }

    function intSetHierarchy()
    {
        $path = array();
        $lastItem = $this->pathModule[count($this->pathModule) - 1];
        $lastItem = '<span class="pathNow">' . strip_tags($lastItem) . '</span>';
        $this->pathModule[count($this->pathModule) - 1] = $lastItem;

//		echo "<pre>"; print_r($this->pathModule); echo "</pre>";

        $html = '<div class="path">' . implode(' \ ', $this->pathModule) . '</div>';
        $this->set_template_var('hierarchy_path', $html);
        return true;
    }

    function getPathFromId($id)
    {
        $sql = "SELECT parent, title FROM " . $this->tables["structure"] . " WHERE s_id='" . $id . "'";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        if ($row["parent"]) {
            $this->getPathFromId($row["parent"]);
            $this->pathAdmin[] = '<a href="' . $this->format_url('sim', 'man', array('s_id' => $id)) . '">' . $row['title'] . '</a>';
        } else {
            $this->pathAdmin[] = '<a href="' . $this->format_url('sim', 'man', array('s_id' => $id)) . '">' . $row['title'] . '</a>';
        }
    }

    function getTopFromId($s_id)
    {
        $sql = "SELECT language_id FROM " . $this->tables["structure"] . " WHERE s_id='" . $s_id . "'";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        $sql = "SELECT s_id FROM " . $this->tables["structure"] . " WHERE language_id='" . $row["language_id"] . "' AND level=1";
//		echo $sql."<br>";
        $result = $this->dbc->Execute($sql);
        $row = $result->FetchRow();
        return $row["s_id"];
    }

    function int_set_help()
    {
    }

    function int_set_message($group, $message, $caption = '', $msg_type = 'information', $buttons = array())
    {
        $this->init_message_box();
        $this->message_box->add($group, $message, $caption, $msg_type, $buttons);
    }

    function int_add_message($message, $caption = '', $msg_type = 'information', $buttons = array())
    {
        $this->init_message_box();
        return $this->message_box->get_message($message, $caption, $msg_type, $buttons);
    }

    function int_add_h1($message)
    {
        return $this->parse_template(array('message' => $message), 'h1');
    }

    function int_add_h2($message)
    {
        return $this->parse_template(array('message' => $message), 'h2');
    }

    function int_add_h3($message)
    {
        return $this->parse_template(array('message' => $message), 'h3');
    }

    function int_add_tabs($tabs, $active)
    {
        $html = '';
        while (list($id, $code) = each($tabs)) {
            if ($id == $active) {
                $html .= $this->parse_template(array('code' => $code), 'tabs_active');
            } else {
                $html .= $this->parse_template(array('code' => $code), 'tabs_inactive');
            }
        }
        return $this->parse_template(array('tabs' => $html), 'tabs_block');;
    }

    function int_get_contex_menu($items = array())
    {
        static $menu_id = 0;
        $menu_id++;
        $options = '<option class="faded">Выберите модуль</option>';
        while (list($title, $url) = each($items)) {
            $options .= '<option value="' . $url . '">' . $title . '</option>';
        }
        $variables = array(
            'options' => $options,
            'menu_id' => $menu_id,
        );
        $html = $this->parse_template($variables, 'block_add');
        return $html;
    }

    function init_message_box()
    {
        if (is_object($this->message_box)) {
            return true;
        }
        $this->message_box = new message_box($this->admin_url . 'img/');
        $this->parse_template(array(), 'message_box_body', false);
        $this->parse_template(array(), 'message_box_message', false);
        $this->parse_template(array(), 'message_box_button', false);
        if (!empty($this->templates['message_box_body'])) {
            $this->message_box->templates['message_box'] = $this->templates['message_box_body'];
        }
        if (!empty($this->templates['message_box_message'])) {
            $this->message_box->templates['message_body'] = $this->templates['message_box_message'];
        }
        if (!empty($this->templates['message_box_button'])) {
            $this->message_box->templates['button'] = $this->templates['message_box_button'];
        }
    }

    /*trabslate*/

    function init_admin_lang()
    {
        $this->languages = array("ru" => "ru");
        return "ru";


        $this->languages = array();
        $languages = array();
        $query = 'SELECT DISTINCT(lang) FROM ' . $this->tables['admin_translate'] . ' WHERE lang>"" ORDER BY lang';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            $this->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            $languages['ru'] = 'ru';
        } else if ($result->RecordCount() > 0) {
            while ($row = $result->FetchRow()) {
                $languages[$row['lang']] = $row['lang'];
            }
        }
        $this->languages = $languages;
        if (!empty($_COOKIE['pm_admin_lang']) && !empty($languages[$_COOKIE['pm_admin_lang']])) {
            $this->admin_lang = $_COOKIE['pm_admin_lang'];
            return true;
        }
        $this->admin_lang = array_shift($languages);
//	echo "<pre>"; print_r($this->admin_lang); echo "</prE>";
        return true;
    }

    function translate($source, $plugin = '')
    {
        /* rg@0606 */
        return $source;
        /* /rg@0606 */
        $query = 'SELECT * FROM ' . $this->tables['admin_translate'] . ' WHERE source="' . addslashes($source) . '" and lang="' . addslashes($this->admin_lang) . '" and plugin="' . addslashes($plugin) . '"';
        //die($query);
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query);
            //$this->int_set_message('top', $this->dbc->ErrorMsg().'<br>Query: '.$query, 'SQL Error', 'error');
            return false;
        } else if ($result->RecordCount() == 0) {
            $insert = array(
                'source' => "'" . addslashes($source) . "'",
                'lang' => "'" . $this->admin_lang . "'",
                'plugin' => "'" . $plugin . "'",
                'trans' => "''"
            );
            $insert_fields = '';
            $insert_values = '';
            $sql_sep = '';
            while (list($field, $value) = each($insert)) {
                $insert_fields .= $sql_sep . $field;
                $insert_values .= $sql_sep . $value;
                $sql_sep = ', ';
            }
            $query = 'INSERT INTO ' . $this->tables['admin_translate'] . ' (' . $insert_fields . ') VALUES(' . $insert_values . ')';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                $this->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $query, 'SQL Error', 'error');
                return false;
            }
            return $source;
        } else if ($result->RecordCount() == 1) {
            $row = $result->FetchRow();
            if (!empty($row['trans'])) {
                return $row['trans'];
            } else {
                return $source;
            }
        }
        return $source;
    }

    function parse_template($variables, $template_alias, $clean = true)
    {
        $template_file = $this->themes_path . $this->theme . '/' . $template_alias . '.tpl';

        // Check tpl file
        if (empty($this->templates[$template_alias]) && file_exists($template_file)) {
            $this->templates[$template_alias] = file_get_contents($template_file);
        } else if (empty($this->templates[$template_alias])) {
            trigger_error('Template (' . $template_alias . ') not found: ' . $template_file, E_USER_ERROR);
            return false;
        }

        $content = $this->templates[$template_alias];

        if (!is_array($variables)) {
            trigger_error('Wrong data type of passed variables (array expected)', E_USER_NOTICE);
            return $content;
        }

        while (list($var, $value) = each($variables)) {
//			echo $this->tpl_prefix.$var.$this->tpl_suffix.", ".$value.", ".$content."<br>";
            if (is_array($value)) {
//				echo "<pre>"; print_r($value); echo "</pre>"; 
                $value = implode(",", $value);
            }
            $content = str_replace($this->tpl_prefix . $var . $this->tpl_suffix, $value, $content);
        }

        if ($clean) {
            $parser = new strcom_parser();
            $variables = $parser->gettagslist($content, $this->tpl_prefix, $this->tpl_suffix);

            while (list(, $var) = each($variables)) {
                $content = str_replace($this->tpl_prefix . $var . $this->tpl_suffix, '', $content);
            }
        }

        return $content;
    }

    /* rg@0606 */
    function _get_plugins_access($uid, $summary = true)
    {
        $plugins = array();
        reset($this->plugins);
        while (list(, $plugin) = each($this->plugins)) {
            if (array_key_exists('permissions', $plugin) && $plugin['permissions'] === false) {
                continue;
            }
            $permissions = array();
            if (!empty($this->handlers_list[$plugin['name']])) {
                $permissions = array(
                    'block_add' => 0,
                    'block_delete' => 0,
                    'block_move' => 0,
                    'block_switch' => 0,
                    'block_props' => 0,
                    'block_edit' => 0,
                    'block_inheritance' => 0,
                );
            }
            $permissions['index'] = 0;
            if (!empty($plugin['permissions']) && is_array($plugin['permissions'])) {
                while (list($action,) = each($plugin['permissions'])) {
                    $permissions[$action] = 0;
                }
                //} else {
                //    $plugins[$plugin['name']]['index'] = 0;
            }

            $plugins[$plugin['name']] = $permissions;
        }
        $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid=' . $uid . ' and type="action" ORDER BY plugin, object';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        }
        while ($row = $result->FetchRow()) {
            $plugins[$row['plugin']][$row['object']] = $row['value'];
        }
        if ($summary) {
            $query = 'SELECT * FROM ' . $this->tables['users'] . ' WHERE uid=' . $uid . '';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            $user = $result->FetchRow();
            $groups = array();
            if (empty($user['member_of_groups'])) {
                return $plugins;
            }
            $tmp = explode('|', $user['member_of_groups']);
            foreach ($tmp as $gid) {
                if (empty($gid)) {
                    continue;
                }
                $groups[] = $gid;
            }
            if (count($groups) == 0) {
                return $plugins;
            }
            $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid IN (' . implode(', ', $groups) . ') and type="action" and value="1" ORDER BY plugin, object';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            while ($row = $result->FetchRow()) {
                $plugins[$row['plugin']][$row['object']] = $row['value'];
            }
        }
        return $plugins;
    }

    function _get_structure_access($uid, $summary = true)
    {
        $structure = array();
        $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid=' . $uid . ' and type="structure" ORDER BY object';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        }
        while ($row = $result->FetchRow()) {
            $structure[$row['object']] = $row['value'];
        }

        if ($summary) {
            $query = 'SELECT * FROM ' . $this->tables['users'] . ' WHERE uid=' . $uid . '';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            $user = $result->FetchRow();
            $groups = array();
            if (empty($user['member_of_groups'])) {
                return $structure;
            }
            $tmp = explode('|', $user['member_of_groups']);
            foreach ($tmp as $gid) {
                if (empty($gid)) {
                    continue;
                }
                $groups[] = $gid;
            }
            if (count($groups) == 0) {
                return $structure;
            }
            $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid IN (' . implode(', ', $groups) . ') and type="structure" and value="1" ORDER BY plugin, object';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            while ($row = $result->FetchRow()) {
                $structure[$row['object']] = $row['value'];
            }
        }
        return $structure;
    }
    /* /rg@0606 */

    /* rg@060719 */
    function _get_groups_access($uid, $summary = true)
    {
        $groups_access = array();

        $query = 'SELECT * FROM ' . $this->tables['users'] . ' where ugroup=1 ORDER BY name asc';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        } else if ($result->RecordCount() == 0) {
            return $groups;
        }
        while ($block = $result->FetchRow()) {
            $groups_access[$block['uid']] = 0;
        }

        $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid=' . $uid . ' and type="group" and value=1 ORDER BY object';
        $result = $this->dbc->Execute($query);
        if (!$result) {
            trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
            return false;
        }
        while ($row = $result->FetchRow()) {
            $groups_access[$row['object']] = $row['value'];
        }

        if ($summary) {
            $query = 'SELECT * FROM ' . $this->tables['users'] . ' WHERE uid=' . $uid . '';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            $user = $result->FetchRow();
            $groups = array();
            if (empty($user['member_of_groups'])) {
                return $groups_access;
            }
            $tmp = explode('|', $user['member_of_groups']);
            foreach ($tmp as $gid) {
                if (empty($gid)) {
                    continue;
                }
                $groups[] = $gid;
            }
            if (count($groups) == 0) {
                return $groups_access;
            }
            $query = 'SELECT * FROM ' . $this->tables['user_permissions'] . ' WHERE uid IN (' . implode(', ', $groups) . ') and type="group" and value=1';
            $result = $this->dbc->Execute($query);
            if (!$result) {
                trigger_error($this->dbc->ErrorMsg() . '<br>Query: ' . $query, E_USER_ERROR);
                return false;
            }
            while ($row = $result->FetchRow()) {
                $groups_access[$row['object']] = $row['value'];
            }
        }
        return $groups_access;
    }

    function _get_allowed_groups($uid)
    {
        $groups_access = $this->_get_groups_access($uid);
        $allowed_groups = array();
        foreach ($groups_access as $gid => $allowed) {
            if ($allowed) {
                $allowed_groups[$gid] = 1;
            }
        }
        return $allowed_groups;
    }

    /* /rg@060719 */


} /* cms_admin class end */

function _check_sid($s_id)
{
    $s_id = (int)$s_id;
    return $s_id;
}

?>