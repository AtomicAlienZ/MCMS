<?php
/**
* @package      phpcms
* @subpackage   core
*/
class cms_plugin_admin {
    var $plugin         = array();
    var $config         = array();
    var $dbc;
    var $fob;
    var $cms         = '';

    function init_fob($form_name, $form_action) {
        $fob = new form_builder($form_name, $form_action, 'post', '', '', '', 'multipart/form-data');
/*
        $fob->templates['form'] = "\n<table aligh=center border=0 cellspacing=0 cellpadding=0 class=fob-table>\n[#form]\n[#main]\n</table></form><br><br>\n";
        $fob->templates['input'] = "\n<tr><td width=120 valign=top class=fob-td><b>[#title]</b>[#required]<br>[#description]</td><td class=fob-td>\n[#input]&nbsp;<small>[#caption]</small>[#error]</td></tr>";
        $fob->templates['required'] = '&nbsp;&nbsp;<sup><font color=red><a title="required field">&bull;</a></font></sup>';
        $fob->templates['error'] = '<div style="border: 1px red solid; background-color:#FFCCCC; margin-top:3px;"><b><font color=#aa0000>Error:</font></b><br>[#error_message]</div>';
        $fob->templates['html_code'] = "\n<tr><td colspan=2 valign=top class=fob-td>\n[#input]</td></tr>";
        $fob->templates['date_input1'] = "\n<tr><td width=120 valign=top class=fob-td><b>[#title]</b>[#required]<br>[#description]</td><td class=fob-td>\n[#input]<button id=\"jsc_trigger\">...</button>&nbsp;<small>[#caption]</small>[#error]</td></tr>";
        $fob->templates['date_input'] = "\n<tr><td width=120 valign=top class=fob-td><b>[#title]</b>[#required]<br>[#description]</td><td class=fob-td>\n[#input]<img src=\"/js/img.gif\" id=\"jsc_trigger\" style=\"cursor: pointer;\" border=0>&nbsp;<small>[#caption]</small>[#error]</td></tr>";
        $fob->validate_error_messages = array(
                '2short'   => "Field is to short",
                '2long'    => "Field is to long",
                'format'   => "Wrong data format",
                'required' => "Required field"
                );

        $fob->add_element_patern('text', array(
                                'size'          => 70,
                                'maxlength'     => 100,
                                'class'         => 'text'
                                 ));
        $fob->add_element_patern('date_input', array(
                                'id'            => 'js_date',
                                'size'          => 30,
                                'maxlength'     => 100,
                                'class'         => 'text'
                                 ));

        $fob->add_element_patern('textarea', array(
                                'rows'          => 5,
                                'cols'          => 50,
                                'class'         => 'textearea'
                                 ));
*/
        return $fob;
    }


    function translate($string) {
        return $this->cms->translate($string, $this->plugin['name']);
    }


    function init() {
        require_once(cms_LIB_PATH.'form_builder.class.php');
        //$this->cms->init_dbc();
        $this->dbc = &$this->cms->dbc;
    }


    function main($command, $arguments) {
        $this->init();
        $this->cms->int_set_h1($this->plugin['title']);
        if (empty($command)) {
            $command = 'index';
        }
        if ($command[0]!='_' && method_exists($this, $command)) {
            $result = $this->$command($arguments);
            return $result;
        }
        return $result;
    }



}

?>
