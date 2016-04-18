<?php

class plugin_admin_interface extends cms_plugin_admin
{
    function exec($a = null, $b = null, $c = null)
    {
        var_dump($a, $b, $c);
        die;
    }
}